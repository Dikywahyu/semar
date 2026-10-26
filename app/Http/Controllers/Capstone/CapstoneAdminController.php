<?php

namespace App\Http\Controllers\Capstone;

use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneExpertise;
use App\Models\Capstone\CapstoneScoreComponent;
use App\Models\Dosen\Dosen;
use Illuminate\Http\Request;

class CapstoneAdminController extends Controller
{

    // 1. Index Monitoring Admin / KPS
    public function index(Request $request)
    {
        $query = CapstoneGroup::with(['leader', 'expertise', 'supervisor', 'examiner', 'members.student']);

        if ($request->filled('expertise_id')) {
            $query->where('expertise_id', $request->expertise_id);
        }
        if ($request->filled('final_status')) {
            $query->where('final_status', $request->final_status);
        }
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhereHas('leader', fn($l) => $l->where('name', 'like', "%{$keyword}%"));
            });
        }

        $groups = $query->get();
        $expertises = CapstoneExpertise::all();
        $dosens = Dosen::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $groups
            ]);
        }

        return view('capstone.admin.index', compact('groups', 'expertises', 'dosens'));
    }

    // 2. Admin Memberikan / Mengatur Ruangan Ujian
    public function setExamRoom(Request $request, $groupId)
    {
        $request->validate([
            'exam_room' => 'required|string|max:100'
        ]);

        $group = CapstoneGroup::findOrFail($groupId);
        $group->update(['exam_room' => $request->exam_room]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ruang ujian berhasil ditetapkan oleh Admin.'
        ]);
    }

    // --- MASTER KOMPONEN NILAI (YANG SEBELUMNYA BELUM MUNCUL) ---

    // 3. Tampilkan Halaman atau Data Master Komponen Nilai
    public function komponenIndex(Request $request)
    {
        $components = CapstoneScoreComponent::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $components
            ]);
        }

        // Jika Anda ingin membuat view khusus master komponen:resources/views/capstone/admin/komponen.blade.php
        return view('capstone.admin.komponen', compact('components'));
    }

    // 4. Simpan Komponen Nilai Baru
    public function storeKomponen(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:pembimbing,penguji',
            'percentage' => 'required|numeric|min:0|max:100'
        ]);

        $component = CapstoneScoreComponent::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil ditambahkan.',
            'data' => $component
        ]);
    }

    // 5. Perbarui Komponen Nilai
    public function updateKomponen(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:pembimbing,penguji',
            'percentage' => 'required|numeric|min:0|max:100'
        ]);

        $component = CapstoneScoreComponent::findOrFail($id);
        $component->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil diperbarui.',
            'data' => $component
        ]);
    }

    // 6. Hapus Komponen Nilai
    public function destroyKomponen($id)
    {
        $component = CapstoneScoreComponent::findOrFail($id);
        $component->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil dihapus.'
        ]);
    }

    // --- MASTER KEAHLIAN (EXPERTISES) ---

    // Tampilkan Halaman Master Keahlian
    public function expertiseIndex(Request $request)
    {
        $expertises = CapstoneExpertise::with(['coordinator', 'lecturers'])->get();
        $dosens = Dosen::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $expertises
            ]);
        }

        return view('capstone.admin.expertise', compact('expertises', 'dosens'));
    }

    // Simpan Keahlian Baru
    public function storeExpertise(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'coordinator_id' => 'nullable|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil ditambahkan.',
            'data' => $expertise
        ]);
    }

    // Perbarui Keahlian (Edit)
    public function updateExpertise(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'coordinator_id' => 'nullable|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::findOrFail($id);
        $expertise->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil diperbarui.',
            'data' => $expertise
        ]);
    }

    // Hapus Keahlian
    public function destroyExpertise($id)
    {
        $expertise = CapstoneExpertise::findOrFail($id);
        $expertise->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil dihapus.'
        ]);
    }

    // --- MANAJEMEN DOSEN PADA KEAHLIAN (PIVOT) ---

    // Ambil daftar dosen yang terhubung ke suatu keahlian
    public function GetExpertiseLecturers($expertiseId)
    {
        $expertise = CapstoneExpertise::with('lecturers')->findOrFail($expertiseId);
        $allDosens = Dosen::all();

        return response()->json([
            'status' => 'success',
            'expertise' => $expertise,
            'all_dosens' => $allDosens,
            'assigned_lecturers' => $expertise->lecturers // Relasi many-to-many via tabel pivot
        ]);
    }

    // Tambah dosen ke bidang keahlian
    public function storeExpertiseLecturer(Request $request, $expertiseId)
    {
        $request->validate([
            'lecturer_id' => 'required|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::findOrFail($expertiseId);

        // Cek apakah dosen sudah terdaftar di keahlian ini
        if (!$expertise->lecturers()->where('lecturer_id', $request->lecturer_id)->exists()) {
            $expertise->lecturers()->attach($request->lecturer_id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dosen berhasil ditambahkan ke bidang keahlian ini.'
        ]);
    }

    // Hapus dosen dari bidang keahlian
    public function destroyExpertiseLecturer($expertiseId, $lecturerId)
    {
        $expertise = CapstoneExpertise::findOrFail($expertiseId);
        $expertise->lecturers()->detach($lecturerId);

        return response()->json([
            'status' => 'success',
            'message' => 'Dosen berhasil dihapus dari bidang keahlian ini.'
        ]);
    }
}
