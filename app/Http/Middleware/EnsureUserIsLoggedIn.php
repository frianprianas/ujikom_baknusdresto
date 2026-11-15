<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        // Abaikan route autentikasi, simulasi switch, dan API
        if ($request->is('login*') || $request->is('switch-user*') || $request->is('logout*') || $request->is('api/*')) {
            return $next($request);
        }

        // Jika user secara eksplisit telah logout
        if (session('is_logged_out', false)) {
            return redirect()->route('login')->with('warning', 'Sesi telah berakhir. Silakan pilih akun simulasi untuk masuk kembali.');
        }

        return $next($request);
    }
}
