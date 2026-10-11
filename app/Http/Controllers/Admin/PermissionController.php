<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use App\Models\User;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $permissions = Permission::all();
            return response()->json(['data' => $permissions]);
        }
        return view('admin.permissions.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('permissions', 'name')->where(fn($q) => $q->where('guard_name', $request->guard_name))
            ],
            'guard_name' => 'required|in:web,api'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        activity('iam-management')
            ->performedOn($permission)
            ->causedBy(Auth::user())
            ->log("Menambah permission: {$permission->name}");

        // 1. Definisikan data notifikasi dalam sebuah array
        $notificationData = [
            'title'    => 'permissions Baru Terdeteksi', // Akan mengisi key 'title'
            'message'  => 'Permission "' . $request->name . '" telah berhasil ditambahkan ke sistem.', // Mengisi 'message'
            'url'      => url('admin/permissions'), // Mengisi 'url' untuk link klik
            'image'     => Auth::user()->avatar,
            'type'     => 'success', // Mengisi 'type'
        ];

        // 2. Ambil target user (Super Admin)
        $admins = User::role('Super Admin')->get();

        // 3. Kirim dengan menyertakan array data di atas
        Notification::send($admins, new SystemNotification($notificationData));

        return response()->json(['status' => 'success', 'message' => 'Permission berhasil ditambahkan']);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Permission::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('permissions', 'name')
                    ->where(fn($q) => $q->where('guard_name', $request->guard_name))
                    ->ignore($id)
            ],
            'guard_name' => 'required|in:web,api'
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);


        $notificationData = [
            'title'   => 'Update Permission Terdeteksi',
            'message' => 'Permission "' . $permission->name . '" telah diperbarui oleh ' . Auth::user()->name,
            'url'     => url('admin/permissions'),
            'image'     => Auth::user()->avatar,
            'type'    => 'warning', // Gunakan warna kuning/warning untuk menandakan perubahan
        ];

        $admins =  User::role('Super Admin')->get();

        // Kirim notifikasi
        Notification::send($admins, new \App\Notifications\SystemNotification($notificationData));


        return response()->json(['status' => 'success', 'message' => 'Permission diperbarui']);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $name = $permission->name;
        $permission->delete();


        // 1. Definisikan data notifikasi dalam sebuah array
        $notificationData = [
            'title'    => 'Permission Dihapus ' . $name, // Akan mengisi key 'title'
            'message'  => 'Permission "' . $name . '" telah berhasil dihapus dari sistem.', // Mengisi 'message'
            'url'      => url('admin/permissions'), // Mengisi 'url' untuk link klik
            'image'     => Auth::user()->avatar,
            'type'     => 'danger', // Mengisi 'type'
        ];

        // 2. Ambil target user (Super Admin)
        $admins = User::role('Super Admin')->get();

        // 3. Kirim dengan menyertakan array data di atas
        Notification::send($admins, new SystemNotification($notificationData));


        return response()->json(['status' => 'success', 'message' => "Permission {$name} dihapus"]);
    }
}
