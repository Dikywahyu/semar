<?php

namespace App\Imports;

use App\Models\TugasAkhir\ThesisApplication;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ThesisAssignmentImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $data = $row->toArray();
                Validator::make($data, [
                    'application_id' => ['required', 'integer', 'exists:thesis_applications,id'],
                    'p2_id' => ['nullable', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
                    'examiner_id' => ['nullable', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
                ])->validate();

                $application = ThesisApplication::query()->lockForUpdate()->findOrFail($data['application_id']);
                $p2Id = $data['p2_id'] ?? null;
                $examinerId = $data['examiner_id'] ?? null;
                if (($p2Id !== null && $p2Id === $application->p1_id)
                    || ($examinerId !== null && in_array($examinerId, [$application->p1_id, $p2Id], true))) {
                    throw ValidationException::withMessages([
                        'file' => 'Pembimbing 1, Pembimbing 2, dan Penguji harus merupakan dosen yang berbeda (baris ' . ($index + 2) . ').',
                    ]);
                }
                if ($application->scores()->exists()) {
                    throw ValidationException::withMessages([
                        'file' => 'Plotting tidak dapat diubah setelah nilai mulai diinput (application_id ' . $application->id . ').',
                    ]);
                }
                $application->update([
                    'p2_id' => $p2Id,
                    'examiner_id' => $examinerId,
                ]);
            }
        });
    }
}
