<?php

namespace App\Http\Controllers\Capstone;

use App\Exports\CapstonePlottingExport;
use App\Exports\CapstoneGradeRecapExport;
use App\Imports\CapstonePlottingImport;
use App\Mail\CapstoneExamInvitation;
use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneExpertise;
use App\Models\Capstone\CapstoneScoreComponent;
use App\Models\Dosen\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CapstoneAdminController extends Controller
{
    private function authorizeCapstoneAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'KPS', 'Kaprodi']), 403);
    }

    // 1. Index Monitoring Admin / KPS
    public function index(Request $request)
    {
        $this->authorizeCapstoneAdmin();
        $query = CapstoneGroup::with(['leader', 'expertise', 'supervisor', 'examiner', 'members.student', 'coordinatorApprover', 'supervisorApprover', 'kpsApprover', 'kpiApprover']);

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
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'exam_room' => 'required|string|max:100',
            'exam_date' => 'nullable|date|after_or_equal:today',
            'exam_time' => 'nullable|date_format:H:i',
        ]);

        $group = CapstoneGroup::findOrFail($groupId);
        abort_unless($group->proposed_exam_date && $group->proposed_exam_time, 409, 'Mahasiswa harus mengusulkan tanggal dan waktu ujian terlebih dahulu.');
        $examDate = $validated['exam_date'] ?? $group->proposed_exam_date;
        $examTime = $validated['exam_time'] ?? $group->proposed_exam_time;
        abort_unless($examDate && $examTime, 422, 'Mahasiswa harus mengusulkan tanggal dan waktu ujian terlebih dahulu.');
        $group->update([
            'exam_date' => $examDate,
            'exam_time' => $examTime,
            'exam_room' => $validated['exam_room'],
            'exam_invitation_sent_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ruang ujian berhasil ditetapkan oleh Admin.'
        ]);
    }

    public function setKpsApproval(Request $request, $groupId)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi']), 403);
        $validated = $request->validate(['approved' => 'required|boolean']);
        $group = CapstoneGroup::findOrFail($groupId);
        $approved = (bool) $validated['approved'];
        abort_if($approved && (! $group->final_report_file || ! $group->acc_coordinator || ! $group->acc_supervisor), 409, 'ACC KPS hanya dapat diberikan setelah laporan final serta ACC Koordinator dan Dosen Pembimbing lengkap.');
        $isSuperAdmin = auth()->user()->hasRole('Super Admin');

        $role = $isSuperAdmin ? 'KPS (diwakilkan Super Admin)' : 'KPS';
        $changes = ['acc_kps' => $approved];
        if ($approved) {
            $changes += [
                'kps_approved_by_role' => $role,
                'kps_approved_by_user_id' => auth()->id(),
                'kps_approved_at' => now(),
                'approved_by_role' => $role,
                'approved_by_user_id' => auth()->id(),
            ];
        }
        $group->update($changes);
        $this->refreshFinalStatus($group);

        return response()->json(['status' => 'success', 'message' => 'Status ACC KPS berhasil diperbarui.', 'acc_kps' => $group->fresh()->acc_kps]);
    }

    public function storeManualPlotting(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);
        $validated = $request->validate([
            'leader_id' => 'required|exists:users,id',
            'expertise_id' => 'required|exists:capstone_expertises,id',
            'supervisor_id' => 'nullable|exists:dosens,id_dosen',
            'examiner_id' => 'nullable|exists:dosens,id_dosen',
            'project_location' => 'nullable|string|max:255',
        ]);
        $group = CapstoneGroup::create($validated + [
            'status_pendaftaran' => 'submitted',
            'title' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Plotting Capstone berhasil ditambahkan.', 'data' => $group]);
    }

    public function updatePlotting(Request $request, $groupId)
    {
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'supervisor_id' => 'present|nullable|exists:dosens,id_dosen',
            'examiner_id' => 'present|nullable|exists:dosens,id_dosen',
            'project_location' => 'present|nullable|string|max:255',
        ]);
        $group = CapstoneGroup::findOrFail($groupId);
        $supervisorChanged = $group->supervisor_id !== ($validated['supervisor_id'] ?? null);
        $group->update($validated);
        if ($supervisorChanged) {
            $group->update([
                'acc_supervisor' => false,
                'acc_kps' => false,
                'final_status' => 'proses',
                'exam_invitation_sent_at' => null,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Plotting lokasi dan dosen berhasil diperbarui.']);
    }

    public function importPlotting(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        Excel::import(new CapstonePlottingImport(), $request->file('file'));

        return response()->json(['status' => 'success', 'message' => 'Data plotting berhasil diimpor.']);
    }

    public function exportPlotting()
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);

        return Excel::download(new CapstonePlottingExport(), 'plotting-capstone.xlsx');
    }

    public function deleteGroup($groupId)
    {
        $this->authorizeCapstoneAdmin();
        $group = CapstoneGroup::with('members')->findOrFail($groupId);
        foreach (['consultation_card_file', 'draft_report_file', 'berita_acara_file', 'lembar_revisi_file', 'final_report_file', 'recap_score_file', 'agreement_file', 'drawings_file', 'rab_file', 'structural_calculation_file'] as $field) {
            if ($group->$field) {
                Storage::disk('public')->delete($group->$field);
            }
        }
        $group->delete();

        return response()->json(['status' => 'success', 'message' => 'Ajuan Capstone berhasil dihapus.']);
    }

    public function invitation($groupId)
    {
        $this->authorizeCapstoneAdmin();
        $group = CapstoneGroup::with(['leader', 'members.student', 'supervisor', 'examiner', 'expertise'])->findOrFail($groupId);
        abort_unless($group->exam_date && $group->exam_time && $group->exam_room, 409, 'Jadwal ujian belum lengkap.');

        return view('capstone.invitation', compact('group'));
    }

    public function sendInvitation($groupId)
    {
        $this->authorizeCapstoneAdmin();
        $group = CapstoneGroup::with(['leader', 'members.student', 'supervisor', 'examiner', 'expertise'])->findOrFail($groupId);
        abort_unless($group->exam_date && $group->exam_time && $group->exam_room, 409, 'Jadwal ujian belum lengkap.');
        $recipients = collect([$group->supervisor?->email, $group->examiner?->email])->filter()->unique()->values()->all();
        abort_if($recipients === [], 422, 'Email dosen pembimbing/penguji belum tersedia.');
        Mail::to($recipients)->send(new CapstoneExamInvitation($group));
        $group->update(['exam_invitation_sent_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Undangan ujian berhasil dikirim.']);
    }

    public function printDocument($groupId, $type)
    {
        $this->authorizeCapstoneAdmin();
        abort_unless(in_array($type, ['berita-acara', 'kesepakatan', 'sk'], true), 404);
        abort_unless($type !== 'sk' || auth()->user()->hasRole('Super Admin'), 403);
        $group = CapstoneGroup::with(['leader', 'members.student', 'supervisor', 'examiner', 'expertise'])->findOrFail($groupId);

        return view('capstone.print_document', compact('group', 'type'));
    }

    public function gradeRecap(Request $request)
    {
        $this->authorizeCapstoneAdmin();
        if ($request->boolean('export')) {
            return Excel::download(
                new CapstoneGradeRecapExport($request->filled('expertise_id') ? $request->integer('expertise_id') : null),
                'rekap-nilai-capstone.xlsx'
            );
        }
        $groups = CapstoneGroup::with(['leader', 'expertise', 'supervisor', 'studentScores.student', 'studentScores.component'])
            ->when($request->filled('expertise_id'), fn ($query) => $query->where('expertise_id', $request->input('expertise_id')))
            ->whereHas('studentScores')
            ->get();

        return view('capstone.admin.grade_recap', [
            'groups' => $groups,
            'expertises' => CapstoneExpertise::orderBy('name')->get(),
            'export' => $request->boolean('export'),
        ]);
    }

    private function refreshFinalStatus(CapstoneGroup $group): void
    {
        $group->refresh();
        $group->update([
            'final_status' => $group->acc_coordinator && $group->acc_supervisor && $group->acc_kps && $group->final_report_file
                ? 'selesai'
                : 'proses',
        ]);
    }

    // --- MASTER KOMPONEN NILAI (YANG SEBELUMNYA BELUM MUNCUL) ---

    // 3. Tampilkan Halaman atau Data Master Komponen Nilai
    public function komponenIndex(Request $request)
    {
        $this->authorizeCapstoneAdmin();
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

    // 5. Perbarui Komponen Nilai
    public function updateKomponen(Request $request, $id)
    {
        $this->authorizeCapstoneAdmin();
        CapstoneScoreComponent::findOrFail($id);
        abort(409, 'Komponen nilai yang sudah dibuat tidak dapat diubah.');
    }

    // 6. Hapus Komponen Nilai
    public function destroyKomponen($id)
    {
        $this->authorizeCapstoneAdmin();
        CapstoneScoreComponent::findOrFail($id);
        abort(409, 'Komponen nilai tidak dapat dihapus.');
    }

    // --- MASTER KEAHLIAN (EXPERTISES) ---

    // Tampilkan Halaman Master Keahlian
    public function expertiseIndex(Request $request)
    {
        $this->authorizeCapstoneAdmin();
        $expertises = CapstoneExpertise::with(['coordinator', 'lecturers'])->orderBy('id')->get();
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
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinator_id' => 'nullable|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil ditambahkan.',
            'data' => $expertise
        ]);
    }

    // Perbarui Keahlian (Edit)
    public function updateExpertise(Request $request, $id)
    {
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coordinator_id' => 'nullable|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::findOrFail($id);
        $this->abortIfProtectedExpertise($expertise);
        $expertise->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil diperbarui.',
            'data' => $expertise
        ]);
    }

    // Hapus Keahlian
    public function destroyExpertise($id)
    {
        $this->authorizeCapstoneAdmin();
        DB::transaction(function () use ($id) {
            $expertise = CapstoneExpertise::lockForUpdate()->findOrFail($id);
            $this->abortIfProtectedExpertise($expertise);
            $expertise->groups()->update(['expertise_id' => null]);
            $expertise->lecturers()->detach();
            $expertise->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Bidang keahlian berhasil dihapus.'
        ]);
    }

    // --- MANAJEMEN DOSEN PADA KEAHLIAN (PIVOT) ---

    // Ambil daftar dosen yang terhubung ke suatu keahlian
    public function GetExpertiseLecturers($expertiseId)
    {
        $this->authorizeCapstoneAdmin();
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
        $this->authorizeCapstoneAdmin();
        $validated = $request->validate([
            'lecturer_id' => 'required|exists:dosens,id_dosen'
        ]);

        $expertise = CapstoneExpertise::findOrFail($expertiseId);
        $this->abortIfProtectedExpertise($expertise);

        // Cek apakah dosen sudah terdaftar di keahlian ini
        if (!$expertise->lecturers()->where('lecturer_id', $validated['lecturer_id'])->exists()) {
            $expertise->lecturers()->attach($validated['lecturer_id']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dosen berhasil ditambahkan ke bidang keahlian ini.'
        ]);
    }

    // Hapus dosen dari bidang keahlian
    public function destroyExpertiseLecturer($expertiseId, $lecturerId)
    {
        $this->authorizeCapstoneAdmin();
        $expertise = CapstoneExpertise::findOrFail($expertiseId);
        $this->abortIfProtectedExpertise($expertise);
        $expertise->lecturers()->detach($lecturerId);

        return response()->json([
            'status' => 'success',
            'message' => 'Dosen berhasil dihapus dari bidang keahlian ini.'
        ]);
    }

    private function abortIfProtectedExpertise(CapstoneExpertise $expertise): void
    {
        $protectedId = CapstoneExpertise::query()->min('id');
        abort_if(
            $protectedId !== null && (int) $expertise->getKey() === (int) $protectedId,
            409,
            'Bidang keahlian nomor 1 dilindungi dan tidak dapat diubah atau dihapus.'
        );
    }
}
