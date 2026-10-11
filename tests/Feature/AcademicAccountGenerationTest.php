<?php

namespace Tests\Feature;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicAccountGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_login_accounts_for_active_students(): void
    {
        $this->actingAs($this->admin());
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260001',
            'nama_mahasiswa' => 'Mahasiswa Baru',
            'email' => 'mahasiswa@example.test',
        ]);
        MahasiswaRegistrasi::create([
            'nim' => '20260002',
            'nama_mahasiswa' => 'Mahasiswa Nonaktif',
            'aktif' => false,
        ]);

        $this->postJson(route('master.mahasiswas.generate-users'))
            ->assertOk()
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('skipped_count', 0);

        $user = User::where('identifier_number', $student->nim)->firstOrFail();
        $this->assertSame('mahasiswa@example.test', $user->email);
        $this->assertTrue(Hash::check($student->nim, $user->password));
        $this->assertTrue($user->hasRole('Mahasiswa'));

        auth()->logout();
        $this->post('/login', ['username' => $student->email, 'password' => $student->nim])
            ->assertRedirect('/dashboard');

        $this->post(route('logout'))->assertRedirect();
        $this->post('/login', ['username' => $student->nim, 'password' => $student->nim])
            ->assertRedirect('/dashboard');
    }

    public function test_admin_can_generate_lecturer_account_without_email_using_nidn(): void
    {
        $this->actingAs($this->admin());
        $lecturer = Dosen::create([
            'nidn' => '0011223344',
            'nama_dosen' => 'Dosen Baru',
        ]);

        $this->postJson(route('master.dosens.generate-users'))
            ->assertOk()
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('skipped_count', 0);

        $user = User::where('identifier_number', $lecturer->nidn)->firstOrFail();
        $this->assertSame('account-'.hash('sha256', $lecturer->nidn).'@semar.invalid', $user->email);
        $this->assertTrue(Hash::check($lecturer->nidn, $user->password));
        $this->assertTrue($user->hasRole('Dosen'));
    }

    public function test_account_generation_skips_existing_identifier_and_email_conflicts(): void
    {
        $this->actingAs($this->admin());
        User::factory()->create(['identifier_number' => '20260003']);
        User::factory()->create(['email' => 'used@example.test']);
        MahasiswaRegistrasi::create([
            'nim' => '20260003',
            'nama_mahasiswa' => 'Akun Sudah Ada',
        ]);
        MahasiswaRegistrasi::create([
            'nim' => '20260004',
            'nama_mahasiswa' => 'Email Bentrok',
            'email' => 'used@example.test',
        ]);

        $this->postJson(route('master.mahasiswas.generate-users'))
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('skipped_count', 2);

        $this->assertDatabaseCount('users', 3);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]));

        return $user;
    }
}
