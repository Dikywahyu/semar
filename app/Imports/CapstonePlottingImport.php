<?php

namespace App\Imports;

use App\Models\Capstone\CapstoneGroup;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CapstonePlottingImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?CapstoneGroup
    {
        $attributes = [
            'leader_id' => $row['leader_id'],
            'expertise_id' => $row['expertise_id'],
            'supervisor_id' => $row['supervisor_id'] ?? null,
            'examiner_id' => $row['examiner_id'] ?? null,
            'project_location' => $row['project_location'] ?? null,
            'status_pendaftaran' => 'submitted',
        ];

        if (! empty($row['group_id'])) {
            $group = CapstoneGroup::findOrFail($row['group_id']);
            $supervisorChanged = $group->supervisor_id !== ($attributes['supervisor_id'] ?? null);
            $group->update(collect($attributes)->except('leader_id')->all());
            if ($supervisorChanged) {
                $group->update([
                    'acc_supervisor' => false,
                    'acc_kps' => false,
                    'final_status' => 'proses',
                    'exam_invitation_sent_at' => null,
                ]);
            }

            return null;
        }

        return DB::transaction(fn () => CapstoneGroup::create($attributes));
    }

    public function rules(): array
    {
        return [
            '*.group_id' => ['nullable', 'integer', 'exists:capstone_groups,id'],
            '*.leader_id' => ['required', 'integer', 'exists:users,id'],
            '*.expertise_id' => ['required', 'integer', 'exists:capstone_expertises,id'],
            '*.supervisor_id' => ['nullable', 'exists:dosens,id_dosen'],
            '*.examiner_id' => ['nullable', 'exists:dosens,id_dosen'],
            '*.project_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
