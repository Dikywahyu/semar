<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MahasiswasExport;
use App\Http\Controllers\Controller;
use App\Imports\MahasiswasImport;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Prodi;
use App\Services\AcademicAccountGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MahasiswaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeManagement();

        if ($request->ajax()) {
            $query = MahasiswaRegistrasi::query()->with('prodi:id,kode_prodi,nama_prodi,jenjang');
            $total = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));

            if ($search !== '') {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_mahasiswa', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nama_program_studi', 'like', "%{$search}%")
                        ->orWhereHas('prodi', fn ($query) => $query->where('nama_prodi', 'like', "%{$search}%"));
                });
            }

            $filtered = (clone $query)->count();
            $length = min(max((int) $request->input('length', 10), 1), 100);
            $mahasiswas = $query->orderBy('nama_mahasiswa')
                ->offset(max((int) $request->input('start', 0), 0))
                ->limit($length)
                ->get();

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $mahasiswas,
            ]);
        }

        return view('admin.master.mahasiswas', [
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(),
        ]);
    }

    public function export()
    {
        $this->authorizeManagement();

        return Excel::download(new MahasiswasExport, 'data-mahasiswa.xlsx');
    }

    public function generateUsers(Request $request, AcademicAccountGenerator $generator): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $result = $generator->generateStudentAccounts();

        Log::info('Student accounts generated from master data.', [
            'user_id' => auth()->id(),
            'created_count' => $result['created_count'],
            'skipped_count' => $result['skipped_count'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pembuatan akun mahasiswa selesai.',
                ...$result,
            ]);
        }

        return redirect()->route('master.mahasiswas.index')
            ->with('success', "Pembuatan akun selesai: {$result['created_count']} akun dibuat, {$result['skipped_count']} data dilewati.");
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $import = new MahasiswasImport;
        Excel::import($import, $request->file('file'));

        $report = [
            'imported' => $import->importedCount,
            'skipped' => $import->skipped,
        ];

        Log::info('Master mahasiswa Excel import completed.', [
            'user_id' => auth()->id(),
            'imported_count' => $import->importedCount,
            'skipped_count' => count($import->skipped),
            'skipped_rows' => $import->skipped,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Impor mahasiswa selesai.',
                'imported_count' => $import->importedCount,
                'skipped_count' => count($import->skipped),
                'skipped' => $import->skipped,
            ]);
        }

        return redirect()->route('master.mahasiswas.index')
            ->with('import_report', $report)
            ->with('success', "Impor selesai: {$import->importedCount} data ditambahkan, ".count($import->skipped).' baris dilewati.');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $mahasiswa = MahasiswaRegistrasi::create($this->validatedData($request));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data mahasiswa berhasil ditambahkan.', 'data' => $mahasiswa], 201);
        }

        return back()->with('success', 'Data mahasiswa berhasil ditambahkan.');
    }

    public function update(Request $request, MahasiswaRegistrasi $mahasiswa): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        $mahasiswa->update($this->validatedData($request, $mahasiswa));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data mahasiswa berhasil diperbarui.', 'data' => $mahasiswa]);
        }

        return back()->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy(Request $request, MahasiswaRegistrasi $mahasiswa): RedirectResponse|JsonResponse
    {
        $this->authorizeManagement();
        abort_if($mahasiswa->pengajuanPkl()->exists(), 409, 'Mahasiswa yang memiliki pengajuan PKL tidak dapat dihapus.');

        $mahasiswa->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data mahasiswa berhasil dihapus.']);
        }

        return back()->with('success', 'Data mahasiswa berhasil dihapus.');
    }

    private function validatedData(Request $request, ?MahasiswaRegistrasi $mahasiswa = null): array
    {
        $validated = $request->validate([
            'nim' => ['required', 'string', 'max:30', Rule::unique('mahasiswa_registrasis', 'nim')->ignore($mahasiswa?->id_registrasi_mahasiswa, 'id_registrasi_mahasiswa')],
            'nama_mahasiswa' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('mahasiswa_registrasis', 'email')->ignore($mahasiswa?->id_registrasi_mahasiswa, 'id_registrasi_mahasiswa')],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'angkatan' => ['nullable', 'integer', 'between:1900,2100'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['sometimes', 'boolean'],
        ]);
        $validated['nama_program_studi'] = Prodi::findOrFail($validated['prodi_id'])->nama_prodi;

        return $validated;
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([
            'Super Admin',
            'BAAK',
            'Akademik',
        ]) || auth()->user()?->can('student-edit'), 403);
    }
}
