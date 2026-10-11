<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // <-- Pastikan model User di-import

class UpdateUserLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // AMAN: Ambil instansi model User asli berdasarkan ID yang login
            User::where('id', Auth::id())->update([
                'last_seen' => now()
            ]);
        }

        return $next($request);
    }
}