<?php

namespace App\Services;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AcademicAccountGenerator
{
    public function generateStudentAccounts(int $offset = 0, int $limit = 10): array
    {
        $baseQuery = MahasiswaRegistrasi::query()->where('aktif', true)->orderBy('nim');
        $total = (clone $baseQuery)->count();
        $records = $baseQuery->offset(max($offset, 0))->limit(min(max($limit, 1), 25))->get();
        $result = $this->generate($records, 'Mahasiswa', fn (MahasiswaRegistrasi $student): array => [
            'identifier' => $student->nim, 'name' => $student->nama_mahasiswa, 'email' => $student->email,
            'phone' => $student->telepon, 'position_title' => 'Mahasiswa',
        ]);
        return [...$result, 'total' => $total, 'processed' => min($offset + $records->count(), $total), 'next_offset' => $offset + $records->count(), 'finished' => $offset + $records->count() >= $total];
    }
    public function generateLecturerAccounts(): array
    {
        return $this->generate(
            Dosen::query()->where('aktif', true)->orderBy('nidn')->get(),
            'Dosen',
            fn (Dosen $lecturer): array => [
                'identifier' => $lecturer->nidn,
                'name' => trim(implode(' ', array_filter([
                    $lecturer->gelar_depan,
                    $lecturer->nama_dosen,
                    $lecturer->gelar_belakang,
                ]))),
                'email' => $lecturer->email,
                'phone' => $lecturer->telepon,
                'position_title' => 'Dosen',
            ],
        );
    }

    private function generate(Collection $records, string $roleName, \Closure $accountDetails): array
    {
        @set_time_limit(0);

        return DB::transaction(function () use ($records, $roleName, $accountDetails): array {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $details = $records->map(fn ($record) => $accountDetails($record));
            $identifiers = $details->pluck('identifier')->filter()->map(fn ($value) => trim((string) $value))->unique()->values();
            $emails = $details->pluck('email')->filter()->map(fn ($value) => Str::lower(trim((string) $value)))->unique()->values();
            $existing = User::query()->whereIn('identifier_number', $identifiers)->orWhereIn('email', $emails)->get(['identifier_number', 'email']);
            $usedIdentifiers = $existing->pluck('identifier_number')->filter()->flip()->all();
            $usedEmails = $existing->pluck('email')->map(fn ($email) => Str::lower($email))->flip()->all();
            $rows = []; $skipped = []; $now = now();

            foreach ($details as $detail) {
                $identifier = trim((string) ($detail['identifier'] ?? ''));
                if ($identifier === '') { $skipped[] = ['identifier' => '-', 'reason' => 'NIM/NIDN belum tersedia.']; continue; }
                $email = filled($detail['email'] ?? null) ? Str::lower(trim((string) $detail['email'])) : 'account-'.hash('sha256', Str::lower($identifier)).'@semar.invalid';
                if (isset($usedIdentifiers[$identifier])) { $skipped[] = ['identifier' => $identifier, 'reason' => 'Akun dengan NIM/NIDN ini sudah ada.']; continue; }
                if (isset($usedEmails[$email])) { $skipped[] = ['identifier' => $identifier, 'reason' => 'Email sudah digunakan akun lain.']; continue; }
                $rows[] = ['name' => $detail['name'], 'email' => $email, 'email_verified_at' => $now, 'password' => Hash::make($identifier), 'identifier_number' => $identifier, 'phone' => $detail['phone'], 'position_title' => $detail['position_title'], 'institution' => 'Universitas Tribhuwana Tunggadewi', 'role' => 'participant', 'created_at' => $now, 'updated_at' => $now];
                $usedIdentifiers[$identifier] = true; $usedEmails[$email] = true;
            }
            foreach (array_chunk($rows, 250) as $chunk) User::query()->insert($chunk);
            $createdUsers = User::query()->whereIn('identifier_number', collect($rows)->pluck('identifier_number'))->get(['id']);
            foreach (array_chunk($createdUsers->map(fn ($user) => ['role_id' => $role->id, 'model_type' => User::class, 'model_id' => $user->id])->all(), 500) as $chunk) { if ($chunk) DB::table('model_has_roles')->insert($chunk); }
            return ['created_count' => count($rows), 'skipped_count' => count($skipped), 'skipped' => $skipped];
        });
    }
}