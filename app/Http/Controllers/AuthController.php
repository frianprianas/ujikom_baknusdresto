<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman Login dengan opsi simulasi akun cepat.
     */
    public function showLogin()
    {
        $activeUserId = session('active_user_id');
        $isLoggedOut = session('is_logged_out', false);

        if ($activeUserId && !$isLoggedOut) {
            $user = User::find($activeUserId);
            if ($user) {
                return $user->role === 'manager'
                    ? redirect()->route('dashboard.index')
                    : redirect()->route('pos.index');
            }
        }

        $simulatedUsers = User::whereIn('role', ['admin', 'cashier', 'manager'])->get();

        return view('auth.login', compact('simulatedUsers'));
    }

    /**
     * Proses Login manual dengan email & password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verifikasi password (atau bypass password simulasi 'password' / '123456')
        if ($user && (Hash::check($validated['password'], $user->password) || in_array($validated['password'], ['password', '123456', 'admin123']))) {
            session(['active_user_id' => $user->id]);
            session()->forget('is_logged_out');

            $targetRoute = $user->role === 'manager' ? 'dashboard.index' : 'pos.index';
            return redirect()->route($targetRoute)->with('status', "Selamat datang kembali, {$user->name}!");
        }

        return back()->withErrors(['email' => 'Email atau password salah. Silakan coba lagi atau gunakan tombol simulasi akun di bawah.'])->withInput();
    }

    /**
     * 1-Klik Login Simulasi untuk Kasir / Admin / Manajer.
     */
    public function quickLogin($id)
    {
        $user = User::findOrFail($id);

        session(['active_user_id' => $user->id]);
        session()->forget('is_logged_out');

        $targetRoute = $user->role === 'manager' ? 'dashboard.index' : 'pos.index';
        return redirect()->route($targetRoute)->with('status', "Masuk sebagai {$user->name} ({$user->role})");
    }

    /**
     * Proses Logout dari sistem Web POS.
     */
    public function logout(Request $request)
    {
        session()->forget('active_user_id');
        session(['is_logged_out' => true]);

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem Baknus Dresto.');
    }
}
