<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestaurantTable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_and_pay(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $table = RestaurantTable::first();

        // 1. Buat pesanan baru
        $response = $this->postJson('/orders', [
            'order_type' => 'dine-in',
            'table_id' => $table->id,
            'customer_name' => 'Budi Santoso',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => $product->price,
                    'notes' => 'Tanpa cabai',
                ]
            ],
            'is_held' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $orderId = $response->json('order.id');

        // 2. Eksekusi Pembayaran
        $payResponse = $this->postJson("/payments/{$orderId}/pay", [
            'payment_method' => 'cash',
            'amount_paid' => 100000.00,
        ]);

        $payResponse->assertStatus(200);
        $payResponse->assertJsonPath('success', true);
        $payResponse->assertJsonPath('order.status', 'completed');
    }

    public function test_can_hold_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();

        $response = $this->postJson('/orders', [
            'order_type' => 'takeaway',
            'customer_name' => 'Pelanggan Nanti Bayar',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                ]
            ],
            'is_held' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('order.status', 'held');
    }

    public function test_manager_cannot_act_as_cashier(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = \App\Models\User::where('role', 'manager')->first();
        $product = Product::first();

        // Coba buat pesanan saat aktif sebagai manager
        $response = $this->withSession(['active_user_id' => $manager->id])
            ->postJson('/orders', [
                'order_type' => 'dine-in',
                'customer_name' => 'Testing Manajer Blokir',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => $product->price,
                    ]
                ],
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }
}
