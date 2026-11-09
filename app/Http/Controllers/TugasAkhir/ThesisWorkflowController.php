<?php

namespace App\Http\Controllers\TugasAkhir;

use App\Exports\ThesisAssignmentExport;
use App\Http\Controllers\Controller;
use App\Imports\ThesisAssignmentImport;
use App\Mail\ThesisExamInvitation;
use App\Models\Dosen\Dosen;
use App\Models\Semester;
use App\Models\TugasAkhir\ThesisApplication;
use App\Models\TugasAkhir\ThesisDocument;
use App\Models\TugasAkhir\ThesisScore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ThesisWorkflowController extends Controller
{
    private const P1_LIMIT = 10;

    private function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    private function authorizeKps(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi']), 403);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'KPS', 'Kaprodi']), 403);
    }

    private function authorizeCoordinator(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([
            'Super Admin', 'KPS', 'Kaprodi', 'Koordinator Skripsi', 'Koordinator TA', 'Koordinator Skripsi/TA',
        ]), 403);
    }

    private function activeSemester(): ?Semester
    {
        return Semester::query()->where('aktif', true)->orderByDesc('id')->first();
    }

    private function dosenForUser(?User $user): ?Dosen
    {
        if (! $user || ! $user->hasAnyRole([
            'dosen_pembimbing', 'dosen_penguji', 'dosen', 'Dosen', 'DosenLB', 'Guru Besar',
        ])) {
            return null;
        }

        return Dosen::query()->where('aktif', true)->where(function ($query) use ($user) {
            $query->when($user->email, fn ($builder) => $builder->where('email', $user->email))
                ->when($user->identifier_number, fn ($builder) => $builder->orWhere('nidn', $user->identifier_number));
        })->first();
    }

    private function applicationForStudent(Request $request, int $id): ThesisApplication
    {
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);

        return ThesisApplication::query()->whereKey($id)->where('student_id', $user->id)->firstOrFail();
    }

    private function currentApplications()
    {
        $semester = $this->activeSemester();

        return ThesisApplication::query()
            ->with([
                'student', 'semester', 'p1', 'p2', 'examiner', 'documents.verifier', 'scores.evaluator',
                'semproConsultationApprover', 'semproApprover', 'semhasCoordinatorVerifier',
                'semhasKpsVerifier', 'semhasApprover', 'taCoordinatorVerifier', 'taKpsVerifier',
                'taDraftVerifier', 'taFinalApprover',
            ])
            ->when($semester, fn ($query) => $query->where('semester_id', $semester->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->latest();
    }

    private function taInitialRequirementsVerified(ThesisApplication $application): bool
    {
        return ($application->ta_coordinator_verified || $application->ta_kps_verified)
            && $application->documents()
                ->whereIn('document_type', ['semhas_result', 'journal_submission'])
                ->where('verification_status', 'approved')
                ->count() === 2;
    }

    public function studentIndex(Request $request)
    {
        $user = auth()->user();
        $isStudent = $user?->hasAnyRole(['mahasiswa', 'Mahasiswa']) ?? false;
        abort_unless($isStudent || $this->isSuperAdmin(), 403);

        $studentId = $isStudent ? $user->id : $request->integer('student_id');
        if (! $studentId) {
            return view('tugas_akhir.profile_selector', [
                'kind' => 'mahasiswa',
                'profiles' => User::role(['mahasiswa', 'Mahasiswa'])->orderBy('name')->get(),
                'route' => route('thesis.student.index'),
                'parameter' => 'student_id',
                'title' => 'Pilih Profil Mahasiswa',
                'description' => 'Pilih profil untuk melihat progres Tugas Akhir. Akses Super Admin pada halaman ini bersifat hanya-baca.',
            ]);
        }

        $student = $isStudent ? $user : User::role(['mahasiswa', 'Mahasiswa'])->findOrFail($studentId);
        $readOnly = ! $isStudent;
        $semester = $this->activeSemester();
        $application = $semester
            ? ThesisApplication::with([
                'p1', 'p2', 'examiner', 'documents.verifier', 'scores.evaluator',
                'semproConsultationApprover', 'semproApprover', 'semhasCoordinatorVerifier',
                'semhasKpsVerifier', 'semhasApprover', 'taCoordinatorVerifier', 'taKpsVerifier',
                'taDraftVerifier', 'taFinalApprover',
            ])
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->first()
            : null;
        $activeP1 = Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get()
            ->map(function (Dosen $dosen) use ($semester) {
                $dosen->active_thesis_count = $semester
                    ? ThesisApplication::query()->where('semester_id', $semester->id)->where('p1_id', $dosen->id_dosen)->where('status', '!=', 'withdrawn')->count()
                    : 0;
                return $dosen;
            })->filter(fn (Dosen $dosen) => $dosen->active_thesis_count < self::P1_LIMIT);

        return view('tugas_akhir.student', compact('application', 'semester', 'activeP1', 'readOnly', 'student'));
    }

    public function submitProposal(Request $request)
    {
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $semester = $this->activeSemester();
        abort_unless($semester, 409, 'Belum ada semester aktif untuk pengajuan Tugas Akhir.');
        $validated = $request->validate([
            'expertise_theme' => ['required', 'string', 'max:255'],
            'p1_id' => ['required', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
        ]);

        $application = DB::transaction(function () use ($validated, $semester) {
            $dosen = Dosen::query()->where('aktif', true)->whereKey($validated['p1_id'])->lockForUpdate()->firstOrFail();
            $existing = ThesisApplication::query()
                ->where('semester_id', $semester->id)
                ->where('student_id', auth()->id())
                ->lockForUpdate()
                ->first();
            abort_if($existing, 409, 'Pengajuan untuk semester aktif sudah tersedia.');

            if (! $this->isSuperAdmin()) {
                $count = ThesisApplication::query()
                    ->where('semester_id', $semester->id)
                    ->where('p1_id', $dosen->id_dosen)
                    ->where('status', '!=', 'withdrawn')
                    ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                    ->count();
                abort_if($count >= self::P1_LIMIT, 409, 'Kuota Pembimbing 1 sudah penuh. Silakan pilih dosen lain.');
            }

            return ThesisApplication::create([
                'student_id' => auth()->id(),
                'semester_id' => $semester->id,
                'expertise_theme' => $validated['expertise_theme'],
                'p1_id' => $dosen->id_dosen,
                'status' => 'pengajuan_sempro',
            ]);
        });

        return response()->json(['status' => 'success', 'message' => 'Pengajuan Sempro berhasil disimpan.', 'id' => $application->id]);
    }

    public function adminIndex()
    {
        $this->authorizeAdmin();
        $applications = $this->currentApplications()->get();
        $semester = $this->activeSemester();
        $lecturers = Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get();
        $load = $lecturers->mapWithKeys(function (Dosen $dosen) use ($semester) {
            $query = ThesisApplication::query()->where('semester_id', $semester?->id);

            return [$dosen->id_dosen => [
                'advising' => (clone $query)->where(fn ($q) => $q->where('p1_id', $dosen->id_dosen)->orWhere('p2_id', $dosen->id_dosen))->count(),
                'examining' => (clone $query)->where('examiner_id', $dosen->id_dosen)->count(),
            ]];
        });
        $students = User::role(['mahasiswa', 'Mahasiswa'])->orderBy('name')->get();

        return view('tugas_akhir.admin', compact('applications', 'semester', 'lecturers', 'load', 'students'));
    }

    public function dosenIndex(Request $request)
    {
        $linkedDosen = $this->dosenForUser(auth()->user());
        $dosen = $linkedDosen;
        if (! $dosen && $this->isSuperAdmin() && $request->filled('id_dosen')) {
            $dosen = Dosen::query()->where('aktif', true)->findOrFail($request->input('id_dosen'));
        }
        if (! $dosen && $this->isSuperAdmin()) {
            return view('tugas_akhir.profile_selector', [
                'kind' => 'dosen',
                'profiles' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
                'route' => route('thesis.dosen.index'),
                'parameter' => 'id_dosen',
                'title' => 'Pilih Profil Dosen',
                'description' => 'Pilih profil untuk melihat penugasan dan nilai Tugas Akhir. Akses Super Admin pada halaman ini bersifat hanya-baca.',
            ]);
        }
        abort_unless($dosen, 403, 'Profil dosen aktif untuk akun ini tidak ditemukan.');
        $canAct = $linkedDosen && $linkedDosen->id_dosen === $dosen->id_dosen;
        $applications = ThesisApplication::with(['student', 'p1', 'p2', 'examiner', 'scores'])
            ->where(fn ($query) => $query->where('p1_id', $dosen->id_dosen)->orWhere('p2_id', $dosen->id_dosen)->orWhere('examiner_id', $dosen->id_dosen))
            ->latest()
            ->get();

        return view('tugas_akhir.dosen', compact('applications', 'dosen', 'canAct'));
    }

    public function updateTitle(Request $request, int $applicationId)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $dosen = $this->dosenForUser(auth()->user());
        abort_unless($dosen, 403, 'Profil dosen tidak terhubung ke akun ini.');
        $application = ThesisApplication::findOrFail($applicationId);
        abort_unless($application->p1_id === $dosen->id_dosen, 403, 'Hanya Pembimbing 1 yang dapat menetapkan judul Tugas Akhir.');
        $application->update(['title' => $validated['title']]);

        return response()->json(['status' => 'success', 'message' => 'Judul Tugas Akhir berhasil diperbarui.']);
    }

    public function verifyDashboard()
    {
        $this->authorizeCoordinator();
        $applications = $this->currentApplications()->get();

        return view('tugas_akhir.verify', compact('applications'));
    }

    public function assignPanel(Request $request, int $applicationId)
    {
        $this->authorizeKps();
        $validated = $request->validate([
            'p2_id' => ['nullable', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
            'examiner_id' => ['nullable', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
        ]);
        $application = ThesisApplication::findOrFail($applicationId);
        abort_if(($validated['p2_id'] ?? null) === $application->p1_id, 422, 'Pembimbing 1 dan Pembimbing 2 harus dosen yang berbeda.');
        abort_if(($validated['examiner_id'] ?? null) === $application->p1_id || ($validated['examiner_id'] ?? null) === ($validated['p2_id'] ?? null), 422, 'Dosen penguji harus berbeda dari dosen pembimbing.');
        abort_if($application->sempro_proposed_date || $application->sempro_exam_date || $application->scores()->exists(), 409, 'Panel tidak dapat diubah setelah usulan jadwal Sempro dikirim atau nilai mulai diinput.');
        $application->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Pembimbing 2 dan dosen penguji berhasil ditetapkan.']);
    }

    public function createManualProposal(Request $request)
    {
        abort_unless($this->isSuperAdmin(), 403);
        $semester = $this->activeSemester();
        abort_unless($semester, 409, 'Belum ada semester aktif.');
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'expertise_theme' => ['required', 'string', 'max:255'],
            'p1_id' => ['required', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
        ]);
        $student = User::findOrFail($validated['student_id']);
        abort_unless($student->hasAnyRole(['mahasiswa', 'Mahasiswa']), 422, 'Akun yang dipilih bukan akun mahasiswa.');
        $existing = ThesisApplication::query()
            ->where('semester_id', $semester->id)
            ->where('student_id', $validated['student_id'])
            ->first();
        abort_if($existing, 409, 'Pengajuan mahasiswa untuk semester aktif sudah tersedia.');
        $application = ThesisApplication::create([
            'student_id' => $validated['student_id'],
            'semester_id' => $semester->id,
            'expertise_theme' => $validated['expertise_theme'],
            'p1_id' => $validated['p1_id'],
            'status' => 'pengajuan_sempro',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Pengajuan manual dibuat (override kuota).', 'id' => $application->id]);
    }

    public function overrideP1(Request $request, int $applicationId)
    {
        abort_unless($this->isSuperAdmin(), 403);
        $validated = $request->validate(['p1_id' => ['required', Rule::exists('dosens', 'id_dosen')->where('aktif', true)]]);
        $application = ThesisApplication::findOrFail($applicationId);
        abort_if($application->status !== 'pengajuan_sempro' || $application->sempro_exam_date || $application->documents()->exists() || $application->scores()->exists(), 409, 'Pembimbing 1 hanya dapat di-override sebelum proses Sempro berjalan.');
        abort_if(in_array($validated['p1_id'], [$application->p2_id, $application->examiner_id], true), 422, 'Pembimbing 1 harus berbeda dari Pembimbing 2 dan Penguji.');
        $application->update(['p1_id' => $validated['p1_id']]);

        return response()->json(['status' => 'success', 'message' => 'Pembimbing 1 ditetapkan oleh Super Admin (override kuota).']);
    }

    public function importAssignments(Request $request)
    {
        $this->authorizeKps();
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        Excel::import(new ThesisAssignmentImport(), $request->file('file'));

        return response()->json(['status' => 'success', 'message' => 'Plotting Pembimbing 2 dan Penguji berhasil diimpor.']);
    }

    public function exportAssignments()
    {
        $this->authorizeKps();

        return Excel::download(new ThesisAssignmentExport($this->activeSemester()?->id), 'plotting-tugas-akhir.xlsx');
    }

    public function uploadDocument(Request $request, int $applicationId)
    {
        $application = $this->applicationForStudent($request, $applicationId);
        abort_if($application->ta_final_approved_at, 409, 'Dokumen tidak dapat diubah setelah Tugas Akhir dinyatakan selesai.');
        $validated = $request->validate([
            'document_type' => ['required', Rule::in([
                'sempro_consultation', 'sempro_agreement', 'sempro_final_draft',
                'capstone_completion', 'sempro_completion', 'semhas_draft', 'semhas_attendance', 'semhas_completion',
                'semhas_result', 'journal_submission', 'ta_final_draft', 'ta_revised_final',
            ])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $file = $request->file('document');
        $type = $validated['document_type'];
        if ($type === 'sempro_consultation' || in_array($type, ['sempro_agreement', 'sempro_final_draft'], true)) {
            abort_if($application->sempro_approved_at, 409, 'Dokumen Sempro tidak dapat diubah setelah Sempro dinyatakan selesai.');
        }
        if (in_array($type, ['capstone_completion', 'sempro_completion', 'semhas_draft', 'semhas_attendance'], true)) {
            abort_if($application->semhas_finished_at, 409, 'Dokumen Semhas tidak dapat diubah setelah Semhas dinyatakan selesai.');
        }
        if (in_array($type, ['capstone_completion', 'sempro_completion'], true)) {
            abort_unless($application->status === 'selesai_sempro', 409, 'Bukti penyelesaian Capstone dan Sempro baru dapat diunggah setelah Sempro selesai.');
        }
        if (in_array($type, ['semhas_draft', 'semhas_attendance'], true)) {
            abort_unless($application->semhas_coordinator_verified && $application->semhas_kps_verified, 409, 'Bukti penyelesaian Capstone dan Sempro harus diverifikasi Koordinator dan Kaprodi terlebih dahulu.');
        }
        if (in_array($type, ['semhas_result', 'journal_submission', 'ta_final_draft', 'ta_revised_final'], true)) {
            abort_unless($application->semhas_finished_at, 409, 'Semhas harus selesai sebelum dokumen tahap Sidang TA dapat diunggah.');
        }
        $path = $file->store('tugas-akhir/' . $application->id, 'public');
        $existing = $application->documents()->where('document_type', $validated['document_type'])->first();
        if ($existing) {
            Storage::disk('public')->delete($existing->file_path);
        }
        $document = $application->documents()->updateOrCreate(
            ['document_type' => $validated['document_type']],
            [
                'file_path' => $path,
                'verification_status' => 'pending',
                'verification_note' => null,
                'verified_by' => null,
                'verified_by_role' => null,
                'verified_at' => null,
            ]
        );
        $this->resetVerificationForUpload($application, $document->document_type);

        return response()->json(['status' => 'success', 'message' => 'Dokumen berhasil diunggah dan menunggu verifikasi.']);
    }

    public function verifyDocument(Request $request, int $applicationId, int $documentId)
    {
        $application = ThesisApplication::findOrFail($applicationId);
        $document = $application->documents()->findOrFail($documentId);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $approved = $validated['decision'] === 'approved';
        $user = auth()->user();
        $userRoles = $user->getRoleNames()->implode(', ');

        if ($document->document_type === 'sempro_consultation') {
            $this->authorizeKps();
            $application->update([
                'sempro_consultation_approved' => $approved,
                'sempro_consultation_approved_by' => $approved ? $user->id : null,
                'sempro_consultation_approved_by_role' => $approved ? $userRoles : null,
                'sempro_consultation_approved_at' => $approved ? now() : null,
            ]);
        } elseif (in_array($document->document_type, ['capstone_completion', 'sempro_completion', 'semhas_result', 'journal_submission'], true)) {
            $this->authorizeCoordinatorOrKps();
            $isKps = $user->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi']);
            if (in_array($document->document_type, ['capstone_completion', 'sempro_completion'], true)) {
                $field = $isKps ? 'semhas_kps_verified' : 'semhas_coordinator_verified';
                $application->update([
                    $field => $approved,
                    ($isKps ? 'semhas_kps_verified_by' : 'semhas_coordinator_verified_by') => $approved ? $user->id : null,
                    ($isKps ? 'semhas_kps_verified_by_role' : 'semhas_coordinator_verified_by_role') => $approved ? $userRoles : null,
                    ($isKps ? 'semhas_kps_verified_at' : 'semhas_coordinator_verified_at') => $approved ? now() : null,
                ]);
            } else {
                $field = $isKps ? 'ta_kps_verified' : 'ta_coordinator_verified';
                $application->update([
                    $field => $approved,
                    ($isKps ? 'ta_kps_verified_by' : 'ta_coordinator_verified_by') => $approved ? $user->id : null,
                    ($isKps ? 'ta_kps_verified_by_role' : 'ta_coordinator_verified_by_role') => $approved ? $userRoles : null,
                    ($isKps ? 'ta_kps_verified_at' : 'ta_coordinator_verified_at') => $approved ? now() : null,
                ]);
            }
        } elseif ($document->document_type === 'ta_final_draft') {
            $this->authorizeCoordinatorOrKps();
            $application->update([
                'ta_draft_verified' => $approved,
                'ta_draft_verified_by' => $approved ? $user->id : null,
                'ta_draft_verified_by_role' => $approved ? $userRoles : null,
                'ta_draft_verified_at' => $approved ? now() : null,
            ]);
        } elseif ($document->document_type === 'semhas_draft' || $document->document_type === 'semhas_attendance') {
            $this->authorizeCoordinatorOrKps();
        } else {
            $this->authorizeKps();
        }

        $document->update([
            'verification_status' => $validated['decision'],
            'verification_note' => $validated['note'] ?? null,
            'verified_by' => $user->id,
            'verified_by_role' => $userRoles,
            'verified_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Status verifikasi dokumen tersimpan.']);
    }

    public function proposeSemproSchedule(Request $request, int $applicationId)
    {
        $application = $this->applicationForStudent($request, $applicationId);
        abort_if($application->sempro_approved_at, 409, 'Jadwal Sempro tidak dapat diubah setelah Sempro selesai.');
        $validated = $request->validate([
            'sempro_proposed_date' => ['required', 'date', 'after:today'],
            'sempro_proposed_time' => ['required', 'date_format:H:i'],
        ]);
        abort_unless($application->sempro_consultation_approved && $application->p1_id && $application->p2_id && $application->examiner_id, 409, 'Kartu konsultasi harus di-ACC Kaprodi dan dosen penguji/pembimbing harus lengkap.');
        $application->update($validated + ['sempro_exam_date' => null, 'sempro_exam_time' => null, 'sempro_exam_room' => null, 'sempro_invitation_sent_at' => null]);

        return response()->json(['status' => 'success', 'message' => 'Usulan jadwal Sempro berhasil dikirim ke Admin.']);
    }

    public function setSemproSchedule(Request $request, int $applicationId)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'sempro_exam_date' => ['required', 'date', 'after_or_equal:today'],
            'sempro_exam_time' => ['required', 'date_format:H:i'],
            'sempro_exam_room' => ['required', 'string', 'max:100'],
        ]);
        $application = ThesisApplication::findOrFail($applicationId);
        abort_if($application->sempro_approved_at, 409, 'Jadwal Sempro tidak dapat diubah setelah Sempro selesai.');
        abort_unless($application->sempro_proposed_date && $application->sempro_proposed_time, 409, 'Mahasiswa belum mengirim usulan jadwal.');
        $application->update($validated + ['sempro_invitation_sent_at' => null]);

        return response()->json(['status' => 'success', 'message' => 'Jadwal dan ruangan Sempro berhasil ditetapkan.']);
    }

    public function sendSemproInvitation(int $applicationId)
    {
        $this->authorizeAdmin();
        $application = ThesisApplication::with(['student', 'p1', 'p2', 'examiner'])->findOrFail($applicationId);
        abort_unless($application->sempro_exam_date && $application->sempro_exam_time && $application->sempro_exam_room, 409, 'Jadwal Sempro belum lengkap.');
        $emails = collect([$application->p1?->email, $application->p2?->email, $application->examiner?->email])->filter()->unique()->values()->all();
        abort_if($emails === [], 422, 'Email dosen pembimbing/penguji belum tersedia.');
        Mail::to($emails)->send(new ThesisExamInvitation($application, 'Seminar Proposal'));
        $application->update(['sempro_invitation_sent_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Undangan Sempro berhasil dikirim.']);
    }

    public function proposeTaSchedule(Request $request, int $applicationId)
    {
        $application = $this->applicationForStudent($request, $applicationId);
        abort_if($application->ta_final_approved_at, 409, 'Jadwal Sidang TA tidak dapat diubah setelah Tugas Akhir selesai.');
        $validated = $request->validate([
            'ta_proposed_date' => ['required', 'date', 'after:today'],
            'ta_proposed_time' => ['required', 'date_format:H:i'],
        ]);
        abort_unless($application->ta_draft_verified && $application->semhas_finished_at && $this->taInitialRequirementsVerified($application) && $application->p1_id && $application->p2_id && $application->examiner_id, 409, 'Persyaratan awal, Semhas, verifikasi draft final, dan plotting panel harus lengkap sebelum usulan sidang.');
        $application->update($validated + [
            'ta_exam_date' => null,
            'ta_exam_time' => null,
            'ta_exam_room' => null,
            'ta_invitation_sent_at' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Usulan jadwal sidang TA berhasil dikirim.']);
    }

    public function setTaSchedule(Request $request, int $applicationId)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'ta_exam_date' => ['required', 'date', 'after_or_equal:today'],
            'ta_exam_time' => ['required', 'date_format:H:i'],
            'ta_exam_room' => ['required', 'string', 'max:100'],
        ]);
        $application = ThesisApplication::findOrFail($applicationId);
        abort_if($application->ta_final_approved_at, 409, 'Jadwal Sidang TA tidak dapat diubah setelah Tugas Akhir selesai.');
        abort_unless($application->ta_proposed_date && $application->ta_proposed_time && $application->ta_draft_verified && $this->taInitialRequirementsVerified($application), 409, 'Usulan jadwal dan seluruh verifikasi tahap awal TA wajib tersedia.');
        $application->update($validated + ['ta_invitation_sent_at' => null]);

        return response()->json(['status' => 'success', 'message' => 'Jadwal sidang TA berhasil ditetapkan.']);
    }

    public function sendTaInvitation(int $applicationId)
    {
        $this->authorizeAdmin();
        $application = ThesisApplication::with(['student', 'p1', 'p2', 'examiner'])->findOrFail($applicationId);
        abort_if($application->ta_final_approved_at, 409, 'Undangan tidak dapat dikirim setelah Tugas Akhir dinyatakan selesai.');
        abort_unless($application->ta_exam_date && $application->ta_exam_time && $application->ta_exam_room, 409, 'Jadwal sidang TA belum lengkap.');
        $emails = collect([$application->p1?->email, $application->p2?->email, $application->examiner?->email])->filter()->unique()->values()->all();
        abort_if($emails === [], 422, 'Email dosen pembimbing/penguji belum tersedia.');
        Mail::to($emails)->send(new ThesisExamInvitation($application, 'Sidang Tugas Akhir'));
        $application->update(['ta_invitation_sent_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Undangan Sidang TA berhasil dikirim.']);
    }

    public function saveScore(Request $request, int $applicationId)
    {
        $validated = $request->validate([
            'assessment_type' => ['required', Rule::in(['sempro_pelaksanaan', 'sempro_komprehensif', 'nilai_ta_final'])],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $dosen = $this->dosenForUser(auth()->user());
        abort_unless($dosen, 403, 'Profil dosen tidak terhubung ke akun ini.');
        $application = ThesisApplication::findOrFail($applicationId);
        $role = match ($dosen->id_dosen) {
            $application->p1_id => 'P1',
            $application->p2_id => 'P2',
            $application->examiner_id => 'PU',
            default => null,
        };
        abort_unless($role, 403, 'Dosen bukan anggota panel penguji/pembimbing untuk pengajuan ini.');
        abort_if($validated['assessment_type'] === 'nilai_ta_final' && (! $application->semhas_finished_at || ! $application->ta_draft_verified), 409, 'Nilai final TA baru dapat diinput setelah Semhas selesai dan draft TA diverifikasi.');
        abort_if(str_starts_with($validated['assessment_type'], 'sempro_') && ! $application->sempro_exam_date, 409, 'Nilai Sempro baru dapat diinput setelah jadwal ujian ditetapkan.');

        ThesisScore::updateOrCreate(
            [
                'thesis_application_id' => $application->id,
                'assessment_type' => $validated['assessment_type'],
                'evaluator_role' => $role,
            ],
            [
                'evaluator_id' => $dosen->id_dosen,
                'score' => $validated['score'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai berhasil disimpan.',
            'average' => $this->averageScore($application, $validated['assessment_type']),
        ]);
    }

    private function averageScore(ThesisApplication $application, string $type): ?float
    {
        $scores = $application->scores()
            ->where('assessment_type', $type)
            ->whereIn('evaluator_role', ['P1', 'P2', 'PU'])
            ->pluck('score');
        if ($scores->count() !== 3) {
            return null;
        }

        return round((float) $scores->avg(), 2);
    }

    public function finishSempro(int $applicationId)
    {
        $this->authorizeKps();
        $application = ThesisApplication::with('documents')->findOrFail($applicationId);
        abort_if($application->sempro_approved_at, 409, 'Sempro sudah dinyatakan selesai.');
        abort_unless($application->sempro_consultation_approved && $application->sempro_exam_date && $application->sempro_exam_time && $application->sempro_exam_room, 409, 'ACC kartu konsultasi dan jadwal/ruangan Sempro harus lengkap.');
        $requiredDocuments = ['sempro_agreement', 'sempro_final_draft'];
        abort_unless($application->documents->whereIn('document_type', $requiredDocuments)->where('verification_status', 'approved')->count() === count($requiredDocuments), 409, 'Kesepakatan Bersama dan Draft Final Sempro harus diverifikasi.');
        foreach (['sempro_pelaksanaan', 'sempro_komprehensif'] as $type) {
            abort_unless($application->scores()->where('assessment_type', $type)->whereIn('evaluator_role', ['P1', 'P2', 'PU'])->count() === 3, 409, 'Nilai pelaksanaan dan komprehensif harus lengkap dari P1, P2, dan Penguji.');
        }
        $application->update([
            'sempro_approved_by' => auth()->id(),
            'sempro_approved_by_role' => auth()->user()->getRoleNames()->implode(', '),
            'sempro_approved_at' => now(),
            'status' => 'selesai_sempro',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Sempro disetujui dan ditandai selesai.']);
    }

    public function finishSemhas(int $applicationId)
    {
        $this->authorizeCoordinatorOrKps();
        $application = ThesisApplication::with('documents')->findOrFail($applicationId);
        abort_if($application->semhas_finished_at, 409, 'Semhas sudah dinyatakan selesai.');
        abort_unless($application->sempro_approved_at, 409, 'Sempro harus selesai sebelum Semhas dapat dinyatakan selesai.');
        abort_unless($application->semhas_coordinator_verified && $application->semhas_kps_verified, 409, 'Verifikasi Koordinator Skripsi dan Kaprodi harus lengkap.');
        foreach (['capstone_completion', 'sempro_completion'] as $type) {
            abort_unless($application->documents->contains(fn ($document) => $document->document_type === $type && $document->verification_status === 'approved'), 409, 'Bukti penyelesaian Capstone Design dan Sempro harus berstatus disetujui.');
        }
        foreach (['semhas_draft', 'semhas_attendance'] as $type) {
            abort_unless($application->documents->contains(fn ($document) => $document->document_type === $type && $document->verification_status === 'approved'), 409, 'Draft Semhas dan daftar hadir harus diverifikasi.');
        }
        $application->update([
            'semhas_finished_at' => now(),
            'semhas_finished_by' => auth()->id(),
            'semhas_finished_by_role' => auth()->user()->getRoleNames()->implode(', '),
            'status' => 'selesai_semhas',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Semhas ditandai selesai.']);
    }

    public function approveFinalTa(int $applicationId)
    {
        $this->authorizeKps();
        $application = ThesisApplication::with('documents')->findOrFail($applicationId);
        abort_if($application->ta_final_approved_at, 409, 'Tugas Akhir sudah dinyatakan selesai.');
        abort_unless(($application->ta_coordinator_verified || $application->ta_kps_verified) && $application->ta_draft_verified, 409, 'Verifikasi Koordinator/Kaprodi untuk syarat awal dan draft final harus lengkap.');
        foreach (['semhas_result', 'journal_submission', 'ta_final_draft'] as $requiredType) {
            abort_unless($application->documents->contains(fn ($document) => $document->document_type === $requiredType && $document->verification_status === 'approved'), 409, 'Bukti Semhas, bukti submit jurnal, dan draft final TA harus diverifikasi.');
        }

        abort_unless($application->semhas_finished_at, 409, 'Semhas harus selesai sebelum kelulusan Tugas Akhir dapat disetujui.');
        abort_unless($application->ta_exam_date && $application->ta_exam_time && $application->ta_exam_room, 409, 'Jadwal dan ruangan Sidang TA harus ditetapkan.');
        abort_unless($application->documents->contains(fn ($document) => $document->document_type === 'ta_revised_final' && $document->verification_status === 'approved'), 409, 'Tugas Akhir final terevisi wajib diunggah dan diverifikasi.');
        abort_unless($application->scores()->where('assessment_type', 'nilai_ta_final')->whereIn('evaluator_role', ['P1', 'P2', 'PU'])->count() === 3, 409, 'Nilai final harus diinput P1, P2, dan Penguji.');
        $application->update([
            'ta_final_approved_by' => auth()->id(),
            'ta_final_approved_by_role' => auth()->user()->getRoleNames()->implode(', '),
            'ta_final_approved_at' => now(),
            'status' => 'selesai_ta',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas Akhir dinyatakan selesai.',
            'final_score' => $this->averageScore($application, 'nilai_ta_final'),
        ]);
    }

    public function gradeSummary(int $applicationId)
    {
        $application = ThesisApplication::with(['scores', 'p1', 'p2', 'examiner'])->findOrFail($applicationId);
        $user = auth()->user();
        $dosen = $this->dosenForUser($user);
        $isAssignedLecturer = $dosen && in_array($dosen->id_dosen, [$application->p1_id, $application->p2_id, $application->examiner_id], true);
        $isStudent = $user?->hasAnyRole(['mahasiswa', 'Mahasiswa']) && $application->student_id === $user->id;
        abort_unless($isStudent || $isAssignedLecturer || $user?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'KPS', 'Kaprodi']), 403);

        return response()->json([
            'sempro_pelaksanaan' => $this->averageScore($application, 'sempro_pelaksanaan'),
            'sempro_komprehensif' => $this->averageScore($application, 'sempro_komprehensif'),
            'nilai_ta_final' => $this->averageScore($application, 'nilai_ta_final'),
            'formula' => 'Rata-rata aritmetika P1, P2, dan Penguji yang telah menginput nilai.',
        ]);
    }

    public function printDocument(int $applicationId, string $type)
    {
        abort_unless(in_array($type, ['sk-sempro', 'ba-sempro', 'ba-komprehensif', 'ba-sidang-ta'], true), 404);
        $this->authorizeAdmin();
        abort_unless($type !== 'sk-sempro' || $this->isSuperAdmin(), 403);
        $application = ThesisApplication::with(['student', 'semester', 'p1', 'p2', 'examiner', 'scores.evaluator'])->findOrFail($applicationId);

        return view('tugas_akhir.print', compact('application', 'type'));
    }

    private function authorizeCoordinatorOrKps(): void
    {
        $this->authorizeCoordinator();
    }

    private function resetVerificationForUpload(ThesisApplication $application, string $type): void
    {
        $updates = [];
        if (in_array($type, ['capstone_completion', 'sempro_completion'], true)) {
            $updates += [
                'semhas_coordinator_verified' => false,
                'semhas_coordinator_verified_by' => null,
                'semhas_coordinator_verified_by_role' => null,
                'semhas_coordinator_verified_at' => null,
                'semhas_kps_verified' => false,
                'semhas_kps_verified_by' => null,
                'semhas_kps_verified_by_role' => null,
                'semhas_kps_verified_at' => null,
            ];
        }
        if (in_array($type, ['semhas_result', 'journal_submission'], true)) {
            $updates += [
                'ta_coordinator_verified' => false,
                'ta_coordinator_verified_by' => null,
                'ta_coordinator_verified_by_role' => null,
                'ta_coordinator_verified_at' => null,
                'ta_kps_verified' => false,
                'ta_kps_verified_by' => null,
                'ta_kps_verified_by_role' => null,
                'ta_kps_verified_at' => null,
                'ta_proposed_date' => null,
                'ta_proposed_time' => null,
                'ta_exam_date' => null,
                'ta_exam_time' => null,
                'ta_exam_room' => null,
                'ta_invitation_sent_at' => null,
            ];
        }
        if ($type === 'ta_final_draft') {
            $updates += [
                'ta_draft_verified' => false,
                'ta_draft_verified_by' => null,
                'ta_draft_verified_by_role' => null,
                'ta_draft_verified_at' => null,
                'ta_proposed_date' => null,
                'ta_proposed_time' => null,
                'ta_exam_date' => null,
                'ta_exam_time' => null,
                'ta_exam_room' => null,
                'ta_invitation_sent_at' => null,
            ];
        }
        if ($type === 'sempro_consultation') {
            $updates += [
                'sempro_consultation_approved' => false,
                'sempro_consultation_approved_by' => null,
                'sempro_consultation_approved_by_role' => null,
                'sempro_consultation_approved_at' => null,
                'sempro_proposed_date' => null,
                'sempro_proposed_time' => null,
                'sempro_exam_date' => null,
                'sempro_exam_time' => null,
                'sempro_exam_room' => null,
                'sempro_invitation_sent_at' => null,
            ];
        }
        if ($updates !== []) {
            $application->update($updates);
        }
    }
}
