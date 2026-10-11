<?php

namespace App\Exports;

use App\Models\Mahasiswa\MahasiswaRegistrasi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswasExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return MahasiswaRegistrasi::query()->with('prodi')->orderBy('nama_mahasiswa');
    }

    public function headings(): array
    {
        return ['nim', 'nama_mahasiswa', 'email', 'kode_prodi', 'nama_prodi', 'angkatan', 'telepon', 'aktif'];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 30, 'C' => 30, 'D' => 28, 'E' => 14, 'F' => 18, 'G' => 10];
    }

    public function map($mahasiswa): array
    {
        return [
            $mahasiswa->nim,
            $mahasiswa->nama_mahasiswa,
            $mahasiswa->email,
            $mahasiswa->prodi?->kode_prodi,
            $mahasiswa->prodi?->nama_prodi ?? $mahasiswa->nama_program_studi,
            $mahasiswa->angkatan,
            $mahasiswa->telepon,
            $mahasiswa->aktif ? 1 : 0,
        ];
    }
}
