<?php

namespace App\Imports;

use App\Models\Dosen\Dosen;
use App\Models\Prodi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class DosensImport implements OnEachRow, SkipsEmptyRows, WithHeadingRow
{
    public int $importedCount = 0;

    public array $skipped = [];

    private array $seenNidn = [];

    private array $seenNuptk = [];

    private array $seenEmails = [];

    public function onRow(Row $row): void
    {
        $data = $this->normalize($row->toArray());
        $rowNumber = $row->getIndex();
        $identifier = $data['nidn'] ?: ($data['nuptk'] ?: '(NIDN/NUPTK kosong)');

        $validator = Validator::make($data, [
            'nidn' => ['nullable', 'string', 'max:30', Rule::unique('dosens', 'nidn')],
            'nuptk' => ['nullable', 'string', 'max:30', Rule::unique('dosens', 'nuptk')],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:100'],
            'gelar_belakang' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('dosens', 'email')],
            'telepon' => ['nullable', 'string', 'max:30'],
            'program_studi' => ['nullable', 'string', 'max:255'],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'aktif' => ['required', 'boolean'],
        ]);

        $nidnKey = strtolower((string) $data['nidn']);
        $nuptkKey = strtolower((string) $data['nuptk']);
        $emailKey = strtolower((string) $data['email']);

        if ($validator->fails()) {
            $this->skip($rowNumber, $identifier, $validator->errors()->first());

            return;
        }

        if (($nidnKey !== '' && isset($this->seenNidn[$nidnKey]))
            || ($nuptkKey !== '' && isset($this->seenNuptk[$nuptkKey]))
            || ($nidnKey !== '' && isset($this->seenNuptk[$nidnKey]))
            || ($nuptkKey !== '' && isset($this->seenNidn[$nuptkKey]))
            || ($emailKey !== '' && isset($this->seenEmails[$emailKey]))) {
            $this->skip($rowNumber, $identifier, 'NIDN, NUPTK, atau email duplikat pada file impor.');

            return;
        }

        $identifierConflict = Dosen::query()
            ->where(function ($query) use ($nidnKey, $nuptkKey) {
                if ($nidnKey !== '') {
                    $query->where('nuptk', $nidnKey);
                }
                if ($nuptkKey !== '') {
                    $method = $nidnKey === '' ? 'where' : 'orWhere';
                    $query->{$method}('nidn', $nuptkKey);
                }
            })
            ->exists();

        if ($identifierConflict) {
            $this->skip($rowNumber, $identifier, 'NIDN atau NUPTK sudah digunakan sebagai identitas lain.');

            return;
        }

        Dosen::create($data);
        $this->importedCount++;
        if ($nidnKey !== '') {
            $this->seenNidn[$nidnKey] = true;
        }
        if ($nuptkKey !== '') {
            $this->seenNuptk[$nuptkKey] = true;
        }
        if ($emailKey !== '') {
            $this->seenEmails[$emailKey] = true;
        }
    }

    private function normalize(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = is_string($value) ? trim($value) : $value;
            if ($row[$key] === '') {
                $row[$key] = null;
            }
        }

        $row['aktif'] = $this->normalizeActiveValue($row['aktif'] ?? null);

        return [
            'nidn' => $this->normalizeText($row['nidn'] ?? null),
            'nuptk' => $this->normalizeText($row['nuptk'] ?? null),
            'nama_dosen' => $this->normalizeText($row['nama_dosen'] ?? null),
            'gelar_depan' => $this->normalizeText($row['gelar_depan'] ?? null),
            'gelar_belakang' => $this->normalizeText($row['gelar_belakang'] ?? null),
            'email' => $this->normalizeText($row['email'] ?? null),
            'telepon' => $this->normalizeText($row['telepon'] ?? null),
            'program_studi' => $this->normalizeText($row['nama_prodi'] ?? $row['program_studi'] ?? null),
            'prodi_id' => $this->findProdiId($row),
            'aktif' => $row['aktif'],
        ];
    }

    private function findProdiId(array $row): ?int
    {
        $prodi = Prodi::query()
            ->when(filled($row['kode_prodi'] ?? null), fn ($query) => $query->where('kode_prodi', $row['kode_prodi']))
            ->when(blank($row['kode_prodi'] ?? null) && filled($row['nama_prodi'] ?? $row['program_studi'] ?? null), fn ($query) => $query->whereRaw('LOWER(nama_prodi) = ?', [mb_strtolower((string) ($row['nama_prodi'] ?? $row['program_studi']))]))
            ->first();

        return $prodi?->id;
    }

    private function normalizeText(mixed $value): ?string
    {
        return $value === null ? null : trim((string) $value);
    }

    private function normalizeActiveValue(mixed $value): mixed
    {
        if ($value === null) {
            return 1;
        }

        return match (strtolower((string) $value)) {
            '1', 'true', 'yes', 'ya', 'aktif' => 1,
            '0', 'false', 'no', 'tidak', 'nonaktif' => 0,
            default => $value,
        };
    }

    private function skip(int $rowNumber, string $identifier, string $reason): void
    {
        $this->skipped[] = compact('rowNumber', 'identifier', 'reason');
    }
}
