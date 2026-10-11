<?php

namespace App\Services;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AcademicAccountGenerator
{
    public function generateStudentAccounts(): array
    {
        return $this->generate(
            MahasiswaRegistrasi::query()->where('aktif', true)->orderBy('nim')->get(),
            'Mahasiswa',
            fn (MahasiswaRegistrasi $student): array => [
                'identifier' => $student->nim,
                'name' => $student->nama_mahasiswa,
                'email' => $student->email,
                'phone' => $student->telepon,
                'position_title' => 'Mahasiswa',
            ],
        );
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
        $result = DB::transaction(function () use ($records, $roleName, $accountDetails): array {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $created = 0;
            $skipped = [];

            foreach ($records as $record) {
                $details = $accountDetails($record);
                $identifier = trim((string) $details['identifier']);

                if ($identifier === '') {
                    $skipped[] = ['identifier' => '—', 'reason' => 'NIM/NIDN belum tersedia.'];

                    continue;
                }

                if (User::query()->where('identifier_number', $identifier)->exists()) {
                    $skipped[] = ['identifier' => $identifier, 'reason' => 'Akun dengan NIM/NIDN ini sudah ada.'];

                    continue;
                }

                $email = filled($details['email'])
                    ? Str::lower(trim((string) $details['email']))
                    : 'account-'.hash('sha256', Str::lower($identifier)).'@semar.invalid';

                if (User::query()->whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists()) {
                    $skipped[] = ['identifier' => $identifier, 'reason' => 'Email sudah digunakan akun lain.'];

                    continue;
                }

                $user = User::create([
                    'name' => $details['name'],
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => $identifier,
                    'identifier_number' => $identifier,
                    'phone' => $details['phone'],
                    'position_title' => $details['position_title'],
                    'institution' => 'Universitas Tribhuwana Tunggadewi',
                    'role' => 'participant',
                ]);
                $user->assignRole($role);
                $created++;
            }

            return [
                'created_count' => $created,
                'skipped_count' => count($skipped),
                'skipped' => $skipped,
            ];
        });

        return $result;
    }
}
