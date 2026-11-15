<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    /**
     * 1. Registrasi & Kirim OTP Simulasi
     * Cukup nama, no telepon, alamat, dan patokan jalan.
     */
    public function requestRegisterOtp(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:255',
            'address_benchmark' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Generate OTP simulasi 4 digit
        $otp = (string) rand(1000, 9999);

        // Cari atau buat user customer
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $user = new User();
            $user->role = 'customer';
            $user->email = $phone . '@customer.baknusdresto.com'; // fallback unique email
        }

        $user->name = $validated['name'];
        $user->phone = $phone;
        $user->address = $validated['address'];
        $user->address_benchmark = $validated['address_benchmark'] ?? null;
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->is_active = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Kode OTP simulasi berhasil dikirim ke nomor {$validated['phone']}.",
            'data' => [
                'phone' => $phone,
                'otp_simulation' => $otp, // Disimulasikan agar user bisa langsung tes tanpa gateway berbayar
                'expires_at' => $user->otp_expires_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * 2. Verifikasi OTP & Buat Password Akun
     */
    public function verifyOtpAndSetPassword(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:4',
            'password' => 'required|string|min:6',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon belum terdaftar. Silakan registrasi terlebih dahulu.',
            ], 404);
        }

        // Verifikasi OTP
        if ($user->otp_code !== $validated['otp_code']) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah. Silakan periksa kembali kode OTP Anda.',
            ], 422);
        }

        if ($user->otp_expires_at && Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kadaluarsa. Silakan minta kode OTP baru.',
            ], 422);
        }

        // Simpan Password
        $user->password = Hash::make($validated['password']);
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Registrasi dan pembuatan password berhasil! Anda dapat langsung login.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'address_benchmark' => $user->address_benchmark,
                    'role' => $user->role,
                ]
            ]
        ]);
    }

    /**
     * 3. Login Pelanggan dengan No HP & Password
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon atau password salah.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => "Selamat datang kembali, {$user->name}!",
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'address_benchmark' => $user->address_benchmark,
                    'role' => $user->role,
                ]
            ]
        ]);
    }

    /**
     * 4. Update Alamat & Patokan Jalan
     */
    public function updateAddress(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'address' => 'required|string|max:255',
            'address_benchmark' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        $user->address = $validated['address'];
        $user->address_benchmark = $validated['address_benchmark'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Alamat dan patokan jalan berhasil diperbarui.',
            'data' => [
                'address' => $user->address,
                'address_benchmark' => $user->address_benchmark,
            ]
        ]);
    }
}
