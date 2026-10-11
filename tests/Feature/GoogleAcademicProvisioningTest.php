<?php

namespace Tests\Feature;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAcademicProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_creates_a_student_account_from_active_master_data(): void
    {
        MahasiswaRegistrasi::create([
            'nim' => '20260001',
            'nama_mahasiswa' => 'Mahasiswa Terdaftar',
            'email' => 'mahasiswa@example.test',
            'telepon' => '081234567890',
        ]);
        $this->mockGoogleUser('MAHASISWA@example.test', 'Nama Google');

        $this->get('/auth/google/callback')
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'mahasiswa@example.test')->firstOrFail();
        $this->assertSame('Mahasiswa Terdaftar', $user->name);
        $this->assertSame('20260001', $user->identifier_number);
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame('Mahasiswa', $user->position_title);
        $this->assertSame('participant', $user->role);
        $this->assertTrue($user->hasRole('Mahasiswa'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('user_credentials', ['user_id' => $user->id]);
    }

    public function test_google_login_creates_an_unclaimed_account_when_email_is_not_in_master_data(): void
    {
        $this->mockGoogleUser('external@example.test', 'Pengguna Luar');

        $this->get('/auth/google/callback')
            ->assertRedirect('/auth/account/claim');

        $user = User::where('email', 'external@example.test')->firstOrFail();
        $this->assertFalse($user->roles()->exists());
        $this->assertNull($user->identifier_number);
    }

    public function test_google_user_can_claim_a_student_account_using_nim(): void
    {
        $mahasiswa = MahasiswaRegistrasi::create([
            'nim' => '20260002',
            'nama_mahasiswa' => 'Mahasiswa Klaim',
            'email' => null,
            'telepon' => '081234567892',
        ]);
        $user = User::factory()->create(['email' => 'google@example.test']);

        $this->actingAs($user)
            ->get('/auth/account/claim')
            ->assertOk()
            ->assertViewIs('auth.account-claim');

        $this->actingAs($user)
            ->post('/auth/account/claim', [
                'identifier' => $mahasiswa->nim,
                'password' => 'a-strong-password',
                'password_confirmation' => 'a-strong-password',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame($mahasiswa->nim, $user->identifier_number);
        $this->assertSame('Mahasiswa Klaim', $user->name);
        $this->assertTrue($user->hasRole('Mahasiswa'));
        $this->assertTrue(password_verify('a-strong-password', $user->password));
        $this->assertDatabaseMissing('user_credentials', ['user_id' => $user->id]);

        $this->post('/login', [
            'username' => $mahasiswa->nim,
            'password' => 'a-strong-password',
        ])->assertRedirect('/dashboard');
    }

    public function test_google_user_can_claim_a_lecturer_account_using_nuptk(): void
    {
        Dosen::create([
            'nidn' => '0011223345',
            'nuptk' => '1234567890123456',
            'nama_dosen' => 'Dosen Klaim',
        ]);
        $user = User::factory()->create(['email' => 'google-dosen@example.test']);

        $this->actingAs($user)
            ->post('/auth/account/claim', [
                'identifier' => '1234567890123456',
                'password' => 'a-strong-password',
                'password_confirmation' => 'a-strong-password',
            ])
            ->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('1234567890123456', $user->identifier_number);
        $this->assertSame('Dosen Klaim', $user->name);
        $this->assertTrue($user->hasRole('Dosen'));
    }

    public function test_academic_record_cannot_be_claimed_by_a_second_google_user(): void
    {
        $mahasiswa = MahasiswaRegistrasi::create([
            'nim' => '20260003',
            'nama_mahasiswa' => 'Mahasiswa Sudah Terhubung',
        ]);
        $existingUser = User::factory()->create(['identifier_number' => $mahasiswa->nim]);
        $newUser = User::factory()->create(['email' => 'another-google@example.test']);

        $this->actingAs($newUser)
            ->from('/auth/account/claim')
            ->post('/auth/account/claim', [
                'identifier' => $mahasiswa->nim,
                'password' => 'a-strong-password',
                'password_confirmation' => 'a-strong-password',
            ])
            ->assertRedirect('/auth/account/claim')
            ->assertSessionHasErrors('identifier');

        $this->assertFalse($newUser->fresh()->roles()->exists());
        $this->assertSame($mahasiswa->nim, $existingUser->fresh()->identifier_number);
    }

    public function test_google_login_creates_a_lecturer_account_from_active_master_data(): void
    {
        Dosen::create([
            'nidn' => '0011223344',
            'nama_dosen' => 'Dosen Terdaftar',
            'email' => 'dosen@example.test',
            'telepon' => '081234567891',
        ]);
        $this->mockGoogleUser('dosen@example.test', 'Nama Google');

        $this->get('/auth/google/callback')
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'dosen@example.test')->firstOrFail();
        $this->assertSame('Dosen Terdaftar', $user->name);
        $this->assertSame('0011223344', $user->identifier_number);
        $this->assertSame('Dosen', $user->position_title);
        $this->assertTrue($user->hasRole('Dosen'));
    }

    private function mockGoogleUser(string $email, string $name): void
    {
        $googleUser = (new SocialiteUser)
            ->map([
                'email' => $email,
                'name' => $name,
                'avatar' => 'https://example.test/avatar.png',
            ])
            ->setRaw(['verified_email' => true])
            ->setToken('google-access-token');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
