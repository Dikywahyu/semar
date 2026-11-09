<?php

namespace App\Exports;

use App\Models\Capstone\CapstoneGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CapstoneGradeRecapExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly ?int $expertiseId = null)
    {
    }

    public function collection(): Collection
    {
        return CapstoneGroup::query()
            ->with(['leader', 'expertise', 'studentScores.student', 'studentScores.component'])
            ->when($this->expertiseId, fn ($query) => $query->where('expertise_id', $this->expertiseId))
            ->get()
            ->flatMap(fn (CapstoneGroup $group) => $group->studentScores->map(fn ($score) => [
                'group_id' => $group->id,
                'judul' => $group->title,
                'ketua' => $group->leader?->name,
                'bidang_keahlian' => $group->expertise?->name,
                'mahasiswa' => $score->student?->name,
                'komponen' => $score->component?->name,
                'kategori' => $score->component?->category,
                'nilai' => $score->score,
            ]));
    }

    public function headings(): array
    {
        return ['ID Kelompok', 'Judul', 'Ketua', 'Bidang Keahlian', 'Mahasiswa', 'Komponen', 'Kategori', 'Nilai'];
    }
}
