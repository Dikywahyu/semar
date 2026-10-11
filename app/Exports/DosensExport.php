<?php

namespace App\Exports;

use App\Models\Dosen\Dosen;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DosensExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Dosen::query()->with('prodi')->orderBy('nama_dosen');
    }

    public function headings(): array
    {
        return ['nidn',   'nama_dosen', 'gelar_depan', 'gelar_belakang', 'email', 'telepon', 'kode_prodi', 'nama_prodi', 'aktif'];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 20, 'C' => 30, 'D' => 18, 'E' => 18, 'F' => 30, 'G' => 18, 'H' => 12, 'I' => 30, 'J' => 10];
    }

    public function map($dosen): array
    {
        return [
            $dosen->nidn,
            $dosen->nuptk,
            $dosen->nama_dosen,
            $dosen->gelar_depan,
            $dosen->gelar_belakang,
            $dosen->email,
            $dosen->telepon,
            $dosen->prodi?->kode_prodi,
            $dosen->prodi?->nama_prodi ?? $dosen->program_studi,
            $dosen->aktif ? 1 : 0,
        ];
    }
}
