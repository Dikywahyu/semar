<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DosensExport;
use App\Http\Controllers\Controller;
use App\Imports\DosensImport;
use App\Models\Dosen\Dosen;
use App\Models\Prodi;
use App\Services\AcademicAccountGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DosenController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeManagement();

        if ($request->ajax()) {
            $query = Dosen::query()->with('prodi:id,kode_prodi,nama_prodi,jenjang');
            $total = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));

            if ($search !== '') {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_dosen', 'like', "%{$search}%")
                        ->orWhere('nidn', 'like', "%{$search}%")
                        ->orWhere('nuptk', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('program_studi', 'like', "%{$search}%")
                        ->orWhereHas('prodi', fn($query) => $query->where('nama_prodi', 'like', "%{$search}%"));
                });
            }

            $filtered = (clone $query)->count();
            $length = min(max((int) $request->input('length', 10), 1), 100);
            $dosens = $query->orderBy('nama_dosen')
                ->offset(max((int) $request->input('start', 0), 0))
                ->limit($length)
                ->get();

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $dosens,
            ]);
        }

        return view('admin.master.dosens', [
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(),
        ]);
    }

    public function export()
    {
        $this->authorizeManagement();

        return Excel::download(new DosensExport, 'data-dosen.xlsx');
    }

    public function generateUsers(Request $request, AcademicAccountGenerator $generator): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $result = $generator->generateLecturerAccounts();

        Log::info('Lecturer accounts generated from master data.', [
            'user_id' => auth()->id(),
            'created_count' => $result['created_count'],
            'skipped_count' => $result['skipped_count'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pembuatan akun dosen selesai.',
                ...$result,
            ]);
        }

        return redirect()->route('master.dosens.index')
            ->with('success', "Pembuatan akun selesai: {$result['created_count']} akun dibuat, {$result['skipped_count']} data dilewati.");
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $import = new DosensImport;
        Excel::import($import, $request->file('file'));

        $report = [
            'imported' => $import->importedCount,
            'skipped' => $import->skipped,
        ];

        Log::info('Master dosen Excel import completed.', [
            'user_id' => auth()->id(),
            'imported_count' => $import->importedCount,
            'skipped_count' => count($import->skipped),
            'skipped_rows' => $import->skipped,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Impor dosen selesai.',
                'imported_count' => $import->importedCount,
                'skipped_count' => count($import->skipped),
                'skipped' => $import->skipped,
            ]);
        }

        return redirect()->route('master.dosens.index')
            ->with('import_report', $report)
            ->with('success', "Impor selesai: {$import->importedCount} data ditambahkan, " . count($import->skipped) . ' baris dilewati.');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $dosen = Dosen::create($this->validatedData($request));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data dosen berhasil ditambahkan.', 'data' => $dosen], 201);
        }

        return back()->with('success', 'Data dosen berhasil ditambahkan.');
    }

    public function update(Request $request, Dosen $dosen): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $dosen->update($this->validatedData($request, $dosen));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data dosen berhasil diperbarui.', 'data' => $dosen]);
        }

        return back()->with('success', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Request $request, Dosen $dosen): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        abort_if($dosen->pengajuanBimbingan()->exists(), 409, 'Dosen yang masih menjadi pembimbing PKL tidak dapat dihapus.');
        abort_if(DB::table('dosen_penguji_pkl')->where('dosen_id', $dosen->id_dosen)->exists(), 409, 'Dosen yang masih menjadi penguji PKL tidak dapat dihapus.');

        $dosen->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data dosen berhasil dihapus.']);
        }

        return back()->with('success', 'Data dosen berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Dosen $dosen = null): array
    {
        $validated = $request->validate([
            'nidn' => ['nullable', 'string', 'max:30', Rule::unique('dosens', 'nidn')->ignore($dosen?->id_dosen, 'id_dosen')],
            'nuptk' => ['nullable', 'string', 'max:30', Rule::unique('dosens', 'nuptk')->ignore($dosen?->id_dosen, 'id_dosen')],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:100'],
            'gelar_belakang' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('dosens', 'email')->ignore($dosen?->id_dosen, 'id_dosen')],
            'telepon' => ['nullable', 'string', 'max:30'],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $nidn = $validated['nidn'] ?? null;
        $nuptk = $validated['nuptk'] ?? null;
        if (filled($nidn) || filled($nuptk)) {
            $identifierConflict = Dosen::query()
                ->when($dosen, fn($query) => $query->where('id_dosen', '!=', $dosen->id_dosen))
                ->where(function ($query) use ($nidn, $nuptk) {
                    if (filled($nidn)) {
                        $query->where('nuptk', $nidn);
                    }
                    if (filled($nuptk)) {
                        $method = filled($nidn) ? 'orWhere' : 'where';
                        $query->{$method}('nidn', $nuptk);
                    }
                })
                ->exists();

            if ($identifierConflict) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nidn' => 'NIDN atau NUPTK sudah digunakan sebagai identitas lain.',
                ]);
            }
        }

        $validated['program_studi'] = Prodi::findOrFail($validated['prodi_id'])->nama_prodi;

        return $validated;
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([
            'Super Admin',
            'BAAK',
            'Akademik',
        ]) || auth()->user()?->can('academic-edit'), 403);
    }
}
