<?php

namespace Tests\Feature;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Pkl\PengajuanPkl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PklMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_master_records_are_used_by_pkl_submissions(): void
    {
        $dosen = Dosen::create([
            'nidn' => '0011223344',
            'nama_dosen' => 'Dosen Lokal',
            'email' => 'dosen@example.test',
        ]);
        $mahasiswa = MahasiswaRegistrasi::create([
            'nim' => '20260001',
            'nama_mahasiswa' => 'Mahasiswa Lokal',
            'email' => 'mahasiswa@example.test',
        ]);
        $pengajuan = PengajuanPkl::create([
            'mahasiswa_registrasi_id' => $mahasiswa->id_registrasi_mahasiswa,
            'nama_instansi' => 'Instansi Kampus',
            'alamat_instansi' => 'Alamat Kampus',
            'dosen_pembimbing_id' => $dosen->id_dosen,
            'status' => 'Pengajuan & Verifikasi Admin',
        ]);

        $this->assertNotEmpty($dosen->id_dosen);
        $this->assertNotEmpty($mahasiswa->id_registrasi_mahasiswa);
        $this->assertSame($dosen->id_dosen, $pengajuan->dosenPembimbing->id_dosen);
        $this->assertSame($mahasiswa->id_registrasi_mahasiswa, $pengajuan->mahasiswaRegistrasi->id_registrasi_mahasiswa);
        $this->assertTrue(Schema::hasColumn('pengajuan_pkl', 'berkas_proposal'));
        $this->assertTrue(Schema::hasColumn('pengajuan_pkl', 'unit_kerja'));
        $this->assertTrue(Schema::hasColumn('komponen_penilaian_pkl', 'id_semester'));
        $this->assertTrue(Schema::hasColumn('komponen_penilaian_pkl', 'bobot'));
    }
}
