<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $activeUserId = session('active_user_id');
        $user = $activeUserId ? User::find($activeUserId) : User::first();

        // Blokir jika pengguna adalah Manager (Manager tidak bisa bertindak seperti kasir)
        if ($user && $user->role === 'manager') {
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak: Akun Manajer ({$user->name}) hanya memiliki hak pemantauan, tidak diizinkan bertindak sebagai kasir!",
            ], 403);
        }

        $validated = $request->validate([
            'order_type' => 'required|in:dine-in,takeaway,delivery',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'customer_name' => 'nullable|string|max:100',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
            'tip_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'is_held' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = User::first(); // Default logged-in user / Alex Morgan
            $activeShift = Shift::where('status', 'open')->latest()->first();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']);
            }

            $discountType = $validated['discount_type'] ?? 'percent';
            $discountVal = floatval($validated['discount_value'] ?? 0);
            $discountAmount = 0;
            if ($discountType === 'percent') {
                $discountAmount = ($subtotal * $discountVal) / 100;
            } else {
                $discountAmount = min($subtotal, $discountVal);
            }

            $taxRate = floatval($validated['tax_rate'] ?? 10.00);
            $taxable = max(0, $subtotal - $discountAmount);
            $taxAmount = ($taxable * $taxRate) / 100;
            $serviceCharge = floatval($validated['service_charge'] ?? 0);
            $tipAmount = floatval($validated['tip_amount'] ?? 0);
            $totalAmount = $taxable + $taxAmount + $serviceCharge + $tipAmount;

            $status = !empty($validated['is_held']) ? 'held' : 'open';

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id,
                'table_id' => $validated['table_id'] ?? null,
                'shift_id' => $activeShift?->id,
                'customer_name' => $validated['customer_name'] ?? 'Walk-in',
                'order_type' => $validated['order_type'],
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountVal,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'service_charge' => $serviceCharge,
                'tip_amount' => $tipAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Update status meja jika dine-in
            if (!empty($validated['table_id']) && $validated['order_type'] === 'dine-in') {
                RestaurantTable::where('id', $validated['table_id'])->update(['status' => 'occupied']);
            }

            return response()->json([
                'success' => true,
                'message' => $status === 'held' ? 'Pesanan berhasil di-hold (disimpan sementara)' : 'Pesanan berhasil dibuat',
                'order' => $order->load(['items', 'table']),
            ]);
        });
    }

    public function getHeldOrders()
    {
        $held = Order::where('status', 'held')
            ->with(['items.product', 'table'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($held);
    }

    public function recallHeldOrder($id)
    {
        $order = Order::with(['items.product', 'table'])->findOrFail($id);

        if ($order->status !== 'held') {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak dalam status held'], 400);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    public function cancel($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'cancelled']);

        if ($order->table_id) {
            RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan',
        ]);
    }
}
