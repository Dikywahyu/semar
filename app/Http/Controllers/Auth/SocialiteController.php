<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use App\Models\UserCredential;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class SocialiteController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = Str::lower(trim((string) $googleUser->getEmail()));
            abort_unless(filled($email), 422, 'Google tidak memberikan alamat email untuk akun ini.');

            $name = trim((string) $googleUser->getName()) ?: $email;
            $avatarUrl = $googleUser->getAvatar();
            $googleEmailVerified = filter_var(
                data_get($googleUser->getRaw(), 'verified_email', false),
                FILTER_VALIDATE_BOOL
            );
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            $isNewUser = false;
            $plainPassword = null;

            if (! $user) {
                $dosen = Dosen::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('aktif', true)
                    ->first();
                $mahasiswa = MahasiswaRegistrasi::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('aktif', true)
                    ->first();
                $isBootstrapAdmin = $email === 'dikywsp@gmail.com';

                if ($dosen && $mahasiswa) {
                    return redirect('/login')->with(
                        'error',
                        'Email ini terdaftar sebagai dosen dan mahasiswa sekaligus. Hubungi administrator untuk memperbaiki data.'
                    );
                }

                if (! $dosen && ! $mahasiswa && ! $isBootstrapAdmin) {
                    $plainPassword = Str::random(40);
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $plainPassword,
                        'avatar' => $avatarUrl,
                        'institution' => 'Universitas Tribhuwana Tunggadewi',
                        'role' => 'participant',
                        'last_seen' => now(),
                    ]);
                    $isNewUser = true;
                } else {
                    $plainPassword = Str::random(40);
                    $academicProfile = $isBootstrapAdmin && ! $dosen && ! $mahasiswa
                        ? [
                            'name' => $name,
                            'identifier_number' => null,
                            'phone' => null,
                            'position_title' => 'Administrator',
                            'role_name' => 'Super Admin',
                        ]
                        : ($dosen
                            ? [
                                'name' => $dosen->nama_dosen,
                                'identifier_number' => $dosen->nidn ?: $dosen->nuptk,
                                'phone' => $dosen->telepon,
                                'position_title' => 'Dosen',
                                'role_name' => 'Dosen',
                            ]
                            : [
                                'name' => $mahasiswa->nama_mahasiswa,
                                'identifier_number' => $mahasiswa->nim,
                                'phone' => $mahasiswa->telepon,
                                'position_title' => 'Mahasiswa',
                                'role_name' => 'Mahasiswa',
                            ]);

                    $user = DB::transaction(function () use ($email, $name, $avatarUrl, $plainPassword, $academicProfile) {
                        $user = User::create([
                            'name' => filled($academicProfile['name']) ? $academicProfile['name'] : $name,
                            'email' => $email,
                            'password' => $plainPassword,
                            'avatar' => $avatarUrl,
                            'identifier_number' => $academicProfile['identifier_number'],
                            'phone' => $academicProfile['phone'],
                            'position_title' => $academicProfile['position_title'],
                            'institution' => 'Universitas Tribhuwana Tunggadewi',
                            'role' => 'participant',
                            'last_seen' => now(),
                        ]);

                        $role = Role::firstOrCreate([
                            'name' => $academicProfile['role_name'],
                            'guard_name' => 'web',
                        ]);
                        $user->assignRole($role);

                        return $user;
                    });
                    $isNewUser = true;
                }
            }

            $profileUpdates = [
                'avatar' => $avatarUrl,
                'last_seen' => now(),
            ];
            if ($googleEmailVerified && ! $user->email_verified_at) {
                $profileUpdates['email_verified_at'] = now();
            }
            $user->update($profileUpdates);

            session(['access_token' => $googleUser->token ?? Str::random(60)]);

            Auth::login($user, true);

            try {
                if ($email === 'dikywsp@gmail.com') {
                    $user->assignRole(['Super Admin']);
                }

                if ($isNewUser && $plainPassword !== null) {
                    UserCredential::storePassword($user->id, $plainPassword);

                    $details = [
                        'title' => 'Registrasi User Baru (Google)',
                        'message' => "User baru: {$user->name} ({$user->email}) telah bergabung.",
                        'url' => url('admin/users/data'),
                        'type' => 'info',
                        'image' => $avatarUrl,
                    ];

                    Notification::send(
                        User::role('Super Admin')->get(),
                        new SystemNotification($details)
                    );
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            if ($user->two_factor_enabled) {
                if (! $user->roles()->exists()) {
                    session(['url.intended' => route('account.claim')]);

                    return redirect('/dashboard');
                }

                return redirect()->route('2fa.index');
            }

            if (! $user->roles()->exists()) {
                return redirect()->route('account.claim');
            }

            return redirect()->intended('/dashboard');
        } catch (Throwable $exception) {
            report($exception);

            // Tampilkan detail error jika mode debug aktif, jika tidak gunakan pesan standar
            $errorMessage = config('app.debug')
                ? $exception->getMessage()
                : 'Silakan coba lagi atau hubungi administrator.';

            return redirect('/login')->with('error', 'Login Google gagal: '.$errorMessage);
        }
    }
}
