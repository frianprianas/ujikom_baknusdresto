<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    /**
     * 1. Daftar Kategori
     */
    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * 2. Daftar Menu & Stok Terkini
     */
    public function products(Request $request)
    {
        $query = Product::where('is_available', true)->with('category');

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search') && $request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }

        $products = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * 3. Daftar Meja Restoran (untuk Dine-In Self-Order)
     */
    public function tables()
    {
        $tables = RestaurantTable::orderBy('table_number')->get();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    /**
     * 4. Buat Pesanan Baru (Online Delivery atau Dine-In Self Order)
     * CATATAN PENTING: Pada tahap ini user baru "PESAN SAJA" (belum bayar).
     * Stok BELUM berkurang, meja BELUM di-booking sampai pembayaran dikonfirmasi.
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:dine-in,delivery,takeaway',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
            'delivery_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validated['order_type'] === 'dine-in' && empty($validated['table_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Untuk pesanan Dine-in tanpa kasir, silakan pilih nomor meja yang tersedia.'
            ], 422);
        }

        if ($validated['order_type'] === 'delivery' && empty($validated['delivery_address'])) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat pengantaran wajib diisi untuk pesanan delivery.'
            ], 422);
        }

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $orderItemsData = [];

            // Validasi ketersediaan stok
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi (Tersedia: {$product->stock} porsi).");
                }

                $lineSubtotal = $product->price * $item['quantity'];
                $subtotal += $lineSubtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $lineSubtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // Hitung Pajak PB1 10%
            $taxRate = 10.0;
            $taxAmount = ($subtotal * $taxRate) / 100;
            $totalAmount = $subtotal + $taxAmount;

            $orderNumber = 'MOB-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'status' => 'open', // Masih belum bayar
                'delivery_status' => 'pending',
                'is_paid' => false,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // PERHATIKAN: STOK BELUM DIKURANGI DAN MEJA BELUM DIUBAH!
            // Menunggu aksi pembayaran user sesuai instruksi.

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat. Silakan lakukan pembayaran untuk memproses pesanan dan mengunci stok/meja.',
                'data' => $order->load(['items', 'table'])
            ], 201);
        });
    }

    /**
     * 5. Bayar Pesanan Langsung di Aplikasi (Self-Pay)
     * EFEK SETELAH DIBAYAR:
     * 1. Stok produk langsung BERKURANG & tercatat di stock_movements.
     * 2. Jika Dine-In: status Meja berubah menjadi "reserved" (Booking).
     * 3. Jika Delivery: status pengantaran berubah menjadi "preparing" (Sedang Dimasak).
     * 4. KOT otomatis terbuat untuk koki dapur.
     */
    public function payOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:qris,gopay,ovo,dana,shopeepay,va_bca,va_mandiri,va_bri',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($id);

            if ($order->is_paid || $order->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan ini sudah dibayar sebelumnya.'
                ], 400);
            }

            if ($validated['amount_paid'] < $order->total_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah pembayaran kurang dari total tagihan.'
                ], 422);
            }

            // 1. KURANGI STOK MENU DAN CATAT STOCK MOVEMENT
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        if ($product->stock < $item->quantity) {
                            throw new \Exception("Maaf, stok {$product->name} tidak cukup saat pembayaran (Sisa: {$product->stock}).");
                        }

                        $stockBefore = $product->stock;
                        $product->decrement('stock', $item->quantity);
                        $stockAfter = $product->fresh()->stock;

                        StockMovement::create([
                            'product_id' => $product->id,
                            'user_id' => null, // Pembayaran mandiri via aplikasi mobile
                            'type' => 'out',
                            'quantity' => -$item->quantity, // Negatif karena pengurangan stok keluar
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'unit_cost' => $product->cost_price,
                            'total_cost' => $product->cost_price * $item->quantity,
                            'reference_no' => $order->order_number,
                            'notes' => 'Pembayaran Mandiri Mobile: ' . strtoupper($order->order_type) . ' #' . $order->order_number,
                        ]);
                    }
                }
            }

            // 2. JIKA DINE-IN: UBAH STATUS MEJA MENJADI "reserved" (BOOKING)
            $tableInfo = null;
            if ($order->order_type === 'dine-in' && $order->table_id) {
                $table = RestaurantTable::lockForUpdate()->find($order->table_id);
                if ($table) {
                    $table->update(['status' => 'reserved']); // Meja berubah jadi booking
                    $tableInfo = $table;
                }
            }

            // 3. CATAT TRANSAKSI PEMBAYARAN SESUAI METODE YANG DIPILIH (GOPAY, OVO, DANA, QRIS, DLL)
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => null,
                'invoice_number' => 'INV-MOB-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $validated['amount_paid'],
                'change_due' => max(0, $validated['amount_paid'] - $order->total_amount),
                'reference_no' => strtoupper($validated['payment_method']) . '-' . strtoupper(Str::random(8)),
                'paid_at' => now(),
            ]);

            // 4. UPDATE STATUS ORDER
            $order->update([
                'is_paid' => true,
                'status' => 'completed',
                'delivery_status' => $order->order_type === 'delivery' ? 'preparing' : null,
            ]);

            // 5. BUAT TIKET DAPUR (KOT)
            $kot = KitchenOrder::create([
                'order_id' => $order->id,
                'kot_number' => 'KOT-MOB-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'status' => 'pending',
                'sent_to_kitchen_at' => now(),
            ]);

            foreach ($order->items as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kot->id,
                    'order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                    'status' => 'pending',
                    'notes' => $item->notes,
                ]);
            }

            $message = $order->order_type === 'dine-in'
                ? "Pembayaran berhasil! Meja {$tableInfo?->table_number} telah berhasil di-BOOKING dan stok menu telah terpotong. Koki dapur sedang menyiapkan makanan Anda."
                : "Pembayaran berhasil! Stok makanan telah terpotong dan pesanan masuk dapur untuk disiapkan.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'order' => $order->fresh(['items', 'table', 'payment']),
                    'payment' => $payment,
                    'table_status' => $tableInfo ? $tableInfo->status : null,
                ]
            ]);
        });
    }

    /**
     * 6. Detail Status Pesanan & Pelacakan Pengantaran (Tracking)
     */
    public function showOrder($id)
    {
        $order = Order::with(['items', 'table', 'payment'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * 7. Simulasi Tahapan Pengantaran Makanan (Delivery Simulation)
     * Urutan: pending -> preparing (Dimasak) -> on_the_way (Diantar Driver) -> delivered (Sampai)
     */
    public function advanceDeliverySimulation(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->order_type !== 'delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi pengantaran hanya berlaku untuk pesanan bertipe delivery.'
            ], 400);
        }

        if (!$order->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan belum dibayar. Silakan lakukan pembayaran terlebih dahulu agar makanan dimasak dan diantar.'
            ], 400);
        }

        $nextStatus = match ($order->delivery_status) {
            'pending' => 'preparing',
            'preparing' => 'on_the_way',
            'on_the_way' => 'delivered',
            'delivered' => 'delivered',
            default => 'preparing'
        };

        if ($request->has('force_status') && in_array($request->force_status, ['preparing', 'on_the_way', 'delivered'])) {
            $nextStatus = $request->force_status;
        }

        $order->update([
            'delivery_status' => $nextStatus
        ]);

        $statusDescriptions = [
            'preparing' => 'Pesanan sedang diracik dan dimasak oleh Chef Baknus Dresto.',
            'on_the_way' => 'Driver Baknus Express sedang dalam perjalanan mengantarkan makanan hangat ke alamat Anda.',
            'delivered' => 'Pesanan telah sampai di lokasi tujuan! Selamat menikmati santapan Anda.'
        ];

        return response()->json([
            'success' => true,
            'message' => $statusDescriptions[$nextStatus] ?? 'Status diperbarui',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'delivery_status' => $nextStatus,
                'status_label' => match($nextStatus) {
                    'preparing' => 'Sedang Dimasak',
                    'on_the_way' => 'Sedang Diantar Driver',
                    'delivered' => 'Tiba di Tujuan',
                    default => 'Menunggu Pembayaran'
                },
                'description' => $statusDescriptions[$nextStatus] ?? '',
                'estimated_minutes' => match($nextStatus) {
                    'preparing' => 15,
                    'on_the_way' => 8,
                    'delivered' => 0,
                    default => 25
                }
            ]
        ]);
    }
}
