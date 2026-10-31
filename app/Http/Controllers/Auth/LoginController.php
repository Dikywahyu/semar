<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    /**
     * Tampilkan form login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * ðŸŸ¢ FUNGSI BARU: Memproses input form login manual Email & Password
     */
    public function login(Request $request)
    {
        // 1. Validasi input form
        $request->validate([
            'username' => 'required_without:email|nullable|string',
            'email' => 'required_without:username|nullable|string|email',
            'password' => 'required|string',
        ]);

        // NIM/NIDN maupun email dapat dipakai sebagai identitas login.
        $identifier = trim((string) $request->input('username', $request->input('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
            ->orWhere('identifier_number', $identifier)
            ->first();
        if ($user && ! $user->active) { return back()->withInput()->withErrors(['username' => 'Akun ini telah dinonaktifkan oleh administrator.']); }

        $remember = $request->filled('remember');

        // 3. Lakukan percobaan login (Attempt)
        if ($user && Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // SINKRONISASI AKTIVITAS LOG
            activity()
                ->causedBy($user)
                ->log('User logged in via manual form');

            // ðŸŸ¢ JIKA USER MENGAKTIFKAN 2FA:
            // Buat intended mengarah ke dashboard, lalu biarkan Google2FAMiddleware yang mencegatnya!
            if ($user->two_factor_enabled) {
                if (! $user->roles()->exists()) {
                    $request->session()->put('url.intended', route('account.claim'));

                    return redirect('/dashboard');
                }

                return redirect()->intended('/dashboard');
            }

            if (! $user->roles()->exists()) {
                return redirect()->route('account.claim');
            }

            // Jika tidak aktif 2FA, langsung lolos ke dashboard utama
            return redirect()->intended($this->redirectPath());
        }

        // 4. Jika password/email salah, kembalikan ke halaman login dengan pesan eror bawaan
        return redirect()->back()
            ->withInput([
                'username' => $identifier,
                'remember' => $remember,
            ])
            ->withErrors([
                'username' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.',
            ]);
    }

    /**
     * Logika setelah user berhasil login manual (Fallback).
     */
    protected function authenticated(Request $request, $user)
    {
        // Tetap jaga fungsi log aktivitas lama Anda jika dibutuhkan
    }
}
