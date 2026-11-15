<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_request_otp_and_set_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Request OTP Registrasi
        $regResponse = $this->postJson('/api/mobile/auth/register-otp', [
            'name' => 'Budi Pelanggan',
            'phone' => '081234567899',
            'address' => 'Jl. Kebon Jeruk No. 10',
            'address_benchmark' => 'Pagar hitam depan Masjid',
        ]);

        $regResponse->assertStatus(200);
        $regResponse->assertJsonPath('success', true);
        $otp = $regResponse->json('data.otp_simulation');
        $phone = $regResponse->json('data.phone');

        // 2. Verifikasi OTP dan Set Password
        $verifyResponse = $this->postJson('/api/mobile/auth/verify-otp-password', [
            'phone' => $phone,
            'otp_code' => $otp,
            'password' => 'secret123',
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonPath('success', true);
        $verifyResponse->assertJsonPath('data.user.role', 'customer');

        // 3. Login dengan No HP & Password
        $loginResponse = $this->postJson('/api/mobile/auth/login', [
            'phone' => $phone,
            'password' => 'secret123',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonPath('success', true);
        $loginResponse->assertJsonPath('data.user.name', 'Budi Pelanggan');
        $loginResponse->assertJsonPath('data.user.address_benchmark', 'Pagar hitam depan Masjid');
    }
}
