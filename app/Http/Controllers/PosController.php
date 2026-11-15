<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shift;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $products = Product::where('is_available', true)
            ->with('category')
            ->orderBy('name')
            ->get();
        $tables = RestaurantTable::orderBy('table_number')->get();
        $heldOrders = Order::where('status', 'held')->with('items')->orderByDesc('updated_at')->get();
        $activeShift = Shift::where('status', 'open')->latest()->first();

        return view('pos.index', compact('categories', 'products', 'tables', 'heldOrders', 'activeShift'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $categoryId = $request->get('category_id');

        $products = Product::where('is_available', true)
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->when($query, function ($q) use ($query) {
                return $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->get();

        return response()->json($products);
    }
}
