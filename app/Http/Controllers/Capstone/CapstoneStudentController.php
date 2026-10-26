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

class CapstoneStudentController extends Controller
{

    // Menampilkan halaman / data dashboard mahasiswa (Mendukung View & AJAX)
    public function index(Request $request)
    {
        $userId = Auth::id();
        $group = CapstoneGroup::with(['expertise', 'supervisor', 'examiner', 'members.student'])
            ->where('leader_id', $userId)
            ->orWhereHas('members', function ($q) use ($userId) {
                $q->where('student_id', $userId);
            })->first();

        $invitations = CapstoneGroupMember::with(['group.leader', 'group.expertise'])
            ->where('student_id', $userId)
            ->where('status', 'pending')
            ->get();

        $expertises = CapstoneExpertise::all();
        $availableStudents = User::role(['mahasiswa', 'Mahasiswa'])->where('id', '!=', $userId)->get();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'my_group' => $group,
                'invitations' => $invitations
            ]);
        }

        return view('capstone.mahasiswa.index', compact('group', 'invitations', 'expertises', 'availableStudents'));
    }

    // 1. Pendaftaran Kelompok & Keahlian
    public function storeGroup(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'expertise_id' => 'required|exists:capstone_expertises,id',
            'members' => 'array|max:2', // Maksimal 2 anggota tambahan
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $group = CapstoneGroup::create([
                'leader_id' => Auth::id(),
                'title' => $request->title,
                'expertise_id' => $request->expertise_id,
                'status_pendaftaran' => 'submitted'
            ]);

            if ($request->has('members')) {
                foreach ($request->members as $memberId) {
                    CapstoneGroupMember::create([
                        'capstone_group_id' => $group->id,
                        'student_id' => $memberId,
                        'status' => 'pending'
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Kelompok dan keahlian berhasil didaftarkan!',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 2. Respon Undangan Anggota (Setuju / Tolak)
    public function responseInvitation(Request $request, $memberId)
    {
        $request->validate(['status' => 'required|in:accepted,rejected']);

        $member = CapstoneGroupMember::where('id', $memberId)
            ->where('student_id', Auth::id())
            ->firstOrFail();

        $member->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Respon undangan berhasil disimpan.'
        ]);
    }

    // 3. Upload Berkas Pelaksanaan (Kartu Konsultasi, Draft Laporan, Tanggal Ujian)
    public function uploadExecution(Request $request, $groupId)
    {
        $group = CapstoneGroup::where('id', $groupId)->where('leader_id', Auth::id())->firstOrFail();

        $request->validate([
            'consultation_card' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'draft_report' => 'nullable|file|mimes:pdf|max:5120',
            'exam_date' => 'nullable|date',
        ]);

        $data = [];
        if ($request->hasFile('consultation_card')) {
            $data['consultation_card_file'] = $request->file('consultation_card')->store('capstone/consultation', 'public');
        }
        if ($request->hasFile('draft_report')) {
            $data['draft_report_file'] = $request->file('draft_report')->store('capstone/drafts', 'public');
        }
        if ($request->filled('exam_date')) {
            $data['exam_date'] = $request->exam_date;
        }

        $group->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas pelaksanaan berhasil diunggah.',
            'data' => $group
        ]);
    }

    // 4. Upload Berkas Sidang & Cek ACC Multi-Pihak
    public function uploadSidang(Request $request, $groupId)
    {
        $group = CapstoneGroup::where('id', $groupId)->where('leader_id', Auth::id())->firstOrFail();

        $request->validate([
            'berita_acara' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'lembar_revisi' => 'nullable|file|mimes:pdf|max:2048',
            'final_report' => 'nullable|file|mimes:pdf|max:10240',
            'recap_score' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $data = [];
        if ($request->hasFile('berita_acara')) $data['berita_acara_file'] = $request->file('berita_acara')->store('capstone/berita_acara', 'public');
        if ($request->hasFile('lembar_revisi')) $data['lembar_revisi_file'] = $request->file('lembar_revisi')->store('capstone/revisi', 'public');
        if ($request->hasFile('final_report')) $data['final_report_file'] = $request->file('final_report')->store('capstone/final', 'public');
        if ($request->hasFile('recap_score')) $data['recap_score_file'] = $request->file('recap_score')->store('capstone/recap', 'public');

        $group->update($data);

        // Otomatis ubah status final menjadi selesai jika semua pihak telah ACC
        if ($group->acc_coordinator && $group->acc_supervisor && $group->acc_kps) {
            $group->update(['final_status' => 'selesai']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas sidang berhasil diunggah.',
            'acc_status' => [
                'coordinator' => $group->acc_coordinator,
                'supervisor' => $group->acc_supervisor,
                'kps' => $group->acc_kps,
                'final_status' => $group->final_status
            ]
        ]);
    }
}
