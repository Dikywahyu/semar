<?php

namespace App\Http\Controllers\Capstone;

use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneScoreComponent;
use App\Models\Capstone\CapstoneStudentScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapstoneScoreController extends Controller
{

    // Index halaman penilaian dosen (Mendukung View & AJAX)
    public function dosenIndex(Request $request)
    {
        $user = Auth::user();
        $dosenId = $user?->id_dosen ?? null;

        $groups = CapstoneGroup::with(['leader', 'members.student', 'studentScores.component'])
            ->where('supervisor_id', $dosenId)
            ->orWhere('examiner_id', $dosenId)
            ->get();

        $components = CapstoneScoreComponent::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'assigned_groups' => $groups,
                'components' => $components
            ]);
        }

        return view('capstone.dosen.index', compact('groups', 'components'));
    }

    // --- MANAJEMEN KOMPONEN NILAI ---
    public function indexComponents(Request $request)
    {
        $components = CapstoneScoreComponent::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $components
            ]);
        }

        return view('capstone.admin.komponen', compact('components'));
    }

    public function storeComponent(Request $request)
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

    public function updateComponent(Request $request, $id)
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

    public function destroyComponent($id)
    {
        CapstoneScoreComponent::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil dihapus.'
        ]);
    }

    // --- INPUT & UNDUH NILAI MAHASISWA OLEH DOSEN ---
    public function storeStudentScores(Request $request, $groupId)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:users,id',
            'scores.*.component_id' => 'required|exists:capstone_score_components,id',
            'scores.*.score' => 'required|numeric|min:0|max:100',
        ]);

        $user = Auth::user();
        $dosenId = $user?->id_dosen ?? null;

        foreach ($request->scores as $item) {
            CapstoneStudentScore::updateOrCreate(
                [
                    'capstone_group_id' => $groupId,
                    'student_id' => $item['student_id'],
                    'component_id' => $item['component_id'],
                    'evaluator_id' => $dosenId
                ],
                [
                    'score' => $item['score']
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai komponen mahasiswa berhasil disimpan ke sistem.'
        ]);
    }

    public function downloadScores($groupId)
    {
        $group = CapstoneGroup::with(['studentScores.student', 'studentScores.component', 'supervisor', 'examiner'])->findOrFail($groupId);

        return response()->json([
            'status' => 'success',
            'group_title' => $group->title,
            'scores' => $group->studentScores
        ]);
    }
}
