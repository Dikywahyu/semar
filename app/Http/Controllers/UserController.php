<?php

namespace App\Http\Controllers;

use App\Models\UserCredential;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function getPermissions($hashid)
    {
        $user = User::findByHashid($hashid);

        return response()->json([
            'user_permissions' => $user->getPermissionNames(), // Izin khusus individu
            'all_permissions' => \Spatie\Permission\Models\Permission::all()->unique('name')->values()
        ]);
    }
    
    public function toggleSecured()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
    
        if ($user->two_factor_enabled) {
            // JIKA AKTIF: Langsung matikan tanpa hapus secret key lama
            $user->update([
                'two_factor_enabled' => 0
            ]);
            
            session()->forget('google2fa.auth_passed');
    
            return response()->json([
                'status'             => 'success',
                'two_factor_enabled' => 0,
                'message'            => 'Dua Faktor Autentikasi (2FA) berhasil dinonaktifkan.'
            ]);
        } else {
            // JIKA NONAKTIF: Cek apakah data google2fa_secret sudah ada di database
            if (!empty($user->google2fa_secret)) {
                // Jika Kunci ada, LANGSUNG AKTIFKAN lewat belakang (AJAX)
                $user->update([
                    'two_factor_enabled' => 1
                ]);
                
                session(['google2fa.auth_passed' => true]);
    
                return response()->json([
                    'status'             => 'success',
                    'two_factor_enabled' => 1,
                    'message'            => '2FA Berhasil diaktifkan kembali secara instan!'
                ]);
            }
    
            // JIKA KUNCI BELUM ADA: Beritahu AJAX untuk melakukan redirect ke halaman setup
            return response()->json([
                'status'  => 'redirect',
                'url'     => route('2fa.setup'),
                'message' => 'Kunci Rahasia belum terdaftar. Anda akan diarahkan ke halaman Setup QR Code.'
            ]);
        }
    }

    public function getUserLogs($hashid)
    {
        $user = User::findByHashid($hashid); // Menggunakan Trait Hashidable

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Mengambil log aktivitas di mana user ini adalah pelakunya (causer)
        $logs = \Spatie\Activitylog\Models\Activity::where('causer_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'created_at'  => $log->created_at->format('d/m/Y H:i'),
                    'description' => strtoupper($log->description),
                    'properties'  => $this->formatLogProperties($log),
                ];
            });

        return response()->json(['logs' => $logs]);
    }

    /**
     * Helper untuk merapikan tampilan JSON properties
     */
    private function formatLogProperties($log)
    {
        $attr = $log->getExtraProperty('attributes');
        if (!$attr) return $log->description;

        // Jika ada perubahan data, tampilkan dalam bentuk list ringkas
        $output = '<ul class="list-unstyled mb-0" style="font-size: 11px;">';
        foreach ($attr as $key => $value) {
            $output .= "<li><strong>{$key}:</strong> " . (is_array($value) ? json_encode($value) : $value) . "</li>";
        }
        $output .= '</ul>';
        return $output;
    }

    public function updatePermissions(Request $request, $hashid)
    {
        $user = User::findByHashid($hashid);

        // Default ke array kosong jika tidak ada yang dipilih
        $permissions = $request->input('permissions', []);

        $allPermissionIds = \Spatie\Permission\Models\Permission::whereIn('name', $permissions)
            ->whereIn('guard_name', ['web', 'api'])
            ->pluck('id');

        $user->permissions()->sync($allPermissionIds);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Mengubah izin khusus user {$user->name} menjadi: " . (empty($permissions) ? 'Tanpa Izin Khusus' : implode(', ', $permissions)));

        return response()->json(['status' => 'success']);
    }

    // Mendapatkan data role untuk modal
    public function getRoles($hashid)
    {
        // Menggunakan helper findByHashid dari Trait Hashidable
        $user = User::findByHashid($hashid);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'user_roles' => $user->getRoleNames(),
            // Ambil semua role lalu filter unik berdasarkan nama menggunakan Collection
            'all_roles' => \Spatie\Permission\Models\Role::all(['id', 'name', 'guard_name'])
                ->unique('name')
                ->values()
        ]);
    }

    // Menyimpan perubahan role
    public function updateRoles(Request $request, $hashid)
    {
        $user = User::findByHashid($hashid);

        // Pastikan $roles adalah array, jika tidak ada centang maka jadi array kosong []
        $roles = $request->input('roles', []);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Ambil ID role yang valid dari database
        $allRoleIds = \Spatie\Permission\Models\Role::whereIn('name', $roles)
            ->whereIn('guard_name', ['web', 'api'])
            ->pluck('id');

        // Jika $roles kosong, sync([]) akan menghapus semua role user tersebut
        $user->roles()->sync($allRoleIds);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Mengubah role user {$user->name} menjadi: " . implode(', ', $request->roles));

        return response()->json(['status' => 'success']);
    }

    // Di UserController.php
    public function showCredential($hashid)
    {
        // Hanya Super Admin yang boleh akses ini
        if (!auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findByHashid($hashid);

        return response()->json([
            'plain_password' => $user->credential ? $user->credential->getPlainPassword() : 'Tidak ada data',

        ]);
    }

    // Mengambil semua data user dalam format JSON
    public function index()
    {
        // Gunakan eager loading 'roles' agar query database ringan
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'hashid'    => $user->hashid, 
                'name'      => $user->name,
                'email'     => $user->email,
                
                // KUNCI UTAMA: Ambil nama role, lalu bersihkan duplikat akibat perbedaan Guard (web/api)
                'role'      => $user->getRoleNames()->unique()->values()->toArray(), 
                
                'avatar'    => $user->avatar,
                'is_online' => $user->last_seen && $user->last_seen->diffInMinutes(now()) < 5,
                'last_seen' => $user->last_seen
                    ? ($user->last_seen->diffInSeconds(now()) < 5 ? 'Baru saja' : $user->last_seen->diffForHumans())
                    : 'Belum Pernah Login',
            ];
        });
    
        return response()->json(['data' => $users]);
    }

    public function getAccessKey($hashid)
    {
        // Mencari user berdasarkan hashid menggunakan Trait
        $user = User::findByHashid($hashid);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Mengambil data dari tabel terpisah (UserCredential)
        $plainPassword = $user->credential ? $user->credential->getPlainPassword() : 'Belum diatur';

        return response()->json([
            'status' => 'success',
            'user_name' => $user->name,
            'access_key' => $plainPassword
        ]);
    }

    public function resetPassword(Request $request, $hashid)
    {
        $user = User::findByHashid($hashid)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        $newPlainPassword = Str::random(18);

        $user->update(['password' => Hash::make($newPlainPassword)]);

        UserCredential::storePassword($user->id, $newPlainPassword);

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'ip' => $request->ip(),
                'browser' => $request->header('User-Agent')
            ])
            ->log("Mereset password untuk user: {$user->name}");

        return response()->json([
            'status' => 'success',
            'message' => "Password {$user->name} berhasil direset",
            'new_password' => $newPlainPassword // Kirim password ke AJAX untuk ditampilkan di modal/alert
        ]);
    }
}
