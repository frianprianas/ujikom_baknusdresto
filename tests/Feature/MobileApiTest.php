<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_categories_products_and_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        $catRes = $this->getJson('/api/mobile/categories');
        $catRes->assertStatus(200);
        $catRes->assertJsonPath('success', true);

        $prodRes = $this->getJson('/api/mobile/products');
        $prodRes->assertStatus(200);
        $prodRes->assertJsonPath('success', true);

        $tableRes = $this->getJson('/api/mobile/tables');
        $tableRes->assertStatus(200);
        $tableRes->assertJsonPath('success', true);
    }

    public function test_order_only_does_not_decrease_stock_and_does_not_book_table(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $initialStock = $product->stock;
        $table = RestaurantTable::where('status', 'available')->first();

        // 1. Pesan saja (belum bayar)
        $orderRes = $this->postJson('/api/mobile/orders', [
            'order_type' => 'dine-in',
            'table_id' => $table->id,
            'customer_name' => 'Pelanggan Meja 01',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        $orderRes->assertStatus(201);
        $orderRes->assertJsonPath('success', true);
        $orderRes->assertJsonPath('data.is_paid', false);

        // Verifikasi: STOK TIDAK BERKURANG
        $this->assertEquals($initialStock, $product->fresh()->stock);

        // Verifikasi: STATUS MEJA MASIH AVAILABLE (BELUM DIBOOKING)
        $this->assertEquals('available', $table->fresh()->status);
    }

    public function test_payment_decreases_stock_and_books_table_for_dine_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $initialStock = $product->stock;
        $table = RestaurantTable::where('status', 'available')->first();

        // 1. Pesan meja dan menu
        $orderRes = $this->postJson('/api/mobile/orders', [
            'order_type' => 'dine-in',
            'table_id' => $table->id,
            'customer_name' => 'Budi Santoso Mandiri',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        $orderId = $orderRes->json('data.id');
        $totalAmount = $orderRes->json('data.total_amount');

        // 2. Bayar langsung di aplikasi (Self-Pay)
        $payRes = $this->postJson("/api/mobile/orders/{$orderId}/pay", [
            'payment_method' => 'qris',
            'amount_paid' => $totalAmount,
        ]);

        $payRes->assertStatus(200);
        $payRes->assertJsonPath('success', true);
        $payRes->assertJsonPath('data.order.is_paid', true);
        $payRes->assertJsonPath('data.table_status', 'reserved');

        // Verifikasi: STOK BERKURANG 2
        $this->assertEquals($initialStock - 2, $product->fresh()->stock);

        // Verifikasi: STATUS MEJA BERUBAH JADI 'reserved' (BOOKING)
        $this->assertEquals('reserved', $table->fresh()->status);
    }

    public function test_delivery_order_payment_and_simulation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::first();
        $initialStock = $product->stock;

        // 1. Pesan Online Delivery
        $orderRes = $this->postJson('/api/mobile/orders', [
            'order_type' => 'delivery',
            'customer_name' => 'Siti Nurhaliza',
            'customer_phone' => '081234567890',
            'delivery_address' => 'Jl. Merdeka No. 45, Jakarta',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ]);

        $orderId = $orderRes->json('data.id');
        $totalAmount = $orderRes->json('data.total_amount');

        // Sebelum bayar: stok belum berkurang
        $this->assertEquals($initialStock, $product->fresh()->stock);

        // 2. Bayar online
        $payRes = $this->postJson("/api/mobile/orders/{$orderId}/pay", [
            'payment_method' => 'gopay',
            'amount_paid' => $totalAmount,
        ]);

        $payRes->assertStatus(200);
        // Setelah bayar: stok berkurang 1
        $this->assertEquals($initialStock - 1, $product->fresh()->stock);
        $this->assertEquals('preparing', $payRes->json('data.order.delivery_status'));

        // 3. Simulasi Tahapan Pengantaran: Menuju ke driver (on_the_way)
        $sim1 = $this->postJson("/api/mobile/orders/{$orderId}/simulate-delivery");
        $sim1->assertStatus(200);
        $sim1->assertJsonPath('data.delivery_status', 'on_the_way');

        // 4. Simulasi Tahapan Pengantaran: Sampai tujuan (delivered)
        $sim2 = $this->postJson("/api/mobile/orders/{$orderId}/simulate-delivery");
        $sim2->assertStatus(200);
        $sim2->assertJsonPath('data.delivery_status', 'delivered');
    }
}
