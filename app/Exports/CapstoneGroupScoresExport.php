<?php

namespace App\Exports;

use App\Models\Capstone\CapstoneGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CapstoneGroupScoresExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly int $groupId)
    {
    }

    public function collection(): Collection
    {
        return CapstoneGroup::query()
            ->with(['studentScores.student', 'studentScores.component', 'studentScores.evaluator'])
            ->findOrFail($this->groupId)
            ->studentScores
            ->map(fn ($score) => [
                'mahasiswa' => $score->student?->name,
                'komponen' => $score->component?->name,
                'kategori' => $score->component?->category,
                'penilai' => $score->evaluator?->nama_dosen,
                'nilai' => $score->score,
            ]);
    }

    public function headings(): array
    {
        return ['Mahasiswa', 'Komponen', 'Kategori', 'Penilai', 'Nilai'];
    }
}
