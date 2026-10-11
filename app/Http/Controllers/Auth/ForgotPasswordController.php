<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | Kontroler ini bertanggung jawab untuk menangani email reset kata sandi.
    | Kita menggunakan Trait 'SendsPasswordResetEmails' bawaan Laravel 
    | untuk menjaga kode tetap bersih dan aman.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Menampilkan formulir untuk meminta tautan reset kata sandi.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Kita bisa melakukan override pada method 'validateEmail' jika 
     * ingin membatasi reset password hanya untuk domain kampus tertentu.
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    }
}
