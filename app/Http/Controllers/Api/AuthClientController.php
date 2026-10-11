<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client; // Gunakan model resmi Passport sesuai blueprint migrasi

class AuthClientController extends Controller
{
    /**
     * Handle Login khusus Client - Menggunakan Engine Passport (Stateless)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Validasi User dan Password lokal
        if (!$user || !Hash::check($request->password, $user->password)) {
            if (function_exists('activity')) {
                activity('auth-api')
                    ->causedBy($user ?? null)
                    ->withProperties([
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'email_attempt' => $request->email
                    ])
                    ->log("Percobaan login API gagal menggunakan email: {$request->email}");
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Kredensial yang diberikan salah.'
            ], 401);
        }

        // 2. Ambil data client Passport dari header X-Client-ID
        $clientId = $request->header('X-Client-ID');
        $client = Client::find($clientId);
        
        $clientName = $client ? $client->name : 'Unknown Client';
        $clientHost = $client ? $client->redirect_uris : 'Unknown Host';

        // 3. Bersihkan Token Lama & Buat Token Baru Berbasis Passport
        // Menggunakan metode tokens() bawaan HasApiTokens milik Passport
        $user->tokens()->where('client_id', $clientId)->delete(); 

        // Generate personal access token khusus untuk internal request stateless client ini
        $tokenResult = $user->createToken('access_token');
        $token = $tokenResult->accessToken;

        // 4. Catat Log Aktivitas Kampus secara Aman (Tanpa subject UUID langsung)
        if (function_exists('activity')) {
            activity('auth-api')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'client_id' => $clientId,
                    'host' => $request->getHost(),
                    'allowed_host' => $clientHost,
                    'user_agent' => $request->userAgent()
                ])
                ->log("User {$user->email} login via API: {$clientName} ({$clientHost})");
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login Berhasil',
            'data' => [
                'token' => $token, // Token enkripsi Passport JWT murni
                'user'  => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'roles'       => $user->getRoleNames()->unique()->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
                ]
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Handle Logout khusus Client - Mencabut Token Passport Aktif
     */
    public function logout(Request $request)
    {
        try {
            // Ambil token Passport yang sedang melekat pada request user saat ini
            $token = $request->user()->token();
            
            // Eksekusi pencabutan hak akses token
            $token->revoke();

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil logout dari aplikasi client.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mencabut token sesi: ' . $e->getMessage()
            ], 500);
        }
    }
}