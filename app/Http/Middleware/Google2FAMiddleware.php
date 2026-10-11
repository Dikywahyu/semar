<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Google2FAMiddleware
{
    // public function handle(Request $request, Closure $next)
    // {
    //     // Bypass rute verifikasi/setup agar tidak terjadi infinite loop
    //     if ($request->is('auth/2fa/verify*') || $request->is('auth/2fa/setup*')) {
    //         return $next($request);
    //     }

    //     // 1. Pastikan user sudah login dasar
    //     if (!Auth::check()) {
    //         return redirect()->route('login');
    //     }

    //     $user = Auth::user();

    //     // 🔴 KUNCI UTAMA: Cek dulu, apakah user ini mengaktifkan 2FA di database?
    //     // Jika di database bernilai 0 (tidak aktif), LANGSUNG LOLOSKAN ke dashboard!
    //     if (!$user->two_factor_enabled) {
    //         return $next($request);
    //     }

    //     // 2. CEK PERTAMA: Jika AKTIF tapi belum punya Secret Key, suruh setup QR Code
    //     if (empty($user->google2fa_secret)) {
    //         return redirect()->route('2fa.setup');
    //     }

    //     // 3. CEK KEDUA: Jika AKTIF dan sudah punya secret, pastikan sudah isi OTP di sesi ini
    //     if (!session()->has('google2fa.auth_passed')) {
    //         return redirect()->route('2fa.index');
    //     }

    //     return $next($request);
    // }
    
    public function handle(Request $request, Closure $next)
    {
        // 1. BYPASS UTAMA: Izinkan semua URL yang mengandung kata 2fa terbuka
        // Ini agar halaman pengisian OTP /auth/auth/2fa/verify tidak terkunci loop!
        if ($request->is('*2fa*')) {
            return $next($request);
        }

        // 2. Pastikan user sudah login dasar (lolos verifikasi Google/Password)
        if (!Auth::check()) {
            return redirect('/auth');
        }

        $user = Auth::user();

        // 3. JIKA USER TIDAK AKTIFKAN 2FA (di database nilainya 0)
        // Langsung loloskan dia masuk ke dashboard IAM atau kembali ke SIAKAD!
        if (!$user->two_factor_enabled) {
            return $next($request);
        }

        // 4. JIKA USER MENGAKTIFKAN 2FA (di database nilainya 1):
        // Periksa apakah sesi browser ini sudah lulus mengisi OTP?
        // Jika sudah ada session 'google2fa.auth_passed', silakan loloskan ke SIAKAD.
        if (session()->has('google2fa.auth_passed')) {
            return $next($request);
        }

        // 5. Jika aktif tapi belum punya Secret Key di database, paksa ke halaman setup QR Code
        if (empty($user->google2fa_secret)) {
            return redirect()->route('2fa.setup');
        }

        // 6. Jika aktif, sudah punya secret, tapi BELUM isi OTP di sesi ini,
        // MAKA LEMPAR PAKSA KE HALAMAN VERIFIKASI OTP BIAR DIA MENGISI KODE DI SANA!
        return redirect()->route('2fa.index');
    }
}