<?php

namespace App\Imports;

use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Prodi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MahasiswasImport implements OnEachRow, SkipsEmptyRows, WithHeadingRow
{
    public int $importedCount = 0;

    public array $skipped = [];

    private array $seenNim = [];

    private array $seenEmails = [];

    public function onRow(Row $row): void
    {
        $data = $this->normalize($row->toArray());
        $rowNumber = $row->getIndex();
        $identifier = $data['nim'] ?: '(NIM kosong)';

        $validator = Validator::make($data, [
            'nim' => ['required', 'string', 'max:30', Rule::unique('mahasiswa_registrasis', 'nim')],
            'nama_mahasiswa' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('mahasiswa_registrasis', 'email')],
            'nama_program_studi' => ['nullable', 'string', 'max:255'],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'angkatan' => ['nullable', 'integer', 'between:1900,2100'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['required', 'boolean'],
        ]);

        $nimKey = strtolower((string) $data['nim']);
        $emailKey = strtolower((string) $data['email']);

        if ($validator->fails()) {
            $this->skip($rowNumber, $identifier, $validator->errors()->first());
            return;
        }

        if (isset($this->seenNim[$nimKey]) || ($emailKey !== '' && isset($this->seenEmails[$emailKey]))) {
            $this->skip($rowNumber, $identifier, 'NIM atau email duplikat pada file impor.');
            return;
        }

        MahasiswaRegistrasi::create($data);
        $this->importedCount++;
        $this->seenNim[$nimKey] = true;
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
            'nim' => $this->normalizeText($row['nim'] ?? null),
            'nama_mahasiswa' => $this->normalizeText($row['nama_mahasiswa'] ?? null),
            'email' => $this->normalizeText($row['email'] ?? null),
            'nama_program_studi' => $this->normalizeText($row['nama_prodi'] ?? $row['nama_program_studi'] ?? null),
            'prodi_id' => $this->findProdiId($row),
            'angkatan' => $row['angkatan'] ?? null,
            'telepon' => $this->normalizeText($row['telepon'] ?? null),
            'aktif' => $row['aktif'],
        ];
    }

    private function normalizeText(mixed $value): ?string
    {
        return $value === null ? null : trim((string) $value);
    }

    private function findProdiId(array $row): ?int
    {
        $prodi = Prodi::query()
            ->when(filled($row['kode_prodi'] ?? null), fn ($query) => $query->where('kode_prodi', $row['kode_prodi']))
            ->when(blank($row['kode_prodi'] ?? null) && filled($row['nama_prodi'] ?? $row['nama_program_studi'] ?? null), fn ($query) => $query->whereRaw('LOWER(nama_prodi) = ?', [mb_strtolower((string) ($row['nama_prodi'] ?? $row['nama_program_studi']))]))
            ->first();

        return $prodi?->id;
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
