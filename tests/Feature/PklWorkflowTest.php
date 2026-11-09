<?php

namespace Tests\Feature;

use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Pkl\KomponenPenilaianPkl;
use App\Models\Pkl\PengajuanPkl;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PklWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_semester_filter_accepts_semester_id_and_code_and_renders_empty_state(): void
    {
        $admin = $this->userWithRole('Admin');
        $semester = $this->createSemester();
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260011',
            'nama_mahasiswa' => 'Mahasiswa Uji',
            'email' => 'mhs-uji@example.test',
        ]);
        PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $student->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Uji',
            'alamat_instansi' => 'Alamat Uji',
            'semester_master_id' => $semester->id,
            'id_semester' => $semester->kode_semester,
            'status' => 'Pengajuan & Verifikasi Admin',
        ]);

        $this->actingAs($admin)
            ->get(route('pkl.admin.index', ['id_semester' => $semester->kode_semester]))
            ->assertOk()
            ->assertViewHas('submissions', fn($submissions) => $submissions->total() === 1);

        $this->actingAs($admin)
            ->get(route('pkl.admin.index', ['id_semester' => $semester->id]))
            ->assertOk()
            ->assertViewHas('submissions', fn($submissions) => $submissions->total() === 1);

        $this->actingAs($admin)
            ->get(route('pkl.admin.index', ['id_semester' => '999999']))
            ->assertOk()
            ->assertViewHas('submissions', fn($submissions) => $submissions->total() === 0);
    }

    public function test_admin_can_load_components_for_a_submission_by_semester_master_relation(): void
    {
        $admin = $this->userWithRole('Admin');
        $semester = $this->createSemester();
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260012',
            'nama_mahasiswa' => 'Mahasiswa Komponen',
            'email' => 'komponen@example.test',
        ]);
        $submission = PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $student->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Uji',
            'alamat_instansi' => 'Alamat Uji',
            'semester_master_id' => $semester->id,
            'id_semester' => $semester->kode_semester,
            'status' => 'Dijadwalkan Ujian',
        ]);
        $component = KomponenPenilaianPkl::create([
            'semester_master_id' => $semester->id,
            'id_semester' => $semester->kode_semester,
            'penilai' => 'Pembimbing',
            'nama_komponen' => 'Laporan',
            'bobot' => 100,
            'bobot_persen' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('pkl.admin.penilaian.show', $submission))
            ->assertOk()
            ->assertJsonPath('data.komponens.0.id', $component->id);
    }

    public function test_student_schedule_proposal_does_not_change_official_schedule_or_assign_room(): void
    {
        $studentUser = $this->userWithRole('Mahasiswa', [
            'email' => 'student@example.test',
            'identifier_number' => '20260013',
        ]);
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260013',
            'nama_mahasiswa' => 'Mahasiswa Jadwal',
            'email' => 'student@example.test',
        ]);
        $submission = PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $student->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Uji',
            'alamat_instansi' => 'Alamat Uji',
            'status' => 'Dijadwalkan Ujian',
        ]);
        $proposedDate = now()->addDays(3)->startOfHour();

        $this->actingAs($studentUser)
            ->post(route('pkl.mahasiswa.usulkan-jadwal', $submission), [
                'tanggal_seminar' => $proposedDate->format('Y-m-d H:i:s'),
                'catatan_usulan_jadwal' => 'Waktu yang disepakati dengan dosen.',
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertNull($submission->tanggal_seminar);
        $this->assertNull($submission->ruangan_seminar);
        $this->assertEquals($proposedDate->format('Y-m-d H:i:s'), $submission->usulan_tanggal_seminar->format('Y-m-d H:i:s'));
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create($attributes);
        $user->assignRole($roleName);

        return $user;
    }

    private function createSemester(): Semester
    {
        return Semester::create([
            'tahun_akademik' => '2026/2027',
            'periode' => 'Ganjil',
            'kode_semester' => '20261',
            'aktif' => true,
        ]);
    }
}
