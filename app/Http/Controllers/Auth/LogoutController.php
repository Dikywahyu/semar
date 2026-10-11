<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Cek apakah user memiliki token aktif (untuk integrasi Passport)
            // Method 'token()' sekarang tersedia karena HasApiTokens sudah dipasang
            if (method_exists($user, 'token') && $user->token()) {
                $user->token()->revoke();
            }

            // Logout dari session web
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
