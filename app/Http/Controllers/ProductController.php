<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('category_id')->orderBy('name')->get();
        $categories = Category::all();

        return view('products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code',
            'barcode' => 'nullable|string',
            'name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'icon_type' => 'nullable|string',
        ]);

        $validated['cost_price'] = $validated['cost_price'] ?? 0;
        $validated['stock'] = $validated['stock'] ?? 100;
        $validated['is_available'] = true;

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan!',
            'product' => $product->load('category'),
        ]);
    }
}
