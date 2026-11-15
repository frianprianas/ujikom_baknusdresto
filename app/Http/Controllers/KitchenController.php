<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $kitchenOrders = KitchenOrder::with(['order.table', 'order.items', 'items.orderItem.product'])
            ->whereIn('status', ['pending', 'cooking', 'ready'])
            ->orderBy('sent_to_kitchen_at', 'asc')
            ->get();

        return view('kitchen.index', compact('kitchenOrders'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,cooking,ready,served',
        ]);

        $kot = KitchenOrder::findOrFail($id);
        $kot->status = $validated['status'];
        if ($validated['status'] === 'served' || $validated['status'] === 'ready') {
            $kot->completed_at = now();
        }
        $kot->save();

        // Update items status juga
        $kot->items()->update(['status' => $validated['status'] === 'served' ? 'ready' : $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan dapur berhasil diperbarui',
            'status' => $kot->status,
        ]);
    }
}
