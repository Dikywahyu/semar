<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthClientController,
    RoleController,
    PermissionController,
    IamRoleSyncController,
};

/*
|--------------------------------------------------------------------------
| API Routes - IAM Server (Passport Integrated)
|--------------------------------------------------------------------------
*/

// 1. Route Login khusus Client / API Non-Stateful (Jika masih dibutuhkan)
Route::middleware(['check.api.host'])->group(function () {
    Route::post('/v1/login-client', [AuthClientController::class, 'login']);
});



// 2. Route Jembatan SSO Terproteksi Token Passport (Stateless OAuth2)
// KUNCI: Guard diganti dari 'auth:sanctum' menjadi 'auth:api' (Milik Passport)
Route::middleware('auth:api')->group(function () {

    /**
     * Endpoint Resmi Profil User
     * Ini yang akan ditembak oleh Web Siakad 
     */
    Route::get('/user-profile', function (Request $request) {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'username'    => $user->username,
                'avatar'      => $user->avatar ?? asset('dist/assets/images/profile/user-1.jpg'),
                // Mengambil nama role & permission dari Spatie unik tanpa duplikasi
                'roles'       => $user->getRoleNames()->unique()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    });
    
    Route::get('/user', function (Request $request) {
         $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'username'    => $user->username,
                'avatar'      => $user->avatar ?? asset('dist/assets/images/profile/user-1.jpg'),
                // Mengambil nama role & permission dari Spatie unik tanpa duplikasi
                'roles'       => $user->getRoleNames()->unique()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    });

    /**
     * Endpoint Single Logout (SLO) SSO
     */
    Route::post('/v1/logout-client', [AuthClientController::class, 'logout']);
    
    /** 
     * Endpoint untuk Mengupdate / Mengklaim Role Baru dari SIAKAD
     */
    Route::post('/update-claimed-role', function (Request $request) {
        $request->validate([
            'role' => 'required|string|in:Mahasiswa,Dosen,Tendik,Karyawan'
        ]);

        $user = $request->user();
        $roleBaru = $request->input('role');

        // Sinkronisasi Role menggunakan Spatie Permission
        $user->syncRoles([$roleBaru]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diperbarui di IAM Pusat.',
            'data' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'username'    => $user->username,
                'avatar'      => $user->avatar ?? asset('dist/assets/images/profile/user-1.jpg'),
                'roles'       => $user->getRoleNames()->unique()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    });
    
});


// Rute Sinkronisasi antar-server terproteksi Secret Header Token
Route::post('/v1/sync-role-siakad', [IamRoleSyncController::class, 'updateUserRoleFromSiakad']);

/**
 * Endpoint untuk Mengupdate / Mengklaim Role Baru dari SIAKAD
 * Menggunakan middleware 'auth:api' atau 'auth:sanctum' sesuai passport IAM Bapak
 */
Route::middleware('auth:api')->post('/update-claimed-role', function (Request $request) {
    // 1. Validasi kiriman data dari SIAKAD
    $request->validate([
        'role' => 'required|string'
    ]);

    $user = $request->user();
    $roleBaru = $request->input('role');

    // 2. Sinkronisasi Role di IAM menggunakan Spatie (Menimpa role lama dengan yang baru diklaim)
    // Jika rolenya adalah 'Mahasiswa', maka sistem IAM pusat akan mengupdate hak aksesnya
    if (in_array($roleBaru, ['Mahasiswa', 'Dosen', 'Karyawan'])) {
        $user->syncRoles([$roleBaru]);
    }

    // 3. Kembalikan response sukses beserta struktur data terbaru agar SIAKAD bisa langsung menimpa Session-nya
    return response()->json([
        'status' => 'success',
        'message' => 'Role berhasil diperbarui di IAM Pusat.',
        'data' => [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'username'    => $user->username,
            'avatar'      => $user->avatar ?? asset('dist/assets/images/profile/user-1.jpg'),
            'roles'       => $user->getRoleNames()->unique()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
        ]
    ], 200, [], JSON_UNESCAPED_SLASHES);
});