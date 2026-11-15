<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('stock', 'asc')->get();
        $categories = Category::all();

        // Financial & stock metrics
        $totalStockValue = $products->sum(function ($p) {
            return $p->stock * $p->cost_price;
        });

        $lowStockCount = $products->where('stock', '<=', 10)->where('stock', '>', 0)->count();
        $outOfStockCount = $products->where('stock', '<=', 0)->count();
        $totalUnits = $products->sum('stock');

        // Total biaya pembelian/restock bulan ini
        $monthlyRestockCost = StockMovement::where('type', 'in')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_cost');

        // Recent movements
        $recentMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->paginate(15);

        return view('inventory.index', compact(
            'products',
            'categories',
            'totalStockValue',
            'lowStockCount',
            'outOfStockCount',
            'totalUnits',
            'monthlyRestockCost',
            'recentMovements'
        ));
    }

    public function restock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);
            $user = User::first();

            $qty = intval($validated['quantity']);
            $costPrice = floatval($validated['cost_price']);
            $totalCost = $qty * $costPrice;

            $stockBefore = $product->stock;
            $stockAfter = $stockBefore + $qty;

            // Update product stock and update cost_price (modal baru jika ada)
            $product->stock = $stockAfter;
            $product->cost_price = $costPrice;
            $product->is_available = true;
            $product->save();

            // Record stock movement (IN)
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user?->id,
                'type' => 'in',
                'quantity' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $costPrice,
                'total_cost' => $totalCost,
                'reference_no' => $validated['reference_no'] ?? 'RESTOCK-' . date('Ymd') . '-' . strtoupper(substr(md5(microtime()), 0, 4)),
                'notes' => $validated['notes'] ?? 'Penambahan stok / Restock bahan',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Stok {$product->name} berhasil ditambahkan sebanyak {$qty} unit!",
                'product' => $product,
                'movement' => $movement,
            ]);
        });
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'new_stock' => 'required|integer|min:0',
            'notes' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);
            $user = User::first();

            $stockBefore = $product->stock;
            $newStock = intval($validated['new_stock']);
            $diff = $newStock - $stockBefore;

            $product->stock = $newStock;
            $product->is_available = $newStock > 0;
            $product->save();

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user?->id,
                'type' => 'adjustment',
                'quantity' => $diff,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'unit_cost' => $product->cost_price,
                'total_cost' => abs($diff) * $product->cost_price,
                'reference_no' => 'ADJ-' . date('Ymd') . '-' . strtoupper(substr(md5(microtime()), 0, 4)),
                'notes' => $validated['notes'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Stok {$product->name} berhasil disesuaikan menjadi {$newStock} unit!",
                'product' => $product,
            ]);
        });
    }
}
