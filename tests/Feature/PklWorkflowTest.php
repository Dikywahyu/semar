<?php

namespace Tests\Feature;

use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Dosen\Dosen;
use App\Models\Pkl\KomponenPenilaianPkl;
use App\Models\Pkl\PengajuanPkl;
use App\Models\Semester;
use App\Models\User;
use App\Models\Pkl\BerkasSeminarPkl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    public function test_student_schedule_submission_becomes_the_official_schedule_and_assigns_examiners(): void
    {
        Mail::fake();
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
        $examiner = Dosen::create([
            'nidn' => '0011223399',
            'nama_dosen' => 'Dosen Penguji',
            'email' => 'penguji@example.test',
        ]);
        $proposedDate = now()->addDays(3)->startOfHour();

        $this->actingAs($studentUser)
            ->post(route('pkl.mahasiswa.usulkan-jadwal', $submission), [
                'tanggal_seminar' => $proposedDate->format('Y-m-d H:i:s'),
                'ruangan_seminar' => 'Usulan Ruang Sidang 1',
                'penguji_ids' => [$examiner->id_dosen],
                'catatan_usulan_jadwal' => 'Waktu yang disepakati dengan dosen.',
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertEquals($proposedDate->format('Y-m-d H:i:s'), $submission->tanggal_seminar->format('Y-m-d H:i:s'));
        $this->assertSame('Usulan Ruang Sidang 1', $submission->ruangan_seminar);
        $this->assertTrue($submission->dosenPenguji->contains('id_dosen', $examiner->id_dosen));
        $this->assertNotNull($submission->schedule_approved_at);
        $this->assertNull($submission->usulan_tanggal_seminar);
        $this->assertNull($submission->usulan_ruangan_seminar);
        $this->assertNull($submission->usulan_dosen_penguji);
    }

    public function test_admin_can_delete_pkl_submission_and_associated_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->userWithRole('Admin');
        $semester = $this->createSemester();
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260099',
            'nama_mahasiswa' => 'Mahasiswa Hapus',
            'email' => 'hapus@example.test',
        ]);

        $proposalFile = 'pkl/test_proposal.pdf';
        $revisiFile = 'pkl/test_revisi.pdf';
        $seminarFile = 'pkl/test_seminar_doc.pdf';

        Storage::disk('local')->put($proposalFile, 'proposal content');
        Storage::disk('local')->put($revisiFile, 'revisi content');
        Storage::disk('local')->put($seminarFile, 'seminar content');

        $submission = PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $student->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Hapus',
            'alamat_instansi' => 'Alamat Hapus',
            'semester_master_id' => $semester->id,
            'id_semester' => $semester->kode_semester,
            'status' => 'Revisi Laporan',
            'berkas_proposal' => $proposalFile,
            'berkas_laporan_revisi' => $revisiFile,
        ]);

        $submission->berkasSeminar()->create([
            'jenis_berkas' => 'draft_laporan',
            'path_berkas' => $seminarFile,
            'status_verifikasi' => 'Valid',
        ]);

        $submission->logAktivitas()->create([
            'aktor_id' => $admin->id,
            'aksi' => 'Catatan Uji',
            'deskripsi' => 'Pengujian hapus',
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('pkl.admin.pengajuan.destroy', $submission));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'stats']);

        $this->assertDatabaseMissing('pengajuan_pkl', ['id' => $submission->id]);
        $this->assertDatabaseMissing('berkas_seminar_pkl', ['path_berkas' => $seminarFile]);
        $this->assertDatabaseMissing('log_aktivitas_pkl', ['aksi' => 'Catatan Uji']);

        Storage::disk('local')->assertMissing($proposalFile);
        Storage::disk('local')->assertMissing($revisiFile);
        Storage::disk('local')->assertMissing($seminarFile);
    }

    public function test_non_admin_cannot_delete_pkl_submission(): void
    {
        $studentUser = $this->userWithRole('Mahasiswa');
        $semester = $this->createSemester();
        $student = MahasiswaRegistrasi::create([
            'nim' => '20260098',
            'nama_mahasiswa' => 'Mahasiswa Test',
            'email' => 'student_test@example.test',
        ]);

        $submission = PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $student->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Test',
            'alamat_instansi' => 'Alamat Test',
            'semester_master_id' => $semester->id,
            'id_semester' => $semester->kode_semester,
            'status' => 'Pengajuan & Verifikasi Admin',
        ]);

        $this->actingAs($studentUser)
            ->deleteJson(route('pkl.admin.pengajuan.destroy', $submission))
            ->assertForbidden();

        $this->assertDatabaseHas('pengajuan_pkl', ['id' => $submission->id]);
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
