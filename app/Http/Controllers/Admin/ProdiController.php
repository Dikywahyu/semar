<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProdiController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeManagement();

        if ($request->ajax()) {
            $query = Prodi::query()->withCount(['dosens', 'mahasiswas']);
            $total = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));

            if ($search !== '') {
                $query->where(fn ($query) => $query
                    ->where('kode_prodi', 'like', "%{$search}%")
                    ->orWhere('nama_prodi', 'like', "%{$search}%")
                    ->orWhere('jenjang', 'like', "%{$search}%")
                    ->orWhere('fakultas', 'like', "%{$search}%"));
            }

            $filtered = (clone $query)->count();
            $length = min(max((int) $request->input('length', 10), 1), 100);
            $data = $query->orderBy('nama_prodi')
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

        return view('admin.master.prodis');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManagement();
        $prodi = Prodi::create($this->validatedData($request));

        return response()->json(['message' => 'Program studi berhasil ditambahkan.', 'data' => $prodi], 201);
    }

    public function update(Request $request, Prodi $prodi): JsonResponse
    {
        $this->authorizeManagement();
        $prodi->update($this->validatedData($request, $prodi));

        return response()->json(['message' => 'Program studi berhasil diperbarui.', 'data' => $prodi->fresh()]);
    }

    public function destroy(Prodi $prodi): JsonResponse
    {
        $this->authorizeManagement();
        abort_if($prodi->dosens()->exists() || $prodi->mahasiswas()->exists(), 409, 'Program studi masih digunakan oleh data dosen atau mahasiswa.');
        $prodi->delete();

        return response()->json(['message' => 'Program studi berhasil dihapus.']);
    }

    public function options(): JsonResponse
    {
        $this->authorizeManagement();

        return response()->json([
            'data' => Prodi::query()->where('aktif', true)->orderBy('nama_prodi')->get(['id', 'kode_prodi', 'nama_prodi', 'jenjang']),
        ]);
    }

    private function validatedData(Request $request, ?Prodi $prodi = null): array
    {
        return $request->validate([
            'kode_prodi' => ['required', 'string', 'max:30', Rule::unique('prodis', 'kode_prodi')->ignore($prodi?->id)],
            'nama_prodi' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Profesi', 'Lainnya'])],
            'fakultas' => ['nullable', 'string', 'max:255'],
            'aktif' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'BAAK', 'Akademik'])
            || auth()->user()?->can('academic-edit')
            || auth()->user()?->can('student-edit'), 403);
    }
}
