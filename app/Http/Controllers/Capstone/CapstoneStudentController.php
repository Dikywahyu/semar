<?php

namespace App\Http\Controllers\Capstone;

use App\Http\Controllers\Controller;
use App\Models\Capstone\CapstoneGroup;
use App\Models\Capstone\CapstoneGroupMember;
use App\Models\Capstone\CapstoneExpertise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Capstone\Concerns\ResolvesCapstoneProfiles;

class CapstoneStudentController extends Controller
{
    use ResolvesCapstoneProfiles;

    // Menampilkan halaman / data dashboard mahasiswa
    public function index(Request $request)
    {
        $student = $this->capstoneStudent($request);

        if (! $student && $this->canChooseCapstoneProfile()) {
            return view('capstone.profile_selector', [
                'jenis' => 'mahasiswa',
                'profiles' => $this->capstoneStudents()->get(),
                'route' => route('capstone.mahasiswa.index'),
                'parameter' => 'student_user_id',
                'title' => 'Pilih Profil Mahasiswa',
                'description' => 'Akun ini tidak terhubung ke profil mahasiswa. Pilih mahasiswa untuk membuka Capstone sebagai mahasiswa.',
                'label' => fn(User $profile) => $profile->name . ($profile->identifier_number ? ' (' . $profile->identifier_number . ')' : ''),
            ]);
        }

        abort_unless($student, 403, 'Profil mahasiswa aktif untuk akun ini belum tersedia.');
        $userId = $student->id;
        $group = CapstoneGroup::with(['expertise', 'supervisor', 'examiner', 'members.student', 'approver', 'coordinatorApprover', 'supervisorApprover', 'kpsApprover', 'kpiApprover'])
            ->where(function ($query) use ($userId) {
                $query->where('leader_id', $userId)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('student_id', $userId)->where('status', 'accepted'));
            })->first();

        $invitations = CapstoneGroupMember::with(['group.leader', 'group.expertise'])
            ->where('student_id', $userId)
            ->where('status', 'pending')
            ->get();

