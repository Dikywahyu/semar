<?php

namespace App\Http\Controllers\Capstone\Concerns;

use App\Models\Dosen\Dosen;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesCapstoneProfiles
{
    protected function canChooseCapstoneProfile(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin']) || $user?->can('academic-edit');
    }

    protected function capstoneStudent(Request $request): ?User
    {
        $user = auth()->user();

        if (! $this->canChooseCapstoneProfile()) {
            return $user?->hasAnyRole(['mahasiswa', 'Mahasiswa']) ? $user : null;
        }

        if ($request->filled('student_user_id')) {
            $request->session()->put('capstone.student_user_id', $request->integer('student_user_id'));
        }

        $studentId = $request->session()->get('capstone.student_user_id');

        return $studentId ? $this->capstoneStudents()->find($studentId) : null;
    }

    protected function capstoneDosen(Request $request): ?Dosen
    {
        $user = auth()->user();

        if ($this->canChooseCapstoneProfile()) {
            if ($request->filled('id_dosen')) {
                $request->session()->put('capstone.dosen_id', $request->input('id_dosen'));
            }

            $dosenId = $request->session()->get('capstone.dosen_id');

            return $dosenId ? Dosen::query()->where('aktif', true)->find($dosenId) : null;
        }

        if (! $user?->hasAnyRole(['dosen', 'Dosen', 'DosenLB', 'Guru Besar', 'dosen_pembimbing', 'dosen_penguji'])) {
            return null;
        }

        return Dosen::query()
            ->where('aktif', true)
            ->where(function ($query) use ($user) {
                $query->when(filled($user->email), fn ($builder) => $builder->where('email', $user->email))
                    ->when(filled($user->identifier_number), fn ($builder) => $builder->orWhere('nidn', $user->identifier_number));
            })
            ->first();
    }

    protected function capstoneStudents()
    {
        return User::role(['mahasiswa', 'Mahasiswa'])->orderBy('name');
    }
}
