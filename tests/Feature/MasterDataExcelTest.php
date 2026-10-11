<?php

namespace Tests\Feature;

use App\Exports\DosensExport;
use App\Exports\MahasiswasExport;
use App\Imports\DosensImport;
use App\Imports\MahasiswasImport;
use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Prodi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MasterDataExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_import_skips_existing_and_duplicate_rows_and_exports_matching_headings(): void
    {
        $prodi = Prodi::create([
            'kode_prodi' => 'TS',
            'nama_prodi' => 'Teknik Sipil',
            'jenjang' => 'S1',
        ]);
        Dosen::create(['nidn' => '100001', 'nama_dosen' => 'Dosen Tersimpan']);
        $import = new DosensImport;
        $headings = (new DosensExport)->headings();

        $import->onRow($this->row($headings, ['100001', null, 'Data lama', null, null, null, null, 'TS', 'Teknik Sipil', 1], 2));
        $import->onRow($this->row($headings, ['100002', '200002', 'Dosen Baru', null, null, null, null, 'TS', 'Teknik Sipil', 1], 3));
        $import->onRow($this->row($headings, ['100002', '200003', 'Duplikat dalam file', null, null, null, null, 'TS', 'Teknik Sipil', 1], 4));

        $this->assertSame(1, $import->importedCount);
        $this->assertSame([2, 4], array_column($import->skipped, 'rowNumber'));
        $this->assertDatabaseHas('dosens', ['nidn' => '100002', 'nuptk' => '200002', 'nama_dosen' => 'Dosen Baru', 'prodi_id' => $prodi->id]);
        $this->assertSame(
            ['nidn', 'nuptk', 'nama_dosen', 'gelar_depan', 'gelar_belakang', 'email', 'telepon', 'program_studi', 'aktif'],
            $headings
        );
    }

    public function test_mahasiswa_import_skips_existing_and_duplicate_rows_and_exports_matching_headings(): void
    {
        MahasiswaRegistrasi::create(['nim' => '20260001', 'nama_mahasiswa' => 'Mahasiswa Tersimpan']);
        $import = new MahasiswasImport;
        $headings = (new MahasiswasExport)->headings();

        $import->onRow($this->row($headings, ['20260001', 'Data lama'], 2));
        $import->onRow($this->row($headings, ['20260002', 'Mahasiswa Baru'], 3));
        $import->onRow($this->row($headings, ['20260002', 'Duplikat dalam file'], 4));

        $this->assertSame(1, $import->importedCount);
        $this->assertSame([2, 4], array_column($import->skipped, 'rowNumber'));
        $this->assertDatabaseHas('mahasiswa_registrasis', ['nim' => '20260002', 'nama_mahasiswa' => 'Mahasiswa Baru']);
        $this->assertSame(
            ['nim', 'nama_mahasiswa', 'email', 'nama_program_studi', 'angkatan', 'telepon', 'aktif'],
            $headings
        );
    }

    private function row(array $headings, array $values, int $rowNumber): Row
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([$headings], null, 'A1');
        $sheet->fromArray([$values], null, "A{$rowNumber}");

        return new Row($sheet->getRowIterator($rowNumber, $rowNumber)->current(), $headings);
    }
}