        $expertises = CapstoneExpertise::all();
        $availableStudents = User::role(['mahasiswa', 'Mahasiswa'])
            ->where('id', '!=', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'identifier_number']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'my_group' => $group,
                'invitations' => $invitations
            ]);
        }

        return view('capstone.mahasiswa.index', compact('group', 'invitations', 'expertises', 'availableStudents', 'student'));
    }

    // 1. Pendaftaran & Update Kelompok & Keahlian
    public function storeGroup(Request $request)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'expertise_id' => 'required|exists:capstone_expertises,id',
            'members' => 'nullable|array|max:2',
            'members.*' => 'distinct|exists:users,id',
            'group_id' => 'nullable|exists:capstone_groups,id' // Validasi group_id untuk proses update
        ]);

        $memberIds = $validated['members'] ?? [];
        abort_if(in_array((string) $student->id, array_map('strval', $memberIds), true), 422, 'Ketua tidak dapat menjadi anggota tambahan.');
        $group = DB::transaction(function () use ($validated, $memberIds, $student) {
            $group = null;
            if (! empty($validated['group_id'])) {
                $group = CapstoneGroup::where('id', $validated['group_id'])
                    ->where('leader_id', $student->id)
                    ->whereIn('status_pendaftaran', ['draft', 'submitted'])
                    ->where('kpi_approval', 'pending')
                    ->where('acc_coordinator', false)
                    ->where('acc_supervisor', false)
                    ->where('acc_kps', false)
                    ->whereNull('exam_date')
                    ->whereDoesntHave('members', fn ($query) => $query->where('status', 'accepted'))
                    ->firstOrFail();
            } else {
                abort_if(
                    CapstoneGroup::where('leader_id', $student->id)
                        ->orWhereHas('members', fn ($query) => $query->where('student_id', $student->id)->where('status', 'accepted'))
                        ->exists(),
                    409,
                    'Anda sudah terdaftar dalam kelompok Capstone.'
                );
            }

            foreach ($memberIds as $memberId) {
                abort_if(
                    CapstoneGroup::where('leader_id', $memberId)
                        ->orWhereHas('members', fn ($query) => $query->where('student_id', $memberId)->where('status', 'accepted'))
                        ->exists(),
                    409,
                    'Salah satu mahasiswa sudah tergabung di kelompok lain.'
                );
            }

            $attributes = [
                'title' => $validated['title'] ?? null,
                'expertise_id' => $validated['expertise_id'],
                'status_pendaftaran' => 'submitted',
            ];
            if ($group) {
                $group->update($attributes);
            } else {
                $group = CapstoneGroup::create($attributes + ['leader_id' => $student->id]);
            }

            CapstoneGroupMember::where('capstone_group_id', $group->id)->delete();
            foreach ($memberIds as $memberId) {
                CapstoneGroupMember::create([
                    'capstone_group_id' => $group->id,
                    'student_id' => $memberId,
                    'status' => 'pending',
                ]);
            }

            return $group;
        });

        return response()->json([
            'status' => 'success',
            'message' => ! empty($validated['group_id']) ? 'Ajuan kelompok berhasil diperbarui.' : 'Kelompok berhasil didaftarkan.',
            'data' => $group,
        ]);
    }

    // 2. Respon Undangan Anggota (Setuju / Tolak)
    public function responseInvitation(Request $request, $memberId)
    {
        $request->validate(['status' => 'required|in:accepted,rejected']);
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        DB::transaction(function () use ($memberId, $student, $request) {
            $member = CapstoneGroupMember::where('id', $memberId)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($member->status === 'pending', 409, 'Undangan ini sudah pernah direspons.');
            if ($request->input('status') === 'accepted') {
                abort_if(
                    CapstoneGroup::where('leader_id', $student->id)
                        ->orWhereHas('members', fn ($query) => $query->where('student_id', $student->id)->where('status', 'accepted'))
                        ->exists(),
                    409,
                    'Anda sudah menjadi anggota kelompok lain.'
                );
                $group = CapstoneGroup::whereKey($member->capstone_group_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_if(
                    $group->members()->where('status', 'accepted')->count() >= 2,
                    409,
                    'Kelompok sudah mencapai batas tiga mahasiswa termasuk ketua.'
                );
            }

            $member->update(['status' => $request->input('status')]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Respon undangan berhasil disimpan.'
        ]);
    }

    // 3. Upload Berkas Pelaksanaan (Kartu Konsultasi, Draft Laporan, Tanggal Ujian)
    public function uploadExecution(Request $request, $groupId)
    {
        // Fungsi ini bisa tetap dipertahankan jika diakses dari modul/rute lain
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);
        $group = CapstoneGroup::where('id', $groupId)->where('leader_id', $student->id)->firstOrFail();

        $validated = $request->validate([
            'consultation_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'draft_report' => 'nullable|file|mimes:pdf|max:5120',
            'exam_date' => 'nullable|date',
        ]);

        $data = [];
        if ($request->hasFile('consultation_card')) {
            if ($group->consultation_card_file) {
                Storage::disk('public')->delete($group->consultation_card_file);
            }
            $data['consultation_card_file'] = $request->file('consultation_card')->store('capstone/consultation', 'public');
        }
        if ($request->hasFile('draft_report')) {
            if ($group->draft_report_file) {
                Storage::disk('public')->delete($group->draft_report_file);
            }
            $data['draft_report_file'] = $request->file('draft_report')->store('capstone/drafts', 'public');
        }
        if ($request->filled('exam_date')) {
            $data['proposed_exam_date'] = $validated['exam_date'];
            $data['exam_date'] = null;
            $data['exam_time'] = null;
            $data['exam_room'] = null;
            $data['exam_invitation_sent_at'] = null;
        }

        $group->update($data);
        if ($request->hasFile('consultation_card')) {
            $this->invalidateApprovalsForDocument($group, 'consultation_card');
        }
        if ($request->hasFile('draft_report')) {
            $this->invalidateApprovalsForDocument($group, 'draft_report');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas pelaksanaan berhasil diunggah.',
            'data' => $group
        ]);
    }

    // 4. Upload Berkas Sidang Dinamis & Cek ACC Multi-Pihak
    public function uploadSidang(Request $request, $groupId)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        // PERBAIKAN: Cari kelompok yang mana user adalah Ketua ATAU Anggota yang sudah ACC
        $group = CapstoneGroup::where('id', $groupId)
            ->where(function ($query) use ($student) {
                $query->where('leader_id', $student->id)
                    ->orWhereHas('members', function ($q) use ($student) {
                        $q->where('student_id', $student->id)
                            ->where('status', 'accepted');
                    });
            })->firstOrFail();

        // Validasi format file_type dari dropdown yang ada di blade
        $request->validate([
            'file_type' => 'required|in:consultation_card,draft_report,berita_acara,lembar_revisi,final_report,recap_score,agreement,drawings,rab,structural_calculation',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,dwg,dxf,xlsx,xls,csv,zip|max:20480',
        ]);

        $file = $request->file('document_file');
        $type = $request->file_type;

        // Pemetaan nama kolom dan folder penyimpanan
        $columnMap = [
            'consultation_card' => 'consultation_card_file',
            'draft_report'      => 'draft_report_file',
            'berita_acara'      => 'berita_acara_file',
            'lembar_revisi'     => 'lembar_revisi_file',
            'final_report'      => 'final_report_file',
            'recap_score'       => 'recap_score_file',
            'agreement' => 'agreement_file',
            'drawings' => 'drawings_file',
            'rab' => 'rab_file',
            'structural_calculation' => 'structural_calculation_file',
        ];

        $folderMap = [
            'consultation_card' => 'capstone/consultation',
            'draft_report'      => 'capstone/drafts',
            'berita_acara'      => 'capstone/berita_acara',
            'lembar_revisi'     => 'capstone/revisi',
            'final_report'      => 'capstone/final',
            'recap_score'       => 'capstone/recap',
            'agreement' => 'capstone/agreement',
            'drawings' => 'capstone/drawings',
            'rab' => 'capstone/rab',
            'structural_calculation' => 'capstone/structural-calculation',
        ];

        $column = $columnMap[$type];
        $folder = $folderMap[$type];

        // Update path file ke dalam database kelompok
        if ($group->$column) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($group->$column);
        }
        $group->update([$column => $file->store($folder, 'public')]);
        $this->invalidateApprovalsForDocument($group, $type);

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas pilihan berhasil diunggah.',
            'acc_status' => [
                'coordinator' => $group->acc_coordinator,
                'supervisor' => $group->acc_supervisor,
                'kps' => $group->acc_kps,
                'final_status' => $group->final_status
            ]
        ]);
    }

    public function proposeExamSchedule(Request $request, $groupId)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);
        $validated = $request->validate([
            'exam_date' => 'required|date|after:today',
            'exam_time' => 'required|date_format:H:i',
        ]);
        $group = CapstoneGroup::whereKey($groupId)
            ->where(function ($query) use ($student) {
                $query->where('leader_id', $student->id)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('student_id', $student->id)->where('status', 'accepted'));
            })->firstOrFail();
        abort_unless($group->supervisor_id && $group->kpi_approval === 'approved', 409, 'Usulan jadwal memerlukan dosen pembimbing dan persetujuan KPI.');
        $group->update([
            'proposed_exam_date' => $validated['exam_date'],
            'proposed_exam_time' => $validated['exam_time'],
            'exam_date' => null,
            'exam_time' => null,
            'exam_room' => null,
            'exam_invitation_sent_at' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Usulan jadwal ujian berhasil dikirim ke Admin.']);
    }

    public function deletePendingGroup(Request $request, $groupId)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);
        $group = CapstoneGroup::whereKey($groupId)
            ->where('leader_id', $student->id)
            ->whereIn('status_pendaftaran', ['draft', 'submitted'])
            ->where('kpi_approval', 'pending')
            ->where('acc_coordinator', false)
            ->where('acc_supervisor', false)
            ->where('acc_kps', false)
            ->where('final_status', '!=', 'selesai')
            ->whereDoesntHave('members', fn ($query) => $query->where('status', 'accepted'))
            ->firstOrFail();
        abort_if($group->exam_date || $group->exam_room, 409, 'Ajuan yang sudah dijadwalkan tidak dapat dihapus oleh mahasiswa.');
        foreach (['consultation_card_file', 'draft_report_file', 'berita_acara_file', 'lembar_revisi_file', 'final_report_file', 'recap_score_file', 'agreement_file', 'drawings_file', 'rab_file', 'structural_calculation_file'] as $field) {
            if ($group->$field) {
                Storage::disk('public')->delete($group->$field);
            }
        }
        $group->delete();

        return response()->json(['status' => 'success', 'message' => 'Ajuan Capstone berhasil dihapus.']);
    }

    // 5. Reset / Pilih Ulang Profil Mahasiswa (Untuk Super Admin)
    public function resetProfile(Request $request)
    {
        // Menghapus HANYA id mahasiswa dari dalam session 'capstone'
        $request->session()->forget('capstone.student_user_id');

        // Redirect kembali ke index agar form pemilihan profil mahasiswa muncul kembali
        return redirect()->route('capstone.mahasiswa.index');
    }

    // 6. Hapus Berkas Sidang
    public function deleteSidangFile(Request $request, $groupId, $fileType)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        $group = CapstoneGroup::where('id', $groupId)
            ->where(function ($query) use ($student) {
                $query->where('leader_id', $student->id)
                    ->orWhereHas('members', function ($q) use ($student) {
                        $q->where('student_id', $student->id)
                            ->where('status', 'accepted');
                    });
            })->firstOrFail();

        // Pemetaan nama kolom
        $columnMap = [
            'consultation_card' => 'consultation_card_file',
            'draft_report'      => 'draft_report_file',
            'berita_acara'      => 'berita_acara_file',
            'lembar_revisi'     => 'lembar_revisi_file',
            'final_report'      => 'final_report_file',
            'recap_score'       => 'recap_score_file',
            'agreement' => 'agreement_file',
            'drawings' => 'drawings_file',
            'rab' => 'rab_file',
            'structural_calculation' => 'structural_calculation_file',
        ];

        if (!array_key_exists($fileType, $columnMap)) {
            return response()->json(['status' => 'error', 'message' => 'Jenis berkas tidak valid.'], 400);
        }

        $column = $columnMap[$fileType];

        if ($group->$column) {
            // Hapus file fisik dari storage
            \Illuminate\Support\Facades\Storage::disk('public')->delete($group->$column);

            // Hapus path dari database
            $group->update([$column => null]);
            $this->invalidateApprovalsForDocument($group, $fileType);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas berhasil dihapus.'
        ]);
    }

    private function invalidateApprovalsForDocument(CapstoneGroup $group, string $fileType): void
    {
        if (in_array($fileType, ['draft_report', 'consultation_card'], true)) {
            $group->update([
                'kpi_approval' => 'pending',
                'proposed_exam_date' => null,
                'proposed_exam_time' => null,
                'exam_date' => null,
                'exam_time' => null,
                'exam_room' => null,
                'exam_invitation_sent_at' => null,
            ]);
        }

        if ($fileType === 'final_report') {
            $group->update([
                'acc_coordinator' => false,
                'acc_supervisor' => false,
                'acc_kps' => false,
                'final_status' => 'proses',
            ]);
        }
    }
}
