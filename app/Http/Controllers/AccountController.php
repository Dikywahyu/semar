<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity; // <-- Import model jika dibutuhkan secara eksplisit

class AccountController extends Controller
{
    /**
     * Memperbarui Nama Pengguna dan Mengunggah Berkas Foto Profil Baru.
     */
    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $request->validate([
            'name'   => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $oldName = $user->name;
        $data = ['name' => $request->name];
        $newAvatarUrl = null;
        $isAvatarChanged = false;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

            $file->move(public_path('dist/assets/images/profile'), $filename);

            $data['avatar'] = $filename;
            $newAvatarUrl = asset('dist/assets/images/profile/' . $filename);
            $isAvatarChanged = true;
        }

        // Jalankan update
        $user->update($data);

        // --- SPATIE ACTIVITY LOG: UPDATE PROFILE ---
        if ($oldName !== $request->name && $isAvatarChanged) {
            $logMessage = "Mengupdate profil: Mengubah nama dari '{$oldName}' menjadi '{$request->name}' dan memperbarui foto profil";
        } elseif ($oldName !== $request->name) {
            $logMessage = "Mengupdate profil: Mengubah nama dari '{$oldName}' menjadi '{$request->name}'";
        } else {
            $logMessage = "Mengupdate profil: Memperbarui foto profil";
        }

        activity()
            ->performedOn($user)
            ->causedBy($user) // Ditindak oleh user itu sendiri
            ->withProperties(['old_name' => $oldName, 'new_name' => $request->name]) // Simpan data kustom jika perlu
            ->log($logMessage);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Profil Anda berhasil diperbarui!',
            'new_avatar' => $newAvatarUrl
        ]);
    }

    /**
     * Memvalidasi Password Lama dan Mengubah ke Password yang Baru.
     */
    public function updatePassword(Request $request)
    {
        // Ambil instansi model User asli
        $user = User::findOrFail(Auth::id());

        // Validasi inputan sandi
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        // Memeriksa kesesuaian sandi lama di database dengan Bcrypt laravel
        if (!Hash::check($request->current_password, $user->password)) {

            // --- SPATIE ACTIVITY LOG: GAGAL VERIFIKASI ---
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log("Gagal mengubah password: Percobaan gagal, password lama tidak cocok untuk akun (Email: {$user->email})");

            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama yang Anda masukkan tidak sesuai.'
            ], 422);
        }

        // 1. Eksekusi update password baru di tabel `users` (Hashed)
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // 2. SINKRONISASI OTOMATIS: Update atau simpan password baru ke tabel `user_credentials`
        // Menggunakan variabel $request->password (plain text) sesuai blueprint storePassword Bapak
        \App\Models\UserCredential::storePassword($user->id, $request->password);

        // --- SPATIE ACTIVITY LOG: SUKSES GANTI PASSWORD & CREDENTIALS ---
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log("Mengubah keamanan akun: Berhasil memperbarui password utama dan memperbarui entri pada user_credentials (Email: {$user->email})");

        return response()->json([
            'status'  => 'success',
            'message' => 'Password akun Anda berhasil diperbarui!'
        ]);
    }
}
