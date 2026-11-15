<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_decreases_when_order_paid(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $initialStock = $product->stock;
        $orderQty = 3;

        // 1. Buat pesanan
        $resOrder = $this->postJson('/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Budi Stok Test',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $orderQty,
                    'unit_price' => $product->price,
                ]
            ],
            'is_held' => false,
        ]);

        $resOrder->assertStatus(200);
        $orderId = $resOrder->json('order.id');

        // 2. Bayar pesanan
        $resPay = $this->postJson("/payments/{$orderId}/pay", [
            'payment_method' => 'cash',
            'amount_paid' => $product->price * $orderQty * 2,
        ]);

        $resPay->assertStatus(200);

        // 3. Verifikasi stok berkurang di database
        $product->refresh();
        $this->assertEquals($initialStock - $orderQty, $product->stock);

        // 4. Verifikasi mutasi stok tercatat
        $movement = StockMovement::where('product_id', $product->id)->where('type', 'out')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-$orderQty, $movement->quantity);
        $this->assertEquals($initialStock, $movement->stock_before);
        $this->assertEquals($initialStock - $orderQty, $movement->stock_after);
    }

    public function test_mechanism_to_restock_product(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $initialStock = $product->stock;
        $restockQty = 25;
        $newCostPrice = 16000.00;

        // Eksekusi Restock
        $response = $this->postJson('/inventory/restock', [
            'product_id' => $product->id,
            'quantity' => $restockQty,
            'cost_price' => $newCostPrice,
            'reference_no' => 'FAKTUR-RESTOCK-001',
            'notes' => 'Kulakan bahan tambahan',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verifikasi stok bertambah
        $product->refresh();
        $this->assertEquals($initialStock + $restockQty, $product->stock);
        $this->assertEquals($newCostPrice, $product->cost_price);

        // Verifikasi log mutasi stok masuk
        $movement = StockMovement::where('product_id', $product->id)->where('type', 'in')->first();
        $this->assertNotNull($movement);
        $this->assertEquals($restockQty, $movement->quantity);
        $this->assertEquals($restockQty * $newCostPrice, $movement->total_cost);
    }
}
