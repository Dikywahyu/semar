<?php

namespace App\Exports;

use App\Models\TugasAkhir\ThesisApplication;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ThesisAssignmentExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly ?int $semesterId)
    {
    }

    public function collection(): Collection
    {
        if (! $this->semesterId) {
            return collect();
        }

        return ThesisApplication::query()
            ->with(['student', 'p1', 'p2', 'examiner'])
            ->when($this->semesterId, fn ($query) => $query->where('semester_id', $this->semesterId))
            ->orderBy('id')
            ->get()
            ->map(fn (ThesisApplication $application) => [
                'application_id' => $application->id,
                'student_id' => $application->student_id,
                'student_name' => $application->student?->name,
                'expertise_theme' => $application->expertise_theme,
                'p1_id' => $application->p1_id,
                'p1_name' => $application->p1?->nama_dosen,
                'p2_id' => $application->p2_id,
                'p2_name' => $application->p2?->nama_dosen,
                'examiner_id' => $application->examiner_id,
                'examiner_name' => $application->examiner?->nama_dosen,
            ]);
    }

    public function headings(): array
    {
        return ['application_id', 'student_id', 'student_name', 'expertise_theme', 'p1_id', 'p1_name', 'p2_id', 'p2_name', 'examiner_id', 'examiner_name'];
    }
}
