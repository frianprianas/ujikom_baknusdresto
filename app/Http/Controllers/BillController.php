<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('status', 'completed')
            ->with(['payment', 'table', 'user', 'items'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('bills.index', compact('orders'));
    }
}
