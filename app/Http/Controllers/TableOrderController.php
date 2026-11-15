<?php

namespace App\Http\Controllers;

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

class TableOrderController extends Controller
{
    /**
     * Tampilkan Halaman Self-Order Pelanggan via Scan QR Meja
     */
    public function showTableOrder(Request $request, $tableId)
    {
        // Cari meja berdasarkan ID atau nomor meja
        $table = RestaurantTable::where('id', $tableId)
            ->orWhere('table_number', $tableId)
            ->firstOrFail();

        // Ambil kategori aktif dan produk yang tersedia
        $categories = Category::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_available', true)->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->get();

        $allProducts = Product::where('is_available', true)
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('order.table', compact('table', 'categories', 'allProducts'));
    }

    /**
     * Proses Pemesanan & Pembayaran Mandiri dari Meja Pelanggan
     */
    public function storeTableOrder(Request $request, $tableId)
    {
        $table = RestaurantTable::where('id', $tableId)
            ->orWhere('table_number', $tableId)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'required|string|in:qris,gopay,ovo,dana,shopeepay,cashier',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($table, $validated) {
            $subtotal = 0;
            $itemsToProcess = [];

            // 1. Verifikasi stok dan hitung subtotal
            foreach ($validated['items'] as $itemData) {
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                if (!$product) {
                    throw new \Exception("Produk tidak ditemukan.");
                }

                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi (sisa: {$product->stock} porsi).");
                }

                $itemSubtotal = $product->price * $itemData['quantity'];
                $subtotal += $itemSubtotal;

                $itemsToProcess[] = [
                    'product' => $product,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemSubtotal,
                    'notes' => $itemData['notes'] ?? null,
                ];
            }

            // Hitung Pajak Restoran PB1 (10%)
            $taxRate = 10.00;
            $taxAmount = round($subtotal * 0.10);
            $totalAmount = $subtotal + $taxAmount;

            $isDigitalPayment = ($validated['payment_method'] !== 'cashier');
            $orderNumber = 'QR-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 2. Buat Record Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => null, // Dipesan mandiri oleh pelanggan lewat scan QR Meja
                'table_id' => $table->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'order_type' => 'dine-in',
                'status' => $isDigitalPayment ? 'completed' : 'open',
                'delivery_status' => 'preparing',
                'is_paid' => $isDigitalPayment,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 3. Simpan Item Pesanan
            foreach ($itemsToProcess as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'],
                ]);

                // Jika pembayaran digital mandiri, potong stok seketika
                if ($isDigitalPayment) {
                    $stockBefore = $item['product']->stock;
                    $item['product']->decrement('stock', $item['quantity']);
                    $stockAfter = $item['product']->fresh()->stock;

                    StockMovement::create([
                        'product_id' => $item['product']->id,
                        'user_id' => null,
                        'type' => 'out',
                        'quantity' => -$item['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_cost' => $item['product']->cost_price,
                        'total_cost' => $item['product']->cost_price * $item['quantity'],
                        'reference_no' => $order->order_number,
                        'notes' => 'Self-Order QR ' . $table->table_number . ' #' . $order->order_number,
                    ]);
                }
            }

            // 4. Jika Pembayaran Digital: Catat Payment Record
            if ($isDigitalPayment) {
                Payment::create([
                    'order_id' => $order->id,
                    'user_id' => null,
                    'invoice_number' => 'INV-QR-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                    'payment_method' => $validated['payment_method'],
                    'amount_paid' => $totalAmount,
                    'total_bill' => $totalAmount,
                    'change_amount' => 0,
                    'status' => 'success',
                ]);
            }

            // 5. Buat Kitchen Order (KOT) untuk koki dapur
            $kitchenOrder = KitchenOrder::create([
                'order_id' => $order->id,
                'kot_number' => 'KOT-QR-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'status' => 'pending',
                'notes' => 'Pesanan Mandiri QR ' . $table->table_number . ' - Atas Nama: ' . $validated['customer_name'],
            ]);

            foreach ($itemsToProcess as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'],
                    'status' => 'pending',
                ]);
            }

            // 6. Update Status Meja Menjadi Occupied
            $table->update(['status' => 'occupied']);

            return response()->json([
                'success' => true,
                'message' => $isDigitalPayment
                    ? 'Pembayaran berhasil! Pesanan Anda telah diteruskan ke dapur koki.'
                    : 'Pesanan berhasil dikirim ke kasir! Silakan bayar di kasir atau tunggu pelayan.',
                'order_number' => $order->order_number,
                'table_number' => $table->table_number,
                'total_amount' => $totalAmount,
                'is_paid' => $isDigitalPayment,
                'payment_method' => $validated['payment_method'],
            ]);
        });
    }
}
