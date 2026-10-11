<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IamRoleSyncController extends Controller
{
    public function updateUserRoleFromSiakad(Request $request)
    {
        // 1. Ambil plain-text token secret dari header
        $pemicuToken = $request->header('X-SIAKAD-SECRET');

        if (!$pemicuToken) {
            return response()->json([
                'success' => false, 
                'message' => 'Missing X-SIAKAD-SECRET Header.'
            ], 400);
        }

        // 2. Verifikasi Client OAuth Passport
        $passportClients = DB::table('oauth_clients')
            ->where('revoked', false)
            ->get();

        $validClient = null;
        foreach ($passportClients as $client) {
            if (Hash::check($pemicuToken, $client->secret)) {
                $validClient = $client;
                break;
            }
        }

        if (!$validClient) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized Secret Key. Kredensial OAuth Passport tidak cocok.'
            ], 401);
        }

        // 3. Validasi payload (Tambahkan parameter 'action')
        $request->validate([
            'email'  => 'required|email',
            'role'   => 'required|string',
            'action' => 'nullable|string|in:assign,revoke' // 👈 TAMBAHAN ACTION
        ]);

        // 4. Cari user target di IAM berdasarkan email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => "User dengan email {$request->email} tidak ditemukan di database IAM."
            ], 404);
        }

        try {
            $roleBaru = $request->role; // Contoh: 'Mahasiswa' / 'Dosen' / 'Tendik'
            $action   = $request->input('action', 'assign'); // Default: 'assign'

            // Ambil data role resmi dari tabel roles Spatie IAM
            $roleSpatie = DB::table('roles')->where('name', $roleBaru)->first();

            if (!$roleSpatie) {
                return response()->json([
                    'success' => false,
                    'message' => "Role '{$roleBaru}' belum terdaftar di tabel roles Spatie IAM."
                ], 422);
            }

            // 🟢 JIKA ACTION = 'revoke' (MENCABUT / MENGHAPUS ROLE)
            if ($action === 'revoke') {
                DB::table('model_has_roles')
                    ->where('role_id', $roleSpatie->id)
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->delete();

                // Bersihkan cache Spatie
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                return response()->json([
                    'success' => true,
                    'message' => "Aplikasi [{$validClient->name}] sukses MENCABUT role {$roleBaru} dari user {$request->email}."
                ], 200);
            }

            // 🔵 JIKA ACTION = 'assign' (MENAMBAH ROLE / DEFAULT)
            $exists = DB::table('model_has_roles')
                ->where('role_id', $roleSpatie->id)
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $roleSpatie->id,
                    'model_type' => get_class($user), 
                    'model_id'   => $user->id
                ]);
            }

            // Bersihkan cache Spatie
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'success' => true,
                'message' => "Aplikasi [{$validClient->name}] sukses menyinkronkan role {$roleBaru} ke user {$request->email}."
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengamankan data role ke database IAM: ' . $e->getMessage()
            ], 500);
        }
    }
}