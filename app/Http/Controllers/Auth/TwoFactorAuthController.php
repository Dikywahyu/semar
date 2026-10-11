<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthController extends Controller
{
    public function index()
    {
        return view('auth.2fa.index');
    }

    /**
     * VERIFIKASI LOGIN RUTIN
     */
    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|numeric',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        // Verifikasi manual menggunakan secret user dan input OTP
        if ($google2fa->verifyKey($user->google2fa_secret, $request->one_time_password)) {

            // Penting: Library ini biasanya mencari key ini di session
            // agar middleware '2fa' mengizinkan akses.
            $request->session()->put('google2fa.auth_passed', true);

            // Log aktivitas login aman untuk unit BAAK
            activity()->log('User passed 2FA verification');

            if (! $user->roles()->exists()) {
                return redirect()->route('account.claim');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['one_time_password' => 'Kode OTP salah atau sudah kedaluwarsa.']);
    }

    /**
     * SETUP QR CODE
     */
    public function setup()
    {
        /** @var User $user */
        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        if (! $user->google2fa_secret) {
            // Generate secret key baru
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        return view('auth.2fa.setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $user->google2fa_secret,
        ]);
    }

    /**
     * AKTIVASI PERTAMA KALI
     */
    public function activate(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|numeric',
        ]);

        $google2fa = app('pragmarx.google2fa');
        /** @var User $user */
        $user = Auth::user();

        // Verifikasi manual menggunakan verifyKey
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->update(['two_factor_enabled' => true]);

            activity()->log('2FA security enabled via Google Authenticator');

            if (! $user->roles()->exists()) {
                return redirect()->route('account.claim')
                    ->with('success', '2FA aktif. Silakan klaim akun akademik Anda.');
            }

            return redirect('/dashboard')->with('success', '2FA berhasil diaktifkan.');
        }

        return back()->withErrors(['one_time_password' => 'Kode salah, silakan coba lagi.']);
    }
}
