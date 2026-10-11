<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AccountClaimController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->roles()->exists()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.account-claim');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->roles()->exists()) {
            return redirect()->intended('/dashboard');
        }

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $identifier = trim($validated['identifier']);

        $mahasiswa = MahasiswaRegistrasi::query()
            ->where('nim', $identifier)
            ->where('aktif', true)
            ->first();
        $dosens = Dosen::query()
            ->where(function ($query) use ($identifier) {
                $query->where('nidn', $identifier);
            })
            ->where('aktif', true)
            ->limit(2)
            ->get();

        $isAmbiguous = ($mahasiswa && $dosens->isNotEmpty()) || $dosens->count() > 1;
        if ($isAmbiguous || (! $mahasiswa && $dosens->isEmpty())) {
            throw ValidationException::withMessages([
                'identifier' => $isAmbiguous
                    ? 'Nomor identitas ditemukan pada lebih dari satu data. Hubungi administrator.'
                    : 'NIM, NIDN, atau NUPTK tidak ditemukan pada data aktif.',
            ]);
        }
        $dosen = $dosens->first();

        if (User::query()
            ->where('identifier_number', $identifier)
            ->where('id', '!=', $user->getKey())
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'identifier' => 'Data akademik ini sudah terhubung dengan akun lain. Hubungi administrator.',
            ]);
        }

        DB::transaction(function () use ($user, $mahasiswa, $dosen, $identifier, $validated) {
            if ($mahasiswa) {
                $name = $mahasiswa->nama_mahasiswa;
                $phone = $mahasiswa->telepon;
                $roleName = 'Mahasiswa';
                $positionTitle = 'Mahasiswa';
            } else {
                $name = trim(implode(' ', array_filter([
                    $dosen->gelar_depan,
                    $dosen->nama_dosen,
                    $dosen->gelar_belakang,
                ])));
                $phone = $dosen->telepon;
                $roleName = 'Dosen';
                $positionTitle = 'Dosen';
            }

            $user->update([
                'name' => filled($name) ? $name : $user->name,
                'identifier_number' => $identifier,
                'password' => $validated['password'],
                'phone' => $phone,
                'position_title' => $positionTitle,
                'institution' => 'Universitas Tribhuwana Tunggadewi',
            ]);
            $user->credential()->delete();

            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $user->assignRole($role);
        });

        activity()
            ->causedBy($user)
            ->log('User claimed academic account as ' . ($mahasiswa ? 'Mahasiswa' : 'Dosen'));

        return redirect('/dashboard')->with('success', 'Akun berhasil dihubungkan dengan data akademik.');
    }
}
