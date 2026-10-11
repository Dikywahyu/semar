<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Laravel\Passport\Client;
use App\Models\PassportClient as Client; // Menggunakan model bawaan Passport
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use App\Models\User;

class PassportClientController extends Controller
{
    /**
     * Mengambil data untuk AJAX DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Tarik data berdasarkan blueprint migrasi asli Anda
            $activeClients = DB::table('oauth_clients')
                ->select(['id', 'name', 'redirect_uris', 'revoked', 'grant_types'])
                ->get();

            // Mapping agar properti object JavaScript DataTable tetap menerima nama 'redirect'
            $mappedClients = $activeClients->map(function ($client) {
                // Decode grant_types jika tersimpan dalam bentuk string JSON
                $grants = json_decode($client->grant_types, true) ?? [];

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'redirect' => $client->redirect_uris, // Diubah ke 'redirect' untuk konsistensi index.blade.php
                    'revoked' => $client->revoked,
                    'grant_types' => $grants
                ];
            });

            return response()->json(['data' => $mappedClients]);
        }

        return view('admin.api_clients.index');
    }
    
    /**
     * Membersihkan dan Mengoptimalkan Cache Sistem via UI Dashboard
     */
    public function clearSystemCache()
    {
        try {
            // Cukup jalankan satu perintah ini, karena sudah membersihkan views, config, dan route cache secara aman
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            
            // HAPUS ATAU BERI TANDA KOMENTAR (//) PADA 2 BARIS DI BAWAH INI:
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('clear-compiled');
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');

            if (function_exists('activity')) {
                activity('iam-system')
                    ->causedBy(Auth::user())
                    ->log("Membersihkan seluruh cache sistem IAM melalui tombol Dashboard Admin.");
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Seluruh cache sistem berhasil dibersihkan!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membersihkan cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan Aplikasi Client SSO Baru (Create)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'redirect' => 'required|url',
        ]);

        $plainSecret = Str::random(40);

        // Instansiasi model Client murni sesuai dengan blueprint migrasi Anda
        $client = new Client();
        
        // Mengisi kolom nullableMorphs('owner')
        $client->owner_type = \App\Models\User::class;
        $client->owner_id = Auth::id();
        
        $client->name = $request->name;
        $client->secret = $plainSecret;
        $client->provider = 'users';
        
        // Sesuai field migrasi Anda:
        $client->redirect_uris = $request->redirect;
        
        // Jenis grant standar untuk jembatan login SSO lempar halaman
        $client->grant_types = ['authorization_code', 'refresh_token'];
        
        $client->revoked = false;
        $client->save();
 
        // 1. PENYESUAIAN LOG: Menggunakan variabel $client yang valid & aman dari data truncation UUID
        if (function_exists('activity')) {
            activity('iam-passport')
                ->causedBy(Auth::user())
                ->withProperties([
                    'client_id'   => $client->id,
                    'client_name' => $client->name,
                    'redirect'    => $request->redirect
                ])
                ->log("Mendaftarkan Aplikasi SSO Baru: {$client->name} (UUID: {$client->id})");
        }
        
        // 2. NOTIFIKASI SYSTEM SINKRON
        $notificationData = [
            'title'   => 'Passport Baru Terdeteksi', 
            'message' => 'Aplikasi SSO "' . $request->name . '" telah berhasil ditambahkan ke sistem.', 
            'url'     => url('admin/api-clients'), // Diarahkan ke rute manajemen API Client yang tepat
            'image'   => Auth::user()->avatar ?? asset('dist/assets/images/profile/user-1.jpg'), 
            'type'    => 'success', 
        ];
        
        $admins = User::role('Super Admin')->get();
        
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new SystemNotification($notificationData));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aplikasi SSO berhasil didaftarkan.',
            'client_id' => $client->id,
            'client_secret' => $plainSecret
        ], 201);
    }
    
    /**
     * Regenerate Client Secret Key (Update Secret Only)
     */
    /**
     * Regenerate Client Secret Key (Update Secret Only)
     * Diselaraskan dengan model casting murni Laravel Passport 12
     */
    public function regenerateSecret($id)
    {
        // 1. Ambil data menggunakan model Client (bukan DB::table) agar fitur internal Passport aktif
        $client = \App\Models\PassportClient::find($id);
    
        if (!$client) {
            return response()->json(['message' => 'Aplikasi tidak ditemukan'], 404);
        }
    
        // 2. Generate plain secret baru sepanjang 40 karakter
        $newPlainSecret = \Illuminate\Support\Str::random(40);
    
        // 3. Simpan nilai plain text murni (Biarkan enkripsi otomatis diurus oleh useClientModel)
        $client->secret = $newPlainSecret;
        $client->save();
    
        // LOG KEAMANAN SYSTEM (Spatie Activity Log)
        if (function_exists('activity')) {
            activity('iam-passport')
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'client_id'   => $id,
                    'client_name' => $client->name
                ])
                ->log("Melakukan Regenerate Secret Key untuk aplikasi SSO: {$client->name}");
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Client Secret berhasil diperbarui!',
            'client_secret' => $newPlainSecret // Kirim plain key baru ke SweetAlert frontend
        ]);
    }

    /**
     * Mengambil Detail untuk Modal Edit (Read)
     */
    public function show($id)
    {
        $client = DB::table('oauth_clients')->where('id', $id)->first();
        
        if (!$client) {
            return response()->json(['message' => 'Aplikasi tidak ditemukan'], 404);
        }

        // Samakan properti redirect agar JavaScript SweetAlert pembaca value tidak bingung
        $client->redirect = $client->redirect_uris;

        return response()->json($client);
    }

    /**
     * Memperbarui Konfigurasi Client (Update)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'redirect' => 'required|url',
        ]);

        $updated = DB::table('oauth_clients')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'redirect_uris' => $request->redirect,
                'updated_at' => now()
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Gagal memperbarui atau data tidak ditemukan'], 404);
        }
        
        // 1. LOG AKTIVITAS: Catat riwayat pembaruan tanpa subject UUID agar tidak error
        if (function_exists('activity')) {
            activity('iam-passport')
                ->causedBy(Auth::user())
                ->withProperties([
                    'client_id'   => $id,
                    'client_name' => $request->name,
                    'redirect'    => $request->redirect
                ])
                ->log("Mengubah konfigurasi Aplikasi SSO: {$request->name}");
        }
        
        // 2. NOTIFIKASI SYSTEM
        $notificationData = [
            'title'   => 'Passport Diperbaharui', 
            'message' => 'Aplikasi SSO "' . $request->name . '" telah berhasil diperbaharui.', 
            'url'     => url('admin/api-clients'), 
            'image'   => Auth::user()->avatar ?? asset('dist/assets/images/profile/user-1.jpg'), 
            'type'    => 'info', 
        ];
        
        $admins = User::role('Super Admin')->get();
        
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new SystemNotification($notificationData));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi aplikasi SSO berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus Client (Delete)
     */
    public function destroy($id)
    {
        // Ambil nama client terlebih dahulu untuk keperluan pelaporan log/notifikasi sebelum datanya hilang
        $client = DB::table('oauth_clients')->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Aplikasi tidak ditemukan'], 404);
        }

        // Eksekusi hapus
        DB::table('oauth_clients')->where('id', $id)->delete();

        // 1. LOG AKTIVITAS
        if (function_exists('activity')) {
            activity('iam-passport')
                ->causedBy(Auth::user())
                ->withProperties([
                    'client_id'   => $id,
                    'client_name' => $client->name
                ])
                ->log("Menghapus Aplikasi SSO secara permanen: {$client->name}");
        }

        // 2. NOTIFIKASI SYSTEM HAPUS
        $notificationData = [
            'title'   => 'Passport Dihapus', 
            'message' => 'Aplikasi SSO "' . $client->name . '" telah dihapus permanen dari sistem.', 
            'url'     => url('admin/api-clients'), 
            'image'   => Auth::user()->avatar ?? asset('dist/assets/images/profile/user-1.jpg'), 
            'type'    => 'warning', 
        ];
        
        $admins = User::role('Super Admin')->get();
        
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new SystemNotification($notificationData));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aplikasi SSO berhasil dihapus secara permanen.'
        ]);
    }
}