<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required|in:api,web'
        ]);

        return DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name
            ]);

            activity('iam-api')
                ->performedOn($role)
                ->causedBy(Auth::user())
                ->log("API: Membuat role baru {$role->name}");

            return response()->json(['status' => 'success', 'data' => $role], 201);
        });
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) return response()->json(['message' => 'Role not found'], 404);

        if (in_array($role->name, ['admin', 'super-admin'])) {
            return response()->json(['message' => 'Cannot delete protected role'], 403);
        }

        $role->delete();

        activity('iam-api')
            ->causedBy(Auth::user())
            ->log("API: Menghapus role {$role->name}");

        return response()->json(['status' => 'success', 'message' => 'Role deleted']);
    }
}
