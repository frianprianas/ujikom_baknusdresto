<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $todayOrders = Order::whereDate('created_at', today())->get();
        $totalSalesToday = $todayOrders->where('status', 'completed')->sum('total_amount');
        $totalOrdersCount = $todayOrders->count();
        $activeOrdersCount = Order::whereIn('status', ['open', 'held'])->count();
        $occupiedTablesCount = RestaurantTable::where('status', 'occupied')->count();
        $totalTablesCount = RestaurantTable::count();

        // Nilai aset persediaan di dapur
        $totalStockAsset = Product::sum(DB::raw('stock * cost_price'));

        // Total HPP terjual hari ini
        $todayCompletedOrderIds = $todayOrders->where('status', 'completed')->pluck('id');
        $todayCostOfGoods = OrderItem::whereIn('order_id', $todayCompletedOrderIds)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->sum(DB::raw('order_items.quantity * products.cost_price'));

        $todayGrossProfit = max(0, $totalSalesToday - $todayCostOfGoods);

        // Best Seller Items (Urutkan dari terbanyak)
        $topProducts = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed');
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(8)
            ->get();

        $recentOrders = Order::with(['table', 'payment'])->latest()->limit(8)->get();

        // Stok Rendah untuk Peringatan Dapur & Manajer
        $lowStockProducts = Product::where('stock', '<=', 15)->orderBy('stock', 'asc')->limit(6)->get();

        // Data Grafik Pesanan & Omzet 7 Hari Terakhir
        $chartLabels = [];
        $chartOrderCounts = [];
        $chartRevenues = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayName = $date->translatedFormat('d M'); // e.g. "08 Nov"
            
            $dayOrders = Order::whereDate('created_at', $date)->where('status', 'completed')->get();

            $chartLabels[] = $dayName;
            $chartOrderCounts[] = $dayOrders->count();
            $chartRevenues[] = (int) $dayOrders->sum('total_amount');
        }

        return view('dashboard.index', compact(
            'totalSalesToday',
            'todayCostOfGoods',
            'todayGrossProfit',
            'totalStockAsset',
            'totalOrdersCount',
            'activeOrdersCount',
            'occupiedTablesCount',
            'totalTablesCount',
            'topProducts',
            'recentOrders',
            'lowStockProducts',
            'chartLabels',
            'chartOrderCounts',
            'chartRevenues'
        ));
    }

    public function switchUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        session(['active_user_id' => $user->id]);

        return redirect()->back()->with('status', "Beralih akun ke {$user->name} ({$user->role})");
    }
}
