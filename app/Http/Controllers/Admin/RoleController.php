<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use Illuminate\Validation\Rule;
use App\Models\User;

class RoleController extends Controller
{
    /**
     * Menampilkan halaman utama & mengambil data via AJAX
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Eager loading count permissions untuk performa database yang lebih baik
            $roles = Role::withCount('permissions')->get();
            return response()->json(['data' => $roles]);
        }

        return view('admin.roles.index');
    }

    /**
     * Menyimpan Role baru dengan validasi composite (name + guard)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Validasi agar nama boleh sama asalkan guard_name berbeda
                Rule::unique('roles', 'name')->where(fn($query) => $query->where('guard_name', $request->guard_name))
            ],
            'guard_name' => 'required|in:web,api'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        // Mencatat log aktivitas ke sistem pusat
        activity('iam-management')
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->log("Menambahkan role baru: {$role->name} ({$role->guard_name})");

        // 1. Definisikan data notifikasi dalam sebuah array
        $notificationData = [
            'title'    => 'Role Baru Terdeteksi', // Akan mengisi key 'title'
            'message'  => 'Role "' . $request->name . '" telah berhasil ditambahkan ke sistem.', // Mengisi 'message'
            'url'      => url('admin/roles'), // Mengisi 'url' untuk link klik
            'image'     => Auth::user()->avatar,
            'type'     => 'success', // Mengisi 'type'
        ];

        // 2. Ambil target user (Super Admin)
        $admins = User::role('Super Admin')->get();

        // 3. Kirim dengan menyertakan array data di atas
        Notification::send($admins, new SystemNotification($notificationData));

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil ditambahkan'
        ]);
    }

    /**
     * Mengambil data satu role untuk ditampilkan di Modal Edit
     */
    public function show($id)
    {
        $role = Role::find($id);
        $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', $role->guard_name)->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        if (!$role) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'role' => $role,
                'all_permissions' => $allPermissions,
                'role_permissions' => $rolePermissions
            ]
        ]);
    }

    /**
     * Memperbarui data role (Composite Unique Validation)
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Pastikan unik pada guard yang sama, abaikan ID milik role ini sendiri
                Rule::unique('roles', 'name')
                    ->where(fn($query) => $query->where('guard_name', $request->guard_name))
                    ->ignore($id)
            ],
            'guard_name' => 'required|in:web,api'
        ]);

        $role->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        $role->syncPermissions($request->permissions);

        activity('iam-management')
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->log("Memperbarui role: {$role->name} ke guard {$role->guard_name}");

        $notificationData = [
            'title'   => 'Update Role Terdeteksi',
            'message' => 'Role "' . $role->name . '" telah diperbarui oleh ' . Auth::user()->name,
            'url'     => url('admin/roles'),
            'image'     => Auth::user()->avatar,
            'type'    => 'warning', // Gunakan warna kuning/warning untuk menandakan perubahan
        ];

        $admins =  User::role('Super Admin')->get();

        // Kirim notifikasi
        Notification::send($admins, new \App\Notifications\SystemNotification($notificationData));

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diperbarui'
        ]);
    }

    /**
     * Menghapus role (dengan proteksi role sistem)
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Proteksi role krusial agar tidak terhapus
        if (in_array($role->name, ['admin', 'super-admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role utama sistem tidak boleh dihapus!'
            ], 403);
        }

        $roleName = $role->name;
        $role->delete();

        activity('iam-management')
            ->causedBy(Auth::user())
            ->log("Menghapus role: {$roleName}");

        // 1. Definisikan data notifikasi dalam sebuah array
        $notificationData = [
            'title'    => 'Role Dihapus ' . $roleName, // Akan mengisi key 'title'
            'message'  => 'Role "' . $roleName . '" telah berhasil dihapus dari sistem.', // Mengisi 'message'
            'url'      => url('admin/roles'), // Mengisi 'url' untuk link klik
            'image'     => Auth::user()->avatar,
            'type'     => 'danger', // Mengisi 'type'
        ];

        // 2. Ambil target user (Super Admin)
        $admins = User::role('Super Admin')->get();

        // 3. Kirim dengan menyertakan array data di atas
        Notification::send($admins, new SystemNotification($notificationData));

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dihapus'
        ]);
    }
}
