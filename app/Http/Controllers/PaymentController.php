<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function pay(Request $request, $orderId)
    {
        $activeUserId = session('active_user_id');
        $user = $activeUserId ? User::find($activeUserId) : User::first();

        // Blokir jika pengguna adalah Manager
        if ($user && $user->role === 'manager') {
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Akun Manajer ({$user->name}) tidak diizinkan memproses pembayaran kasir!",
            ], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,qris,debit,credit,transfer',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $orderId) {
            $order = Order::with('items')->findOrFail($orderId);

            if ($order->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Pesanan ini sudah dibayar!'], 400);
            }

            $amountPaid = floatval($validated['amount_paid']);
            if ($amountPaid < $order->total_amount && $validated['payment_method'] === 'cash') {
                return response()->json([
                    'success' => false,
                    'message' => 'Uang tunai yang dibayar kurang dari total tagihan!',
                ], 422);
            }

            $changeDue = max(0, $amountPaid - $order->total_amount);
            $user = User::first();
            $invoiceNumber = 'INV/' . date('Ymd') . '/' . strtoupper(Str::random(6));

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'invoice_number' => $invoiceNumber,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $amountPaid,
                'change_due' => $changeDue,
                'reference_no' => $validated['reference_no'] ?? null,
                'paid_at' => now(),
            ]);

            // Update status pesanan
            $order->update(['status' => 'completed']);

            // Update status meja jika dine in
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
            }

            // Update shift kasir jika ada
            if ($order->shift_id && $validated['payment_method'] === 'cash') {
                Shift::where('id', $order->shift_id)->increment('cash_sales', $order->total_amount);
            }

            // Kurangi stok barang & catat arus keluar (Stock Out)
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $prod = Product::find($item->product_id);
                    if ($prod) {
                        $stockBefore = $prod->stock;
                        $stockAfter = max(0, $stockBefore - $item->quantity);
                        $prod->update([
                            'stock' => $stockAfter,
                            'is_available' => $stockAfter > 0
                        ]);

                        StockMovement::create([
                            'product_id' => $prod->id,
                            'user_id' => $user?->id,
                            'type' => 'out',
                            'quantity' => -$item->quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'unit_cost' => $prod->cost_price,
                            'total_cost' => $prod->cost_price * $item->quantity,
                            'reference_no' => $payment->invoice_number,
                            'notes' => 'Penjualan Kasir Order ' . $order->order_number,
                        ]);
                    }
                }
            }

            // Buat Kitchen Order Ticket (KOT) jika belum ada
            $existingKot = KitchenOrder::where('order_id', $order->id)->first();
            if (!$existingKot) {
                $kot = KitchenOrder::create([
                    'order_id' => $order->id,
                    'kot_number' => 'KOT-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
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
            }

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil!',
                'payment' => $payment,
                'order' => $order->load(['items', 'table', 'payment']),
            ]);
        });
    }

    public function receipt($orderId)
    {
        $order = Order::with(['items.product', 'table', 'payment', 'user'])->findOrFail($orderId);

        return view('pos.receipt', compact('order'));
    }
}
