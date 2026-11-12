<?php

namespace App\Http\Controllers\Capstone;

use App\Exports\CapstoneGroupScoresExport;
use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneScoreComponent;
use App\Models\Capstone\CapstoneStudentScore;
use App\Models\Dosen\Dosen;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Capstone\Concerns\ResolvesCapstoneProfiles;
use Illuminate\Support\Facades\Auth;

class CapstoneScoreController extends Controller
{
    use ResolvesCapstoneProfiles;

    private function authorizeCapstoneAdmin(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'KPS', 'Kaprodi']), 403);
    }

    // Index halaman penilaian dosen (Mendukung View & AJAX)
    public function dosenIndex(Request $request)
    {
        $dosen = $this->capstoneDosen($request);

        if (! $dosen && $this->canChooseCapstoneProfile()) {
            return view('capstone.profile_selector', [
                'jenis' => 'dosen',
                'profiles' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
                'route' => route('capstone.dosen.index'),
                'parameter' => 'id_dosen',
                'title' => 'Pilih Profil Dosen',
                'description' => 'Akun ini tidak terhubung ke profil dosen. Pilih dosen untuk membuka penilaian Capstone sebagai dosen.',
                'label' => fn (Dosen $profile) => $profile->nama_dosen . ($profile->nidn ? ' (' . $profile->nidn . ')' : ''),
            ]);
        }

        abort_unless($dosen, 403, 'Profil dosen aktif untuk akun ini belum tersedia.');
        $dosenId = $dosen->id_dosen;

        $selectedSemester = $request->input('semester_id');
        if (filled($selectedSemester) && $selectedSemester !== 'unassigned') {
            $request->validate(['semester_id' => ['integer', 'exists:semesters,id']]);
        }
        $groupsQuery = CapstoneGroup::with([
            'leader',
            'members.student',
            'expertise',
            'semesterMaster',
            'supervisor',
            'examiner',
            'supervisorApprover',
            'studentScores.component',
        ])
            ->where(function ($query) use ($dosenId) {
                $query->where('supervisor_id', $dosenId)
                    ->orWhere('examiner_id', $dosenId);
            });
        if ($selectedSemester === 'unassigned') {
            $groupsQuery->whereNull('semester_master_id');
        } elseif (filled($selectedSemester)) {
            $groupsQuery->where('semester_master_id', $selectedSemester);
        }
        $groups = $groupsQuery->orderByDesc('exam_date')->orderByDesc('created_at')->get();

        $components = CapstoneScoreComponent::all();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'assigned_groups' => $groups,
                'components' => $components
            ]);
        }

        $isActingAsProfile = $this->canChooseCapstoneProfile();
        $availableProfiles = $isActingAsProfile
            ? Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get()
            : collect();
        $semesters = Semester::query()
            ->orderByDesc('tahun_akademik')
            ->orderBy('periode')
            ->get();

        return view('capstone.dosen.index', compact(
            'groups',
            'components',
            'dosen',
            'isActingAsProfile',
            'availableProfiles',
            'semesters',
            'selectedSemester'
        ));
    }

    // --- MANAJEMEN KOMPONEN NILAI ---
    public function indexComponents(Request $request)
    {
        $this->authorizeCapstoneAdmin();
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
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:pembimbing,penguji',
            'percentage' => 'required|numeric|min:0|max:100'
        ]);

        $component = CapstoneScoreComponent::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil ditambahkan.',
            'data' => $component
        ]);
    }

    public function updateComponent(Request $request, $id)
    {
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:pembimbing,penguji',
            'percentage' => 'required|numeric|min:0|max:100'
        ]);

        $component = CapstoneScoreComponent::findOrFail($id);
        $component->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil diperbarui.',
            'data' => $component
        ]);
    }

    public function destroyComponent($id)
    {
        $this->authorizeCapstoneAdmin();
        $component = CapstoneScoreComponent::findOrFail($id);
        abort_if($component->studentScores()->exists(), 409, 'Komponen sudah digunakan pada nilai mahasiswa dan tidak dapat dihapus agar riwayat nilai tetap terjaga.');
        $component->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Komponen nilai berhasil dihapus.'
        ]);
    }

    // --- INPUT & UNDUH NILAI MAHASISWA OLEH DOSEN ---
    public function storeStudentScores(Request $request, $groupId)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:users,id',
            'scores.*.component_id' => 'required|exists:capstone_score_components,id',
            'scores.*.score' => 'required|numeric|min:0|max:100',
        ]);

        $dosen = $this->capstoneDosen($request);
        abort_unless($dosen, 403);
        $dosenId = $dosen->id_dosen;

        $group = CapstoneGroup::with('members')
            ->whereKey($groupId)
            ->where(fn ($query) => $query->where('supervisor_id', $dosenId)->orWhere('examiner_id', $dosenId))
            ->firstOrFail();
        $studentIds = collect([$group->leader_id])
            ->merge($group->members->where('status', 'accepted')->pluck('student_id'))
            ->unique()
            ->values();
        abort_if(collect($validated['scores'])->contains(fn ($item) => ! $studentIds->contains((int) $item['student_id'])), 422, 'Nilai hanya dapat diberikan kepada mahasiswa di kelompok ini.');
        $category = $group->supervisor_id === $dosenId ? 'pembimbing' : 'penguji';
        $componentIds = CapstoneScoreComponent::where('category', $category)->pluck('id');
        abort_if(collect($validated['scores'])->contains(fn ($item) => ! $componentIds->contains((int) $item['component_id'])), 422, 'Komponen nilai tidak sesuai dengan peran dosen pada kelompok ini.');

        DB::transaction(function () use ($validated, $groupId, $dosenId) {
            foreach ($validated['scores'] as $item) {
                CapstoneStudentScore::updateOrCreate(
                    [
                        'capstone_group_id' => $groupId,
                        'student_id' => $item['student_id'],
                        'component_id' => $item['component_id'],
                        'evaluator_id' => $dosenId,
                    ],
                    ['score' => $item['score']]
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai komponen mahasiswa berhasil disimpan ke sistem.'
        ]);
    }

    public function updateGroupTitle(Request $request, $groupId)
    {
        $validated = $request->validate(['title' => 'required|string|max:255']);
        $dosen = $this->capstoneDosen($request);
        abort_unless($dosen, 403);
        $group = CapstoneGroup::whereKey($groupId)->where('supervisor_id', $dosen->id_dosen)->firstOrFail();
        $titleChanged = $group->title !== $validated['title'];
        $group->update(['title' => $validated['title']]);
        if ($titleChanged) {
            $group->update([
                'acc_coordinator' => false,
                'acc_supervisor' => false,
                'acc_kps' => false,
                'final_status' => 'proses',
                'exam_invitation_sent_at' => null,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Judul Capstone berhasil disimpan oleh dosen pembimbing.']);
    }

    public function approveFinal(Request $request, $groupId)
    {
        $validated = $request->validate(['approved' => 'sometimes|boolean']);
        $dosen = $this->capstoneDosen($request);
        abort_unless($dosen, 403);
        $group = CapstoneGroup::whereKey($groupId)->where('supervisor_id', $dosen->id_dosen)->firstOrFail();
        $approved = (bool) ($validated['approved'] ?? true);
        abort_if($approved && ! $group->final_report_file, 409, 'Laporan final Capstone harus diunggah sebelum ACC dosen pembimbing.');
        abort_if($approved && ! $group->studentScores()->exists(), 409, 'Nilai Capstone harus diinput sebelum ACC dosen pembimbing.');
        $role = Auth::user()->hasRole('Super Admin')
            ? 'Dosen Pembimbing (diwakilkan Super Admin)'
            : 'Dosen Pembimbing';
        $changes = ['acc_supervisor' => $approved];
        if ($approved) {
            $changes += [
                'supervisor_approved_by_role' => $role,
                'supervisor_approved_by_user_id' => Auth::id(),
                'supervisor_approved_at' => now(),
                'approved_by_role' => $role,
                'approved_by_user_id' => Auth::id(),
            ];
        }
        $group->update($changes);
        $group->refresh();
        $group->update([
            'final_status' => $group->acc_coordinator && $group->acc_supervisor && $group->acc_kps && $group->final_report_file
                ? 'selesai'
                : 'proses',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Status ACC dosen pembimbing berhasil diperbarui.', 'acc_supervisor' => $approved]);
    }

    public function downloadScores($groupId)
    {
        $dosen = $this->capstoneDosen(request());
        abort_unless($dosen, 403);
        $group = CapstoneGroup::with(['studentScores.student', 'studentScores.component', 'supervisor', 'examiner'])
            ->whereKey($groupId)
            ->where(fn ($query) => $query->where('supervisor_id', $dosen->id_dosen)->orWhere('examiner_id', $dosen->id_dosen))
            ->firstOrFail();

        return view('capstone.dosen.scores', compact('group'));
    }

    public function exportScores($groupId)
    {
        $dosen = $this->capstoneDosen(request());
        abort_unless($dosen, 403);
        CapstoneGroup::query()
            ->whereKey($groupId)
            ->where(fn ($query) => $query->where('supervisor_id', $dosen->id_dosen)->orWhere('examiner_id', $dosen->id_dosen))
            ->firstOrFail();

        return Excel::download(new CapstoneGroupScoresExport((int) $groupId), 'nilai-capstone-kelompok-' . $groupId . '.xlsx');
    }
}
