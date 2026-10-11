<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Permission::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required|in:api,web'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        activity('iam-api')
            ->performedOn($permission)
            ->causedBy(Auth::user())
            ->log("API: Menambah permission {$permission->name}");

        return response()->json(['status' => 'success', 'data' => $permission], 201);
    }
}
