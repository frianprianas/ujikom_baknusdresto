<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = RestaurantTable::with(['activeOrder.items'])
            ->orderBy('floor_section')
            ->orderBy('table_number')
            ->get();

        $sections = RestaurantTable::select('floor_section')->distinct()->pluck('floor_section');

        return view('tables.index', compact('tables', 'sections'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $table = RestaurantTable::findOrFail($id);
        $table->status = $validated['status'];
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Status meja diperbarui',
            'table' => $table,
        ]);
    }

    public function printAllQr()
    {
        $tables = RestaurantTable::orderBy('floor_section')->orderBy('table_number')->get();
        return view('tables.print_qr', compact('tables'));
    }
}
