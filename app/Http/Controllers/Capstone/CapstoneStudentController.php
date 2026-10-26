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

        return view('capstone.mahasiswa.index', compact('group', 'invitations', 'expertises', 'availableStudents', 'student'));
    }

    // 1. Pendaftaran & Update Kelompok & Keahlian
    public function storeGroup(Request $request)
    {
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'expertise_id' => 'required|exists:capstone_expertises,id',
            'members' => 'nullable|array|max:2', // Jadikan nullable agar bisa submit tanpa anggota tambahan
            'members.*' => 'exists:users,id',
            'group_id' => 'nullable|exists:capstone_groups,id' // Validasi group_id untuk proses update
        ]);

        DB::beginTransaction();
        try {
            // CEK APAKAH INI PROSES UPDATE ATAU BUAT BARU
            if ($request->filled('group_id')) {
                // Proses Update
                $group = CapstoneGroup::where('id', $request->group_id)
                    ->where('leader_id', $student->id)
                    ->firstOrFail();

                $group->update([
                    'title' => $request->title,
                    'expertise_id' => $request->expertise_id,
                ]);

                $message = 'Ajuan kelompok berhasil diperbarui!';
            } else {
                // Proses Buat Baru
                $group = CapstoneGroup::create([
                    'leader_id' => $student->id,
                    'title' => $request->title,
                    'expertise_id' => $request->expertise_id,
                    'status_pendaftaran' => 'submitted'
                ]);

                $message = 'Kelompok dan keahlian berhasil didaftarkan!';
            }

            // SINKRONISASI ANGGOTA KELOMPOK
            // Hapus anggota lama terlebih dahulu untuk memastikan data selalu sinkron dengan form
            CapstoneGroupMember::where('capstone_group_id', $group->id)->delete();

            // Masukkan anggota baru (jika ada yang dipilih)
            if ($request->has('members') && is_array($request->members)) {
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
                'message' => $message,
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
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);

        $member = CapstoneGroupMember::where('id', $memberId)
            ->where('student_id', $student->id)
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
        // Fungsi ini bisa tetap dipertahankan jika diakses dari modul/rute lain
        $student = $this->capstoneStudent($request);
        abort_unless($student, 403);
        $group = CapstoneGroup::where('id', $groupId)->where('leader_id', $student->id)->firstOrFail();

        $request->validate([
            'consultation_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
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
            'file_type' => 'required|in:consultation_card,draft_report,berita_acara,lembar_revisi,final_report,recap_score',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
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
        ];

        $folderMap = [
            'consultation_card' => 'capstone/consultation',
            'draft_report'      => 'capstone/drafts',
            'berita_acara'      => 'capstone/berita_acara',
            'lembar_revisi'     => 'capstone/revisi',
            'final_report'      => 'capstone/final',
            'recap_score'       => 'capstone/recap',
        ];

        $column = $columnMap[$type];
        $folder = $folderMap[$type];

        // Update path file ke dalam database kelompok
        $group->update([
            $column => $file->store($folder, 'public')
        ]);

        // Otomatis ubah status final menjadi selesai jika semua pihak telah ACC
        if ($group->acc_coordinator && $group->acc_supervisor && $group->acc_kps) {
            $group->update(['final_status' => 'selesai']);
        }

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
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas berhasil dihapus.'
        ]);
    }
}
