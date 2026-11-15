<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_metrics_and_charts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard', false);
        $response->assertSee('dailyOrdersChart', false);
        $response->assertSee('Menu Terlaris (Best Seller)', false);
        $response->assertSee('Pemantauan Stok Kritis', false);
    }

    public function test_manager_can_access_dashboard_and_inventory(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('role', 'manager')->first();

        $response = $this->withSession(['active_user_id' => $manager->id])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang, Hendro Wibowo', false);
        $response->assertSee('Pengawas Manajer', false);

        $invResponse = $this->withSession(['active_user_id' => $manager->id])
            ->get('/inventory');

        $invResponse->assertStatus(200);
        $invResponse->assertSee('Kontrol Stok', false);
    }
}
