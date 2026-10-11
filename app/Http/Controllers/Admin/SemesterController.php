<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeManagement();

        if ($request->ajax()) {
            $query = Semester::query()->withCount(['pengajuanPkl', 'komponenPenilaianPkl']);
            $total = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));

            if ($search !== '') {
                $query->where(fn ($query) => $query
                    ->where('tahun_akademik', 'like', "%{$search}%")
                    ->orWhere('periode', 'like', "%{$search}%")
                    ->orWhere('kode_semester', 'like', "%{$search}%"));
            }

            $filtered = (clone $query)->count();
            $length = min(max((int) $request->input('length', 10), 1), 100);
            $data = $query->orderByDesc('tahun_akademik')->orderBy('periode')
                ->offset(max((int) $request->input('start', 0), 0))
                ->limit($length)
                ->get();

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        }

        return view('admin.master.semesters');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManagement();
        $validated = $this->validatedData($request);

        $semester = DB::transaction(function () use ($validated) {
            if ($validated['aktif']) {
                Semester::query()->where('aktif', true)->update(['aktif' => false]);
            }

            return Semester::create($validated + [
                'kode_semester' => substr($validated['tahun_akademik'], 0, 4) . ($validated['periode'] === 'ganjil' ? '1' : '2'),
            ]);
        });

        return response()->json(['message' => 'Semester berhasil ditambahkan.', 'data' => $semester], 201);
    }

    public function update(Request $request, Semester $semester): JsonResponse
    {
        $this->authorizeManagement();
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $semester) {
            if ($validated['aktif']) {
                Semester::query()->where('aktif', true)->where('id', '!=', $semester->id)->update(['aktif' => false]);
            }

            $semester->update($validated);
        });

        return response()->json(['message' => 'Semester berhasil diperbarui.', 'data' => $semester->fresh()]);
    }

    public function destroy(Semester $semester): JsonResponse
    {
        $this->authorizeManagement();
        abort_if($semester->aktif, 409, 'Semester aktif tidak dapat dihapus.');
        abort_if($semester->pengajuanPkl()->exists() || $semester->komponenPenilaianPkl()->exists(), 409, 'Semester yang sudah digunakan oleh data PKL tidak dapat dihapus.');
        $semester->delete();

        return response()->json(['message' => 'Semester berhasil dihapus.']);
    }

    public function options(): JsonResponse
    {
        $this->authorizeManagement();

        return response()->json([
            'data' => Semester::query()->orderByDesc('tahun_akademik')->orderBy('periode')->get(),
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'tahun_akademik' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'periode' => ['required', Rule::in(['ganjil', 'genap'])],
            'aktif' => ['required', 'boolean'],
        ]);

        [$startYear, $endYear] = array_map('intval', explode('/', $validated['tahun_akademik']));
        abort_if($endYear !== $startYear + 1, 422, 'Tahun akademik harus berurutan, misalnya 2026/2027.');

        return $validated;
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'BAAK', 'Akademik'])
            || auth()->user()?->can('academic-edit'), 403);
    }
}
