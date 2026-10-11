<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | Kontroler ini menangani permintaan reset password setelah user 
    | mengklik link di email. Trait ResetsPasswords menangani semua 
    | logika validasi token dan update database.
    |
    */

    use ResetsPasswords;

    /**
     * Tempat tujuan setelah password berhasil diubah.
     */
    protected $redirectTo = '/dashboard';

    /**
     * Menampilkan form reset (ini dipanggil dari link di email).
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }
}
