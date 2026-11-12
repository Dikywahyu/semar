<?php

namespace App\Exports;

use App\Models\Capstone\CapstoneGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CapstonePlottingExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return CapstoneGroup::query()
            ->with(['leader', 'expertise', 'supervisor', 'examiner'])
            ->orderBy('id')
            ->get()
            ->map(fn (CapstoneGroup $group) => [
                'group_id' => $group->id,
                'leader_id' => $group->leader_id,
                'leader_name' => $group->leader?->name,
                'expertise_id' => $group->expertise_id,
                'expertise' => $group->expertise?->name,
                'supervisor_id' => $group->supervisor_id,
                'supervisor_name' => $group->supervisor?->nama_dosen,
                'examiner_id' => $group->examiner_id,
                'examiner_name' => $group->examiner?->nama_dosen,
                'project_location' => $group->project_location,
            ]);
    }

    public function headings(): array
    {
        return ['group_id', 'leader_id', 'leader_name', 'expertise_id', 'expertise', 'supervisor_id', 'supervisor_name', 'examiner_id', 'examiner_name', 'project_location'];
    }
}
