<?php

namespace App\Http\Controllers;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Pkl\DetailNilaiPkl;
use App\Models\Pkl\KomponenPenilaianPkl;
use App\Models\Pkl\LogAktivitasPkl;
use App\Models\Pkl\PengajuanPkl;
use App\Models\Semester;
use App\Mail\PklExamInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PklController extends Controller
{
    private const STATUS_PENGAJUAN = 'Pengajuan & Verifikasi Admin';
    private const STATUS_PELAKSANAAN = 'Pelaksanaan PKL & Bimbingan';
    private const STATUS_VERIFIKASI = 'Verifikasi Berkas Admin';
    private const STATUS_UJIAN = 'Dijadwalkan Ujian';
    private const STATUS_SEMINAR = 'Ujian Seminar & Penilaian';
    private const STATUS_REVISI = 'Revisi Laporan';
    private const STATUS_SELESAI = 'Selesai';

    private const FILE_FIELDS = [
        'berkas_proposal',
        'berkas_logbook',
        'berkas_kartu_bimbingan',
        'berkas_draft_laporan',
        'berkas_nilai_pamong',
        'berkas_persetujuan_pamong',
        'berkas_laporan_revisi',
        'berkas_lembar_pengesahan',
    ];

    private function canSelectActingProfile(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin'])
            || auth()->user()?->can('academic-edit');
    }

    private function canActAsStudent(): bool
    {
        return $this->canSelectActingProfile()
            && (bool) session('pkl.mahasiswa_profile_id');
    }

    private function canActAsLecturer(): bool
    {
        return $this->canSelectActingProfile()
            && (bool) session('pkl.dosen_profile_id');
    }

    public function mahasiswaIndex(Request $request): View
    {
        $user = auth()->user();
        $isPrivileged = $this->canSelectActingProfile();
        $canSelectStudentProfile = $isPrivileged || $user?->hasAnyRole(['mahasiswa', 'Mahasiswa']);
        if ($isPrivileged && $request->filled('id_registrasi_mahasiswa')) {
            $selected = MahasiswaRegistrasi::query()->where('aktif', true)->find($request->input('id_registrasi_mahasiswa'));
            abort_unless($selected, 404, 'Profil mahasiswa aktif tidak ditemukan.');
            $request->session()->put('pkl.mahasiswa_profile_id', $selected->id_registrasi_mahasiswa);
        }
        $mahasiswa = $isPrivileged && $request->session()->has('pkl.mahasiswa_profile_id')
            ? MahasiswaRegistrasi::query()->where('aktif', true)->find($request->session()->get('pkl.mahasiswa_profile_id'))
            : $this->mahasiswaProfilesForUser()->first();

        if (! $mahasiswa && $canSelectStudentProfile) {
            $request->session()->forget('pkl.mahasiswa_profile_id');
            return view('pkl.profile_selector', [
                'jenis' => 'mahasiswa',
                'profiles' => MahasiswaRegistrasi::query()->where('aktif', true)->orderBy('nama_mahasiswa')->get(),
                'route' => route('pkl.mahasiswa.index'),
                'parameter' => 'id_registrasi_mahasiswa',
                'title' => 'Pilih Profil Mahasiswa',
                'description' => 'Profil mahasiswa belum tertaut otomatis dengan email atau NIM akun ini. Pilih profil mahasiswa untuk melanjutkan.',
            ]);
        }
        abort_unless($mahasiswa, 403, 'Profil mahasiswa aktif untuk akun ini belum tersedia. Hubungi administrator untuk memperbarui identitas akun.');
        if ($isPrivileged) {
            $request->session()->put('pkl.mahasiswa_profile_id', $mahasiswa->id_registrasi_mahasiswa);
        }

        $pengajuan = $mahasiswa->pengajuanPkl()->with([
            'dosenPembimbing',
            'dosenPenguji',
            'detailNilai.komponen',
            'bimbingan.tanggapan.pengirim',
            'logAktivitas.aktor',
            'berkasSeminar',
        ])->latest()->first();

        return view('pkl.mahasiswa_dashboard', [
            'pengajuan' => $pengajuan,
            'mahasiswa' => $mahasiswa,
            'dosensPembimbing' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
            'dosensPenguji' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
            'isActingAsProfile' => $isPrivileged,
            'availableProfiles' => $isPrivileged
                ? MahasiswaRegistrasi::query()->where('aktif', true)->orderBy('nama_mahasiswa')->get()
                : collect(),
        ]);
    }

    public function routeMap(Request $request): View
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin']), 403);

        $query = PengajuanPkl::query()
            ->with([
                'mahasiswaRegistrasi',
                'dosenPembimbing',
                'dosenPenguji',
                'detailNilai.komponen',
                'berkasSeminar',
                'logAktivitas.aktor',
            ]);
        $this->applySemesterFilter($query, $request->input('id_semester'));
        if ($request->filled('pengajuan')) {
            $query->whereKey($request->input('pengajuan'));
        }
        $pengajuan = $query->latest()->first();

        $mahasiswa = $pengajuan?->mahasiswaRegistrasi;
        $submissions = $this->filteredSubmissions($request)->with(['detailNilai.komponen', 'berkasSeminar', 'logAktivitas.aktor'])->paginate(10)->withQueryString();
        $stats = $this->statusStats();

        return view('pkl.mahasiswa_route_map', [
            'pkl' => $pengajuan,
            'mahasiswa' => $mahasiswa,
            'dosensPenguji' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
            'komponen_penilaian_pkl' => $pengajuan ? $this->componentsForSubmission($pengajuan)->get() : collect(),
            'submissions' => $submissions,
            'stats' => $stats,
            'dokumenFormulir' => config('pkl.route_map_documents', []),
            'daftarPengajuan' => PengajuanPkl::query()
                ->with('mahasiswaRegistrasi:id_registrasi_mahasiswa,nim,nama_mahasiswa')
                ->latest()
                ->get(),
        ]);
    }

    public function routeMapUpload(Request $request, PengajuanPkl $pengajuan, int $tahap): RedirectResponse
    {
        $this->assertOwnSubmission($pengajuan);
        $allowedStageStatus = match ($tahap) {
            1 => [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI],
            2 => [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI],
            3 => [self::STATUS_REVISI],
            default => abort(404),
        };
        abort_unless(in_array($pengajuan->status, $allowedStageStatus, true), 409, 'Unggahan dokumen tidak tersedia pada status PKL saat ini.');

        $fields = match ($tahap) {
            1 => ['proposal', 'surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'],
            2 => ['draft_laporan', 'kartu_bimbingan', 'transkrip_sementara'],
            3 => ['formulir_pendaftaran_ujian', 'berita_acara_ujian', 'formulir_nilai', 'permohonan_revisi', 'laporan_final', 'lampiran_teknis', 'acc_pembimbing_akhir', 'rekap_nilai'],
            default => abort(404),
        };
        $jenis = (string) $request->input('jenis_dokumen');
        $field = (string) $request->input('field');
        if ($tahap === 1 && $jenis === 'proposal') {
            $field = 'berkas_proposal';
        } elseif ($tahap === 2) {
            $field = match ($jenis) {
                'draft_laporan' => 'draft_laporan',
                'kartu_bimbingan' => 'kartu_konsultasi',
                default => $jenis
            };
        } else {
            $field = $jenis;
        }
        abort_unless(in_array($jenis, $fields, true), 422, 'Jenis berkas tidak valid.');
        $technicalAttachment = $jenis === 'lampiran_teknis';
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:' . ($technicalAttachment ? 'pdf,jpg,jpeg,png,dwg,dxf,xls,xlsx,csv,zip' : 'pdf,jpg,jpeg,png'),
                'max:' . ($technicalAttachment ? 20480 : 10240),
            ],
        ]);
        abort_unless($request->file('file')->isValid(), 422, 'Pilih berkas yang valid.');

        if ($field === 'berkas_proposal') {
            $document = $this->storePklDocument($pengajuan, 'proposal', $request->file('file'));
            $pengajuan->update(['berkas_proposal' => $document->path_berkas]);
        } elseif (in_array($field, ['draft_laporan', 'kartu_konsultasi'], true)) {
            $legacyField = $field === 'draft_laporan' ? 'berkas_draft_laporan' : 'berkas_kartu_bimbingan';
            $pengajuan->update([$legacyField => $this->storeFile($request->file('file'))]);
            $this->storePklDocument($pengajuan, $field, $request->file('file'));
        } else {
            $this->storePklDocument($pengajuan, $field, $request->file('file'));
        }

        $this->advanceAfterExamDocuments($pengajuan);

        return back()->with('success', 'Berkas berhasil diunggah.');
    }

    public function mahasiswaPlotPembimbing(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeStudentAction();
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_PENGAJUAN, 409, 'Pembimbing hanya dapat diusulkan sebelum pelaksanaan PKL dimulai.');
        abort_unless(! $pengajuan->dosen_pembimbing_id, 409, 'Dosen pembimbing sudah ditetapkan. Hubungi Super Admin atau Kaprodi jika perlu diubah.');
        abort_unless($this->hasInitialRegistrationDocuments($pengajuan), 409, 'Lengkapi formulir dan unggah Proposal, Surat Jalan PKL, Surat Balasan Instansi, serta ACC Pembimbing sebelum memilih dosen pembimbing.');
        $validated = $request->validate([
            'dosen_pembimbing_id' => ['required', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
        ]);
        $dosen = Dosen::findOrFail($validated['dosen_pembimbing_id']);
        $pengajuan->update([
            'dosen_pembimbing_id' => $dosen->id_dosen,
            'status' => self::STATUS_PELAKSANAAN,
        ]);
        $this->logPklActivity($pengajuan, 'Plotting Pembimbing oleh Mahasiswa', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengusulkan dan menetapkan pembimbing ' . $dosen->nama_dosen . '. Admin dapat mengubah penetapan ini.');
        $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Status berubah dari ' . self::STATUS_PENGAJUAN . ' menjadi ' . self::STATUS_PELAKSANAAN . ' setelah mahasiswa memilih dosen pembimbing.');

        return back()->with('success', 'Dosen pembimbing ditetapkan dan status PKL berubah menjadi Pelaksanaan PKL & Bimbingan. Admin dapat menyesuaikan penetapan ini.');
    }

    private function hasInitialRegistrationDocuments(PengajuanPkl $pengajuan): bool
    {
        if (! $pengajuan->berkas_proposal) {
            return false;
        }

        $requiredDocuments = ['surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'];

        return $pengajuan->berkasSeminar()
            ->whereIn('jenis_berkas', $requiredDocuments)
            ->whereNotNull('path_berkas')
            ->distinct('jenis_berkas')
            ->count('jenis_berkas') === count($requiredDocuments);
    }

    private function advanceAfterExamDocuments(PengajuanPkl $pengajuan): void
    {
        $required = ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'];
        if ($pengajuan->status !== self::STATUS_VERIFIKASI || ! $this->examDocumentsApproved($pengajuan, $required)) {
            return;
        }

        $pengajuan->update(['status' => self::STATUS_UJIAN]);
        $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Status berubah dari ' . self::STATUS_VERIFIKASI . ' menjadi ' . self::STATUS_UJIAN . ' setelah mahasiswa melengkapi draft laporan, kartu konsultasi, dan transkrip sementara.');
    }

    private function examDocumentsApproved(PengajuanPkl $pengajuan, ?array $required = null): bool
    {
        $required ??= ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'];
        $documents = $pengajuan->berkasSeminar()
            ->whereIn('jenis_berkas', $required)
            ->where('status_verifikasi', 'Valid')
            ->whereNotNull('path_berkas')
            ->pluck('jenis_berkas')
            ->unique();

        return $documents->count() === count($required);
    }

    public function routeMapVerify(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']), 403);
        $validated = $request->validate([
            'berkas_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(['Valid', 'Ditolak'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_unless($pengajuan->berkasSeminar()->whereKey($validated['berkas_id'])->exists(), 404);
        $pengajuan->berkasSeminar()->whereKey($validated['berkas_id'])->update([
            'status_verifikasi' => $validated['status'],
            'catatan_verifikator' => $validated['catatan'] ?? null,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'approved_by' => $validated['status'] === 'Valid' ? auth()->id() : null,
            'approved_at' => $validated['status'] === 'Valid' ? now() : null,
        ]);
        $berkas = $pengajuan->berkasSeminar()->find($validated['berkas_id']);
        $this->logPklActivity($pengajuan, 'Verifikasi Dokumen', 'Admin ' . auth()->user()->name . ' memverifikasi dokumen ' . ($berkas?->jenis_berkas ?? '-') . ' dengan status ' . $validated['status'] . (($validated['catatan'] ?? '') !== '' ? '. Catatan: ' . $validated['catatan'] : '.'));
        $this->completePklIfReady($pengajuan);
        $this->advanceAfterExamDocuments($pengajuan);

        return back()->with('success', 'Status berkas diperbarui.');
    }

    public function routeMapAcc(PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($pengajuan->dosen_pembimbing_id === $dosen->id_dosen, 403);
        $wasAcc = (bool) $pengajuan->is_acc_seminar;
        $pengajuan->update([
            'is_acc_seminar' => true,
            'acc_seminar_by' => auth()->id(),
            'acc_seminar_at' => now(),
        ]);
        $this->advanceAfterExamDocuments($pengajuan);
        if (! $wasAcc) {
            $this->logPklActivity($pengajuan, 'ACC Seminar', 'Dosen pembimbing ' . (auth()->user()->name ?? '') . ' menyetujui pendaftaran ujian seminar.');
        }

        return back()->with('success', 'ACC seminar berhasil diberikan.');
    }

    public function routeMapProposeSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeStudentAction();
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_UJIAN, 409, 'Usulan jadwal hanya tersedia sebelum ujian dimulai.');
        return $this->mahasiswaProposeSchedule($request, $pengajuan);
    }

    public function mahasiswaProposeSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeStudentAction();
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_UJIAN, 409, 'Usulan atau revisi jadwal tersedia sebelum ujian dimulai.');
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date', 'after:now'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
            'catatan_usulan_jadwal' => ['nullable', 'string', 'max:2000'],
            'penguji_ids' => ['required', 'array', 'min:1'],
            'penguji_ids.*' => ['required', 'distinct', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
        ]);
        $tanggalSebelumnya = $pengajuan->usulan_tanggal_seminar?->format('Y-m-d H:i:s');
        $usulanPenguji = collect($validated['penguji_ids'])->values()->map(
            fn($dosenId, $index) => [
                'dosen_id' => (string) $dosenId,
                'peran' => $index === 0 ? 'Ketua Penguji' : 'Anggota Penguji',
            ]
        )->all();
        $pengajuan->update([
            'usulan_tanggal_seminar' => $validated['tanggal_seminar'],
            'usulan_ruangan_seminar' => $validated['ruangan_seminar'],
            'catatan_usulan_jadwal' => $validated['catatan_usulan_jadwal'] ?? null,
            'usulan_dosen_penguji' => $usulanPenguji,
        ]);
        $namaPenguji = Dosen::query()->whereIn('id_dosen', $validated['penguji_ids'])->pluck('nama_dosen')->implode(', ');
        $this->logPklActivity($pengajuan, 'Usulan Mahasiswa', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengusulkan atau meminta revisi jadwal ujian menjadi ' . $validated['tanggal_seminar'] . ' di ' . $validated['ruangan_seminar'] . '. Usulan dosen penguji: ' . $namaPenguji . (($validated['catatan_usulan_jadwal'] ?? '') !== '' ? '. Catatan: ' . $validated['catatan_usulan_jadwal'] : '') . '. Penetapan jadwal resmi dan penguji menunggu admin. Usulan sebelumnya: ' . ($tanggalSebelumnya ?: '-') . '.');

        return back()->with('success', 'Usulan tanggal ujian tersimpan. Jadwal dan ruangan resmi akan ditetapkan admin.');
    }

    private function logPklActivity(PengajuanPkl $pengajuan, string $aksi, ?string $deskripsi = null): void
    {
        LogAktivitasPkl::create([
            'pengajuan_pkl_id' => $pengajuan->id,
            'aktor_id' => auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
        ]);
    }

    public function uploadTahapDocument(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeStudentAction();
        $this->assertOwnSubmission($pengajuan);
        $validated = $request->validate([
            'jenis_berkas' => ['required', Rule::in(array_keys($this->routeMapDocumentCatalog()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $stage = $this->routeMapDocumentCatalog()[$validated['jenis_berkas']]['stage'];
        $allowed = match ($stage) {
            1 => in_array($pengajuan->status, [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI], true),
            2 => in_array($pengajuan->status, [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI], true),
            3 => $pengajuan->status === self::STATUS_REVISI,
            default => false,
        };
        abort_unless($allowed, 409, 'Unggahan dokumen tidak tersedia pada status PKL saat ini.');

        $kind = $validated['jenis_berkas'];
        if ($kind === 'proposal') {
            $document = $this->storePklDocument($pengajuan, 'proposal', $request->file('file'));
            $pengajuan->update(['berkas_proposal' => $document->path_berkas]);
        } else {
            $this->storePklDocument($pengajuan, $kind, $request->file('file'));
        }

        return back()->with('success', 'Dokumen berhasil diunggah dan menunggu verifikasi.');
    }

    public function routeMapSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']), 403);
        abort_unless($this->examDocumentsApproved($pengajuan), 409, 'Jadwal hanya dapat ditetapkan setelah berkas praujian diverifikasi admin.');
        $isReschedule = $request->boolean('penjadwalan_ulang');
        $dateField = $isReschedule ? 'tanggal_seminar_baru' : 'tanggal_seminar';
        $roomField = $isReschedule ? 'ruangan_seminar_baru' : 'ruangan_seminar';
        $validated = $request->validate([
            $dateField => ['required', 'date', 'after:now'],
            $roomField => ['required', 'string', 'max:255'],
            'penguji_ids' => ['required', 'array', 'min:1'],
            'penguji_ids.*' => ['required', 'distinct', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
            'alasan' => ['nullable', 'string', 'max:2000'],
        ]);
        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        DB::transaction(function () use ($pengajuan, $validated, $dateField, $roomField) {
            $pengajuan->dosenPenguji()->sync(collect($validated['penguji_ids'])->values()->mapWithKeys(
                fn($dosenId, $index) => [$dosenId => ['peran' => $index === 0 ? 'Ketua Penguji' : 'Anggota Penguji']]
            )->all());
            $pengajuan->update([
                'tanggal_seminar' => $validated[$dateField],
                'ruangan_seminar' => $validated[$roomField],
                'status' => self::STATUS_UJIAN,
                'schedule_approved_by' => auth()->id(),
                'schedule_approved_at' => now(),
                'usulan_tanggal_seminar' => null,
                'usulan_ruangan_seminar' => null,
                'catatan_usulan_jadwal' => null,
                'usulan_dosen_penguji' => null,
            ]);
        });
        $aksi = $isReschedule ? 'Jadwal Ujian Direvisi' : 'Jadwal Ujian Ditetapkan';
        $deskripsi = 'Admin ' . (auth()->user()->name ?? '') . ($isReschedule ? ' mengubah jadwal ujian' : ' menetapkan jadwal ujian')
            . ' dari ' . ($tanggalSebelumnya ?: '-') . ' / ' . ($ruanganSebelumnya ?: '-')
            . ' menjadi ' . $pengajuan->tanggal_seminar->format('Y-m-d H:i') . ' / ' . $pengajuan->ruangan_seminar
            . (($validated['alasan'] ?? '') !== '' ? '. Alasan: ' . $validated['alasan'] : '') . '.';
        $this->logPklActivity($pengajuan, $aksi, $deskripsi);
        $invitationSent = $this->sendExamInvitation($pengajuan);

        return back()->with(
            $invitationSent ? 'success' : 'warning',
            $invitationSent
                ? ($isReschedule ? 'Jadwal ujian berhasil direvisi dan undangan dikirim ke dosen terkait.' : 'Jadwal seminar ditetapkan dan undangan dikirim ke dosen terkait.')
                : 'Jadwal tersimpan, tetapi undangan belum dapat dikirim karena email dosen belum tersedia.'
        );
    }

    public function routeMapGrade(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        $validated = $request->validate([
            'kategori_penilai' => ['required', Rule::in(['Pembimbing', 'Penguji'])],
            'nilai' => ['required', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'between:0,100'],
            'catatan_evaluasi' => ['nullable', 'string', 'max:5000'],
        ]);
        abort_unless(
            $validated['kategori_penilai'] === 'Pembimbing'
                ? $pengajuan->dosen_pembimbing_id === $dosen->id_dosen
                : $pengajuan->dosenPenguji()->where('dosens.id_dosen', $dosen->id_dosen)->exists(),
            403
        );
        foreach ($validated['nilai'] as $komponenId => $nilai) {
            if ($nilai === null || $komponenId === 'umum') {
                continue;
            }
            $komponen = $this->componentsForSubmission($pengajuan)
                ->whereKey($komponenId)
                ->where('penilai', $validated['kategori_penilai'])
                ->firstOrFail();
            DetailNilaiPkl::query()->updateOrCreate(
                ['pengajuan_pkl_id' => $pengajuan->id, 'dosen_id' => $dosen->id_dosen, 'komponen_penilaian_pkl_id' => $komponen->id],
                ['nilai_angka' => $nilai]
            );
        }
        $this->markRevisionAfterGradeEntry($pengajuan);
        $this->refreshFinalGrade($pengajuan);
        if ($request->filled('catatan_evaluasi')) {
            $pengajuan->update(['catatan_admin' => $validated['catatan_evaluasi']]);
        }
        $this->logPklActivity($pengajuan, 'Penilaian PKL', 'Dosen ' . (auth()->user()->name ?? '') . ' menyimpan nilai kategori ' . $validated['kategori_penilai'] . ($request->filled('catatan_evaluasi') ? ' dan catatan evaluasi.' : '.'));
        $this->completePklIfReady($pengajuan);

        return back()->with('success', 'Penilaian berhasil disimpan.');
    }

    public function routeMapValidateRevision(PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        abort_unless($pengajuan->berkas_laporan_revisi, 409, 'Laporan revisi belum diunggah.');
        $this->logPklActivity($pengajuan, 'Revisi Divalidasi', 'Dosen ' . (auth()->user()->name ?? '') . ' memvalidasi berkas revisi pasca-ujian.');
        abort_unless($this->completePklIfReady($pengajuan), 409, 'Kelulusan menunggu seluruh dokumen akhir, ACC pembimbing yang valid, dan nilai pembimbing.');

        return back()->with('success', 'Revisi divalidasi.');
    }

    public function routeMapComplete(PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($pengajuan->dosen_pembimbing_id === $dosen->id_dosen, 403);
        abort_unless(in_array($pengajuan->status, [self::STATUS_SEMINAR, self::STATUS_REVISI, self::STATUS_SELESAI], true), 409, 'ACC kelulusan akhir hanya dapat diberikan setelah ujian.');
        abort_unless($this->hasFinalDocumentsAndScore($pengajuan), 409, 'ACC final menunggu seluruh dokumen akhir dan input nilai pembimbing.');
        $pengajuan->update([
            'final_approved_by' => auth()->id(),
            'final_approved_at' => now(),
        ]);
        $pengajuan->berkasSeminar()
            ->where('jenis_berkas', 'acc_pembimbing_akhir')
            ->update([
                'status_verifikasi' => 'Valid',
                'catatan_verifikator' => null,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        $this->logPklActivity($pengajuan, 'ACC Final PKL', 'Dosen pembimbing ' . (auth()->user()->name ?? '') . ' memberikan ACC final kelulusan PKL.');
        abort_unless($this->completePklIfReady($pengajuan), 409, 'Kelulusan menunggu seluruh dokumen akhir, ACC pembimbing yang valid, dan nilai pembimbing.');

        return back()->with('success', 'ACC final berhasil disimpan dan status mahasiswa menjadi Lulus PKL.');
    }

    public function routeMapDocument(string $dokumen)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin']), 403);
        $path = config("pkl.route_map_documents.{$dokumen}");
        abort_unless(is_string($path) && Storage::disk('public')->exists($path), 404, 'Dokumen formulir belum tersedia.');

        return Storage::disk('public')->download($path);
    }

    private function isAssignedDosen(PengajuanPkl $pengajuan, Dosen $dosen): bool
    {
        return $pengajuan->dosen_pembimbing_id === $dosen->id_dosen
            || $pengajuan->dosenPenguji()->where('dosens.id_dosen', $dosen->id_dosen)->exists();
    }

    public function mahasiswaStore(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeStudentAction();
        $mahasiswa = $this->currentMahasiswa();
        abort_if($mahasiswa->pengajuanPkl()->exists(), 409, 'Anda sudah memiliki pengajuan PKL.');
        $fileFields = ['berkas_proposal', 'surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'];

        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'alamat_instansi' => ['required', 'string', 'max:5000'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'berkas_proposal' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
            'surat_jalan_pkl' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'surat_balasan_instansi' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'acc_pembimbing_awal' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);
        foreach (['berkas_proposal', 'surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'] as $field) {
            abort_if($request->hasFile($field) && $request->file($field)->getSize() > 2 * 1024 * 1024, 422, 'Setiap berkas pendaftaran maksimal 2MB.');
        }

        $validated['mahasiswa_registrasi_id'] = $mahasiswa->id_registrasi_mahasiswa;
        $semester = Semester::query()->where('aktif', true)->first();
        abort_unless($semester, 409, 'Semester aktif belum diatur. Hubungi administrator sebelum mengajukan PKL.');
        $validated['semester_master_id'] = $semester->id;
        $validated['id_semester'] = $semester->kode_semester;
        $dokumenAwal = collect(array_diff($fileFields, ['berkas_proposal']))
            ->filter(fn($field) => $request->hasFile($field))
            ->mapWithKeys(fn($field) => [$field => $request->file($field)])
            ->all();
        foreach ($fileFields as $field) {
            unset($validated[$field]);
        }
        if ($request->hasFile('berkas_proposal')) {
            $validated['berkas_proposal'] = $this->storeFile($request->file('berkas_proposal'));
        }
        $validated['status'] = self::STATUS_PENGAJUAN;
        $pengajuan = PengajuanPkl::create($validated);
        $this->logPklActivity($pengajuan, 'Pengajuan PKL', 'Mahasiswa mendaftarkan PKL pada ' . now()->format('d-m-Y H:i') . '.');
        foreach ($dokumenAwal as $field => $file) {
            $this->storePklDocument($pengajuan, $field, $file);
        }
        if ($pengajuan->berkas_proposal) {
            $pengajuan->berkasSeminar()->updateOrCreate(
                ['jenis_berkas' => 'proposal'],
                [
                    'path_berkas' => $pengajuan->berkas_proposal,
                    'status_verifikasi' => 'Pending',
                    'catatan_verifikator' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );
            $this->logPklActivity($pengajuan, 'Unggah Dokumen', (auth()->user()?->name ?? 'Mahasiswa') . ' mengunggah dokumen: Proposal PKL.');
        }

        return $this->successResponse($request, 'Pengajuan PKL berhasil dikirim.', $pengajuan, 201);
    }

    public function mahasiswaUpdate(Request $request, PengajuanPkl $pengajuan): JsonResponse|RedirectResponse
    {
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_PENGAJUAN, 409, 'Pengajuan hanya dapat diedit sebelum disetujui.');

        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'alamat_instansi' => ['required', 'string', 'max:5000'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'berkas_proposal' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        if ($request->hasFile('berkas_proposal')) {
            $validated['berkas_proposal'] = $this->storeFile($request->file('berkas_proposal'));
        }

        $pengajuan->update($validated);

        return $this->successResponse($request, 'Pengajuan PKL berhasil diperbarui.', $pengajuan);
    }

    public function uploadRevisi(Request $request, PengajuanPkl $pengajuan): JsonResponse|RedirectResponse
    {
        $this->assertOwnSubmission($pengajuan);
        abort_unless(in_array($pengajuan->status, [self::STATUS_SEMINAR, self::STATUS_REVISI], true), 409, 'Unggah revisi hanya tersedia setelah seminar.');

        $validated = $request->validate([
            'berkas_laporan_revisi' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'berkas_lembar_pengesahan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        foreach ($validated as $field => $file) {
            $validated[$field] = $this->storeFile($file);
        }
        $pengajuan->update($validated);
        $this->logPklActivity($pengajuan, 'Unggah Dokumen Revisi', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengunggah laporan revisi dan lembar pengesahan.');

        return $this->successResponse($request, 'Berkas revisi berhasil diunggah.', $pengajuan);
    }

    public function uploadBerkasSatuan(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->assertOwnSubmission($pengajuan);
        $allowedFields = [
            'berkas_proposal',
            'surat_jalan_pkl',
            'surat_balasan_instansi',
            'acc_pembimbing_awal',
            'berkas_kartu_bimbingan',
            'berkas_draft_laporan',
            'transkrip_sementara',
            'formulir_pendaftaran_ujian',
            'berita_acara_ujian',
            'formulir_nilai',
            'permohonan_revisi',
            'laporan_final',
            'lampiran_teknis',
            'acc_pembimbing_akhir',
            'rekap_nilai',
        ];
        abort_unless(in_array($request->input('field'), $allowedFields, true), 422, 'Jenis berkas tidak valid.');
        $field = (string) $request->input('field');
        $earlyStageFields = ['berkas_proposal', 'surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'];
        $preExamFields = ['berkas_kartu_bimbingan', 'berkas_draft_laporan', 'transkrip_sementara'];
        $isFinalDocument = ! in_array($field, array_merge($earlyStageFields, $preExamFields), true);
        $allowedStatuses = $isFinalDocument
            ? [self::STATUS_REVISI]
            : ($field === 'transkrip_sementara' || in_array($field, ['berkas_kartu_bimbingan', 'berkas_draft_laporan'], true)
                ? [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI]
                : [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI]);
        abort_unless(in_array($pengajuan->status, $allowedStatuses, true), 409, 'Berkas belum dapat diunggah pada tahap ini.');
        $technicalAttachment = $field === 'lampiran_teknis';
        $validated = $request->validate([
            'field' => ['required', Rule::in($allowedFields)],
            'file' => [
                'required',
                'file',
                'mimes:' . ($technicalAttachment ? 'pdf,jpg,jpeg,png,dwg,dxf,xls,xlsx,csv,zip' : 'pdf,jpg,jpeg,png'),
                'max:' . ($technicalAttachment ? 20480 : ($isFinalDocument ? 10240 : 2048)),
            ],
        ]);

        if (in_array($validated['field'], ['surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal', 'transkrip_sementara'], true)) {
            $berkas = $this->storePklDocument($pengajuan, $validated['field'], $request->file('file'));
        } else {
            if (in_array($validated['field'], ['berkas_proposal', 'berkas_draft_laporan', 'berkas_kartu_bimbingan'], true)) {
                if ($validated['field'] === 'berkas_proposal') {
                    $storedPath = $this->storePklDocument($pengajuan, 'proposal', $request->file('file'))->path_berkas;
                } else {
                    $storedPath = $this->storeFile($request->file('file'));
                }
                $pengajuan->update([$validated['field'] => $storedPath]);
                if (in_array($validated['field'], ['berkas_draft_laporan', 'berkas_kartu_bimbingan'], true)) {
                    $jenisBerkas = $validated['field'] === 'berkas_draft_laporan' ? 'draft_laporan' : 'kartu_konsultasi';
                    $this->storePklDocument($pengajuan, $jenisBerkas, $request->file('file'));
                }
                $stored = $storedPath;
            } else {
                $berkas = $this->storePklDocument($pengajuan, $validated['field'], $request->file('file'));
                $stored = $berkas->path_berkas;
            }
            $berkas = null;
        }

        $this->advanceAfterExamDocuments($pengajuan);

        return response()->json([
            'message' => 'Berkas berhasil diunggah.',
            'file_url' => url('pkl/view-file/' . ($berkas?->path_berkas ?? ($stored ?? $pengajuan->{$validated['field']} ?? null))),
        ]);
    }

    public function ajukanUlangRevisi(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->assertOwnSubmission($pengajuan);
        // abort_unless($pengajuan->status === self::STATUS_REVISI, 409, 'Pengajuan ini tidak sedang menunggu revisi.');
        // abort_unless($pengajuan->berkas_laporan_revisi && $pengajuan->berkas_lembar_pengesahan, 422, 'Unggah laporan revisi dan lembar pengesahan sebelum mengajukan ulang.');

        $pengajuan->update([
            // 'status' => self::STATUS_VERIFIKASI,
            'catatan_admin' => null,
        ]);
        $this->logPklActivity($pengajuan, 'Pengajuan Ulang Revisi', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengajukan ulang berkas revisi untuk diverifikasi.');

        return response()->json(['message' => 'Berkas revisi berhasil diajukan ulang.']);
    }

    public function selesaiPelaksanaan(PengajuanPkl $pengajuan): JsonResponse
    {
        $user = auth()->user();
        $student = ($user?->hasAnyRole(['mahasiswa', 'Mahasiswa']) || $this->canActAsStudent())
            ? $this->currentMahasiswa()
            : null;
        $dosen = ($user?->hasAnyRole(['dosen_pembimbing', 'dosen_penguji', 'Dosen']) || $this->canActAsLecturer())
            ? $this->actingDosenProfile()
            : null;
        $isMahasiswaPemilik = $student?->id_registrasi_mahasiswa === $pengajuan->mahasiswa_registrasi_id;
        $isDosenPembimbing = $dosen && $pengajuan->dosen_pembimbing_id === $dosen->id_dosen;
        abort_unless($isMahasiswaPemilik || $isDosenPembimbing, 403);
        abort_unless($pengajuan->status === self::STATUS_PELAKSANAAN, 409, 'Pengajuan tidak sedang dalam tahap pelaksanaan.');

        $pengajuan->update(['status' => self::STATUS_VERIFIKASI]);
        $aktor = $isMahasiswaPemilik ? 'Mahasiswa' : 'Dosen pembimbing';
        $this->logPklActivity($pengajuan, 'Pelaksanaan PKL Selesai', $aktor . ' ' . (auth()->user()->name ?? '') . ' menyatakan kegiatan PKL di instansi selesai.');
        $this->advanceAfterExamDocuments($pengajuan);

        return response()->json(['message' => 'Pelaksanaan PKL ditandai selesai. Status sekarang: ' . $pengajuan->fresh()->status . '.']);
    }

    public function adminIndex(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();
        $submissions = $this->filteredSubmissions($request)->with(['logAktivitas.aktor', 'berkasSeminar'])->paginate(15);
        $stats = $this->statusStats();

        $submissions->getCollection()->each(function ($pengajuan) {
            $usulan = $pengajuan->logAktivitas
                ->where('aksi', 'Usulan Mahasiswa')
                ->sortByDesc('created_at')
                ->first();
            $deskripsi = $usulan?->deskripsi ?? '';
            preg_match('/Usulan tanggal ujian:\\s*([^;]+)/u', $deskripsi, $tanggal);
            preg_match('/Usulan ruangan:\\s*([^;]+)/u', $deskripsi, $ruangan);
            preg_match('/usulan dosen pembimbing:\\s*(.+?)(?:;|\\.|$)/u', $deskripsi, $pembimbing);
            $pengajuan->setAttribute('usulan_tanggal_seminar', $pengajuan->usulan_tanggal_seminar?->format('Y-m-d\TH:i') ?: trim($tanggal[1] ?? ''));
            $pengajuan->setAttribute('usulan_ruangan_seminar', $pengajuan->usulan_ruangan_seminar ?: trim($ruangan[1] ?? ''));
            $pengajuan->setAttribute('usulan_nama_pembimbing', trim($pembimbing[1] ?? ''));
        });

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pkl.partials.admin_table', compact('submissions'))->render(),
                'stats' => $stats,
            ]);
        }

        return view('pkl.admin_dashboard', [
            'submissions' => $submissions,
            'stats' => $stats,
            'dosens' => Dosen::where('aktif', true)->orderBy('nama_dosen')->get(),
            'mahasiswas' => MahasiswaRegistrasi::where('aktif', true)->orderBy('nama_mahasiswa')->get(),
            'semesters' => Semester::query()->orderByDesc('tahun_akademik')->orderBy('periode')->get(),
        ]);
    }

    public function adminStore(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'mahasiswa_registrasi_id' => ['required', 'exists:mahasiswa_registrasis,id_registrasi_mahasiswa'],
            'nama_instansi' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['nullable', 'string', 'max:255'],
            'alamat_instansi' => ['required', 'string', 'max:5000'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);
        abort_if(PengajuanPkl::where('mahasiswa_registrasi_id', $validated['mahasiswa_registrasi_id'])->exists(), 409, 'Mahasiswa ini sudah memiliki pengajuan PKL.');

        $semester = Semester::query()->where('aktif', true)->first();
        abort_unless($semester, 409, 'Semester aktif belum diatur. Atur semester aktif terlebih dahulu.');
        $validated['semester_master_id'] = $semester->id;
        $validated['id_semester'] = $semester->kode_semester;
        $validated['status'] = self::STATUS_PENGAJUAN;
        $pengajuan = PengajuanPkl::create($validated);
        $this->logPklActivity($pengajuan, 'Pengajuan PKL Dibuat Admin', 'Admin ' . (auth()->user()->name ?? '') . ' mendaftarkan pengajuan PKL untuk mahasiswa.');

        return $this->successResponse($request, 'Pengajuan PKL berhasil dibuat.', $pengajuan, 201);
    }

    public function adminEdit(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();

        return response()->json([
            'data' => $pengajuan->load(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji']),
        ]);
    }

    public function adminUpdate(Request $request, PengajuanPkl $pengajuan): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:255'],
            'alamat_instansi' => ['required', 'string', 'max:5000'],
            'unit_kerja' => ['nullable', 'string', 'max:255'],
            'dosen_pembimbing_id' => ['nullable', Rule::exists('dosens', 'id_dosen')->where('aktif', true)],
            'semester_master_id' => ['required', 'exists:semesters,id'],
            'sk_tugas' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statuses())],
            'catatan_admin' => ['nullable', 'string', 'max:5000'],
        ]);
        $statusSebelumnya = $pengajuan->status;
        $pembimbingSebelumnya = $pengajuan->dosen_pembimbing_id;
        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        if (
            ! $pembimbingSebelumnya
            && ! empty($validated['dosen_pembimbing_id'])
            && $validated['status'] === self::STATUS_PENGAJUAN
        ) {
            $validated['status'] = self::STATUS_PELAKSANAAN;
        }
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $pengajuan->update($validated);
        if ($pembimbingSebelumnya && $pembimbingSebelumnya !== $pengajuan->dosen_pembimbing_id) {
            $pengajuan->dosenPenguji()->detach();
            $pengajuan->detailNilai()->delete();
            $pengajuan->update([
                'is_acc_seminar' => false,
                'acc_seminar_by' => null,
                'acc_seminar_at' => null,
                'final_approved_by' => null,
                'final_approved_at' => null,
                'tanggal_seminar' => null,
                'ruangan_seminar' => null,
                'schedule_approved_by' => null,
                'schedule_approved_at' => null,
            ]);
        }
        if ($pembimbingSebelumnya !== $pengajuan->dosen_pembimbing_id) {
            $pengajuan->load('dosenPembimbing');
            $this->logPklActivity($pengajuan, 'Plotting Pembimbing', 'Admin ' . (auth()->user()->name ?? '') . ' menetapkan dosen pembimbing: ' . ($pengajuan->dosenPembimbing?->nama_dosen ?? 'Belum ditentukan') . '.');
        }
        if ($tanggalSebelumnya !== $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s') || $ruanganSebelumnya !== $pengajuan->ruangan_seminar) {
            $this->logPklActivity($pengajuan, 'Perubahan Jadwal oleh Admin', 'Admin ' . (auth()->user()->name ?? '') . ' menetapkan jadwal ' . ($pengajuan->tanggal_seminar?->format('d-m-Y H:i') ?? '-') . ' di ' . ($pengajuan->ruangan_seminar ?: 'ruangan belum ditentukan') . '.');
        }
        if (($validated['status'] ?? null) !== ($statusSebelumnya ?? null)) {
            $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Admin ' . auth()->user()->name . ' mengubah status dari ' . $statusSebelumnya . ' menjadi ' . $pengajuan->status . '.');
        }
        $this->logPklActivity($pengajuan, 'Perubahan Pengajuan oleh Admin', 'Admin ' . auth()->user()->name . ' memperbarui data pengajuan PKL.');
        $pengajuan->load('dosenPembimbing');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Data pengajuan PKL berhasil diperbarui.',
                'data' => $pengajuan,
                'stats' => $this->statusStats(),
            ]);
        }

        return back()->with('success', 'Data pengajuan PKL berhasil diperbarui.');
    }

    public function adminApplyStudentProposals(PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorizeAdmin();
        $pembimbingSebelumnya = $pengajuan->dosen_pembimbing_id;
        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        abort_unless(request()->filled('tanggal_seminar') && request()->filled('ruangan_seminar') && request()->filled('dosen_pembimbing_id'), 422, 'Pilih dosen pembimbing dan usulan jadwal/ruangan yang ingin ditetapkan.');
        $validated = request()->validate([
            'dosen_pembimbing_id' => ['required', 'exists:dosens,id_dosen'],
            'tanggal_seminar' => ['required', 'date', 'after:now'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
        ]);
        $pembimbingBaru = Dosen::findOrFail($validated['dosen_pembimbing_id']);
        abort_unless($pembimbingSebelumnya === $validated['dosen_pembimbing_id'], 409, 'Setelah pembimbing diganti, dosen pembimbing baru harus memberikan ACC sebelum jadwal ditetapkan.');
        abort_unless($pengajuan->is_acc_seminar && $this->examDocumentsApproved($pengajuan), 409, 'Berkas praujian harus diverifikasi dan mendapat ACC pembimbing sebelum jadwal ditetapkan.');
        $pengajuan->update([
            'dosen_pembimbing_id' => $validated['dosen_pembimbing_id'],
            'tanggal_seminar' => $validated['tanggal_seminar'],
            'ruangan_seminar' => $validated['ruangan_seminar'],
            'schedule_approved_by' => auth()->id(),
            'schedule_approved_at' => now(),
            'usulan_tanggal_seminar' => null,
            'usulan_ruangan_seminar' => null,
            'catatan_usulan_jadwal' => null,
        ]);
        $dokumenUjianLengkap = $this->examDocumentsApproved($pengajuan);
        $statusSebelumnya = $pengajuan->status;
        if ($dokumenUjianLengkap) {
            $pengajuan->update(['status' => self::STATUS_UJIAN]);
        }
        if ($pembimbingSebelumnya !== $pengajuan->dosen_pembimbing_id) {
            $pengajuan->dosenPenguji()->detach();
            $pengajuan->detailNilai()->delete();
            $pengajuan->update(['is_acc_seminar' => false]);
            $pengajuan->load('dosenPembimbing');
            $this->logPklActivity($pengajuan, 'Plotting Pembimbing', 'Admin ' . (auth()->user()->name ?? '') . ' menetapkan dosen pembimbing: ' . $pembimbingBaru->nama_dosen . '.');
        }
        $this->logPklActivity($pengajuan, 'Usulan Mahasiswa Disetujui Admin', 'Admin ' . auth()->user()->name . ' menetapkan dosen pembimbing ' . $pembimbingBaru->nama_dosen . ', tanggal ' . ($pengajuan->tanggal_seminar?->format('d-m-Y H:i') ?? '-') . ', dan ruangan ' . ($pengajuan->ruangan_seminar ?: '-') . '. Jadwal sebelumnya: ' . ($tanggalSebelumnya ?: '-') . ' / ' . ($ruanganSebelumnya ?: '-') . '.');
        if ($dokumenUjianLengkap && $statusSebelumnya !== self::STATUS_UJIAN) {
            $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Admin ' . auth()->user()->name . ' mengubah status dari ' . $statusSebelumnya . ' menjadi ' . self::STATUS_UJIAN . ' saat menetapkan jadwal ujian.');
        }
        if ($tanggalSebelumnya !== $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s') || $ruanganSebelumnya !== $pengajuan->ruangan_seminar) {
            $this->logPklActivity($pengajuan, 'Jadwal Ujian Ditetapkan', 'Admin ' . auth()->user()->name . ' menetapkan atau mengubah jadwal ujian.');
        }
        $invitationSent = $this->sendExamInvitation($pengajuan);

        return back()->with(
            $invitationSent ? 'success' : 'warning',
            $invitationSent
                ? 'Usulan mahasiswa diterapkan dan undangan dikirim ke dosen terkait.'
                : 'Usulan jadwal tersimpan, tetapi undangan belum dapat dikirim karena email dosen belum tersedia.'
        );
    }

    public function verifyDocuments(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        abort_unless($pengajuan->status === self::STATUS_VERIFIKASI, 409, 'Berkas hanya dapat diverifikasi pada tahap Verifikasi Berkas Admin.');
        $validated = $request->validate([
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_admin' => ['required_if:keputusan,ditolak', 'nullable', 'string', 'max:5000'],
        ]);

        if ($validated['keputusan'] === 'disetujui') {
            $requiredDocumentKinds = ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'];
            $uploadedRequiredKinds = $pengajuan->berkasSeminar()
                ->whereIn('jenis_berkas', $requiredDocumentKinds)
                ->whereNotNull('path_berkas')
                ->pluck('jenis_berkas')
                ->unique();
            abort_unless($uploadedRequiredKinds->count() === count($requiredDocumentKinds), 422, 'Draft laporan, kartu konsultasi, dan transkrip sementara wajib diunggah sebelum disetujui.');
            $statusSebelumnya = $pengajuan->status;
            $statusBaru = self::STATUS_UJIAN;
            $pengajuan->update(['status' => $statusBaru, 'catatan_admin' => null]);
            $pengajuan->berkasSeminar()
                ->whereNotNull('path_berkas')
                ->update([
                    'status_verifikasi' => 'Valid',
                    'catatan_verifikator' => null,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            $this->logPklActivity($pengajuan, 'Verifikasi Berkas Disetujui', 'Admin ' . (auth()->user()->name ?? '') . ' menyetujui berkas yang diunggah setelah tiga dokumen wajib praujian dinyatakan lengkap.');
            if ($statusSebelumnya !== $statusBaru) {
                $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Admin ' . auth()->user()->name . ' mengubah status dari ' . $statusSebelumnya . ' menjadi ' . $statusBaru . '.');
            }
        } else {
            $pengajuan->update([
                'catatan_admin' => $validated['catatan_admin'],
            ]);
            $pengajuan->berkasSeminar()
                ->whereNotNull('path_berkas')
                ->update([
                    'status_verifikasi' => 'Ditolak',
                    'catatan_verifikator' => $validated['catatan_admin'],
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            $this->logPklActivity($pengajuan, 'Berkas Perlu Revisi', 'Admin ' . (auth()->user()->name ?? '') . ' meminta perbaikan berkas: ' . $validated['catatan_admin']);
        }

        return response()->json(['message' => 'Keputusan verifikasi berhasil disimpan.']);
    }

    public function adminFinalizePkl(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        abort_unless($pengajuan->status === self::STATUS_REVISI, 409, 'Penyelesaian PKL hanya dapat di-ACC pada tahap Revisi Laporan.');
        abort_unless($this->hasFinalDocumentsAndScore($pengajuan), 409, 'Seluruh berkas akhir dan nilai dosen wajib lengkap sebelum status dapat diselesaikan.');

        DB::transaction(function () use ($pengajuan) {
            $pengajuan->update([
                'final_approved_by' => auth()->id(),
                'final_approved_at' => now(),
            ]);
            $this->logPklActivity($pengajuan, 'ACC Penyelesaian PKL', 'Admin ' . (auth()->user()->name ?? '') . ' memberikan ACC penyelesaian PKL.');
            abort_unless($this->completePklIfReady($pengajuan), 409, 'Status PKL belum dapat diselesaikan. Periksa kembali kelengkapan dokumen dan nilai.');
        });

        return response()->json(['message' => 'PKL berhasil di-ACC dan status mahasiswa menjadi Selesai.']);
    }

    public function adminReviewFinalDocument(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        abort_unless($pengajuan->status === self::STATUS_REVISI, 409, 'Berkas akhir hanya dapat diverifikasi pada tahap Revisi Laporan.');
        $validated = $request->validate([
            'jenis_berkas' => ['required', Rule::in([
                'formulir_pendaftaran_ujian',
                'berita_acara_ujian',
                'formulir_nilai',
                'permohonan_revisi',
                'laporan_final',
                'lampiran_teknis',
                'acc_pembimbing_akhir',
                'rekap_nilai',
            ])],
            'status' => ['required', Rule::in(['Valid', 'Ditolak'])],
            'catatan' => ['required_if:status,Ditolak', 'nullable', 'string', 'max:5000'],
        ]);
        $berkas = $pengajuan->berkasSeminar()
            ->where('jenis_berkas', $validated['jenis_berkas'])
            ->whereNotNull('path_berkas')
            ->firstOrFail();
        $isApproved = $validated['status'] === 'Valid';
        $berkas->update([
            'status_verifikasi' => $validated['status'],
            'catatan_verifikator' => $isApproved ? null : $validated['catatan'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'approved_by' => $isApproved ? auth()->id() : null,
            'approved_at' => $isApproved ? now() : null,
        ]);

        $label = $this->routeMapDocumentCatalog()[$validated['jenis_berkas']]['label'] ?? str_replace('_', ' ', $validated['jenis_berkas']);
        $this->logPklActivity(
            $pengajuan,
            $isApproved ? 'Berkas Akhir Di-ACC' : 'Berkas Akhir Perlu Revisi',
            'Admin ' . (auth()->user()->name ?? '') . ($isApproved ? ' menyetujui ' : ' meminta revisi ') . $label . ($isApproved ? '.' : ': ' . $validated['catatan'])
        );

        return response()->json(['message' => $isApproved ? 'Berkas berhasil di-ACC.' : 'Permintaan revisi berkas berhasil disimpan.']);
    }

    public function setSchedule(Request $request, PengajuanPkl $pengajuan): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($this->examDocumentsApproved($pengajuan), 409, 'Jadwal hanya dapat ditetapkan setelah berkas praujian diverifikasi admin.');
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date', 'after:now'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
            'penguji' => ['required', 'array', 'min:1'],
            'penguji.*.dosen_id' => ['required', 'distinct', 'exists:dosens,id_dosen'],
            'penguji.*.peran' => ['required', Rule::in(['Ketua Penguji', 'Anggota Penguji'])],
        ]);

        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        DB::transaction(function () use ($pengajuan, $validated) {
            $pengajuan->dosenPenguji()->sync(collect($validated['penguji'])->mapWithKeys(
                fn($penguji) => [$penguji['dosen_id'] => ['peran' => $penguji['peran']]]
            )->all());
            $pengajuan->update([
                'tanggal_seminar' => $validated['tanggal_seminar'],
                'ruangan_seminar' => $validated['ruangan_seminar'],
                'status' => self::STATUS_UJIAN,
                'schedule_approved_by' => auth()->id(),
                'schedule_approved_at' => now(),
                'usulan_tanggal_seminar' => null,
                'usulan_ruangan_seminar' => null,
                'catatan_usulan_jadwal' => null,
                'usulan_dosen_penguji' => null,
            ]);
        });
        $isReschedule = $tanggalSebelumnya !== null || $ruanganSebelumnya !== null;
        $this->logPklActivity(
            $pengajuan,
            $isReschedule ? 'Jadwal Ujian Direvisi' : 'Jadwal Ujian Ditetapkan',
            'Admin ' . auth()->user()->name . ($isReschedule ? ' merevisi' : ' menetapkan')
                . ' jadwal ujian ' . $validated['tanggal_seminar'] . ' di ' . $validated['ruangan_seminar']
                . ' dengan ' . count($validated['penguji']) . ' dosen penguji. Jadwal sebelumnya: '
                . ($tanggalSebelumnya ?: '-') . ' / ' . ($ruanganSebelumnya ?: '-') . '.'
        );
        $invitationSent = $this->sendExamInvitation($pengajuan);

        $message = $invitationSent
            ? 'Jadwal seminar berhasil disimpan dan undangan dikirim ke dosen terkait.'
            : 'Jadwal seminar tersimpan, tetapi undangan belum dapat dikirim karena email dosen belum tersedia.';

        return $this->successResponse($request, $message, $pengajuan->load('dosenPenguji'));
    }

    public function adminGrades(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $pengajuan->load(['dosenPembimbing', 'dosenPenguji']);
        $komponens = $this->componentsForSubmission($pengajuan)->orderBy('id')->get();
        $existingNilai = $pengajuan->detailNilai()
            ->get()
            ->groupBy('dosen_id')
            ->map(fn($rows) => $rows->keyBy('komponen_penilaian_pkl_id'));

        return response()->json([
            'data' => [
                'pkl' => $pengajuan,
                'komponens' => $komponens,
                'existing_nilai' => $existingNilai,
            ],
        ]);
    }

    public function saveAdminGrades(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate(['nilai' => ['required', 'array']]);
        $allowedDosenIds = collect([$pengajuan->dosen_pembimbing_id])
            ->merge($pengajuan->dosenPenguji()->pluck('dosens.id_dosen'))
            ->filter()
            ->unique()
            ->mapWithKeys(fn($id) => [(string) $id => true]);
        $components = $this->componentsForSubmission($pengajuan)->get()->keyBy('id');
        if ($components->contains(fn($component) => $component->penilai === 'Instansi')) {
            $allowedDosenIds->put('PENANGGUNG_LAPANGAN', true);
        }
        abort_if($components->isEmpty(), 422, 'Belum ada komponen penilaian untuk semester ini.');
        foreach ($allowedDosenIds->keys() as $dosenId) {
            $category = $dosenId === 'PENANGGUNG_LAPANGAN'
                ? 'Instansi'
                : ($dosenId === $pengajuan->dosen_pembimbing_id ? 'Pembimbing' : 'Penguji');
            $expectedComponentIds = $components->filter(fn($component) => $component->penilai === $category)
                ->keys()->map(fn($id) => (string) $id)->all();
            $submittedIds = array_map('strval', array_keys($validated['nilai'][$dosenId] ?? []));
            abort_if(array_diff($expectedComponentIds, $submittedIds), 422, 'Nilai seluruh komponen untuk setiap kategori penilai wajib diisi.');
        }
        $komponens = $components->only(collect($validated['nilai'])->flatMap(fn($scores) => array_keys($scores))->unique()->all());

        DB::transaction(function () use ($validated, $pengajuan, $allowedDosenIds, $komponens) {
            $weightedValues = [];
            foreach ($validated['nilai'] as $dosenId => $scores) {
                abort_unless($allowedDosenIds->has((string) $dosenId), 422, 'Penilai tidak sesuai dengan pengajuan ini.');
                foreach ($scores as $komponenId => $score) {
                    $komponen = $komponens->get($komponenId);
                    abort_unless($komponen, 422, 'Komponen penilaian tidak valid.');
                    $expectedCategory = (string) $dosenId === 'PENANGGUNG_LAPANGAN'
                        ? 'Instansi'
                        : ((string) $dosenId === $pengajuan->dosen_pembimbing_id ? 'Pembimbing' : 'Penguji');
                    abort_unless($komponen->penilai === $expectedCategory, 422, 'Komponen tidak sesuai kategori penilai.');
                    validator(['score' => $score], ['score' => ['required', 'numeric', 'between:0,100']])->validate();

                    DetailNilaiPkl::updateOrCreate(
                        [
                            'pengajuan_pkl_id' => $pengajuan->id,
                            'dosen_id' => $dosenId,
                            'komponen_penilaian_pkl_id' => $komponenId,
                        ],
                        ['nilai_angka' => $score]
                    );
                    $weightedValues[$komponenId]['scores'][] = (float) $score;
                    $weightedValues[$komponenId]['bobot'] = (float) $komponen->bobot;
                }
            }

            $bobotTotal = array_sum(array_column($weightedValues, 'bobot'));
            if ($bobotTotal > 0) {
                $nilaiAkhir = collect($weightedValues)->sum(
                    fn($item) => (array_sum($item['scores']) / count($item['scores'])) * $item['bobot']
                ) / $bobotTotal;
                $pengajuan->update([
                    'nilai_akhir' => round($nilaiAkhir, 2),
                    'nilai_huruf' => $this->letterGrade($nilaiAkhir),
                ]);
            }
        });
        $this->markRevisionAfterGradeEntry($pengajuan);
        $this->refreshFinalGrade($pengajuan);
        $this->completePklIfReady($pengajuan);

        return response()->json(['message' => 'Nilai seminar berhasil disimpan.']);
    }

    public function invitation(PengajuanPkl $pengajuan): View
    {
        $pengajuan->load(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji']);
        $user = auth()->user();
        $isAdmin = $user?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'])
            || $user?->can('academic-edit');
        $isStudent = $this->mahasiswaProfilesForUser()->whereKey($pengajuan->mahasiswa_registrasi_id)->exists();
        $isAssignedDosen = $this->dosenProfilesForUser()
            ->where(function ($query) use ($pengajuan) {
                $query->whereKey($pengajuan->dosen_pembimbing_id)
                    ->orWhereHas('pengajuanDiuji', fn($query) => $query->whereKey($pengajuan->id));
            })
            ->exists();
        abort_unless($isAdmin || $isStudent || $isAssignedDosen, 403);
        abort_unless($pengajuan->tanggal_seminar && $pengajuan->ruangan_seminar, 404, 'Undangan tersedia setelah jadwal dan ruangan ditetapkan admin.');

        return view('pkl.invitation', compact('pengajuan'));
    }

    public function gradeRecap(Request $request): View
    {
        $this->authorizeAdmin();
        $submissions = $this->filteredSubmissions($request)
            ->with(['detailNilai.komponen', 'mahasiswaRegistrasi'])
            ->get()
            ->each(function ($pengajuan) {
                $pengajuan->setAttribute('nilai_rekap', $this->weightedFinalScore($pengajuan));
            });

        return view('pkl.grade_recap', compact('submissions'));
    }

    public function exportGradeRecap(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();
        $submissions = $this->filteredSubmissions($request)
            ->with(['detailNilai.komponen', 'mahasiswaRegistrasi'])
            ->get();

        return response()->streamDownload(function () use ($submissions) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['NIM', 'Nama Mahasiswa', 'Instansi', 'Semester', 'Nilai Akhir', 'Nilai Huruf', 'Status']);
            foreach ($submissions as $pengajuan) {
                $score = $this->weightedFinalScore($pengajuan);
                fputcsv($output, [
                    $this->csvSafe($pengajuan->mahasiswaRegistrasi?->nim),
                    $this->csvSafe($pengajuan->mahasiswaRegistrasi?->nama_mahasiswa),
                    $this->csvSafe($pengajuan->nama_instansi),
                    $this->csvSafe($pengajuan->id_semester),
                    $score,
                    $score === null ? $pengajuan->nilai_huruf : $this->letterGrade($score),
                    $this->csvSafe($pengajuan->status),
                ]);
            }
            fclose($output);
        }, 'rekap-nilai-pkl.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function componentsIndex(Request $request): View
    {
        $this->authorizeAdmin();
        $defaultSemester = Semester::query()->where('aktif', true)->first()
            ?? Semester::query()->orderByDesc('tahun_akademik')->orderBy('periode')->first();
        $selectedSemester = (string) $request->input('id_semester', $defaultSemester?->kode_semester ?? '');
        $semester = Semester::query()->where('kode_semester', $selectedSemester)->first();
        if (!$semester && ctype_digit($selectedSemester)) {
            $semester = Semester::find((int) $selectedSemester);
        }
        $selectedSemester = $semester?->kode_semester ?? $selectedSemester;
        $komponens = KomponenPenilaianPkl::query()
            ->when($semester, fn($query) => $query->where(function ($query) use ($semester, $selectedSemester) {
                $query->where('semester_master_id', $semester->id)
                    ->orWhere(fn($query) => $query->whereNull('semester_master_id')->where('id_semester', $selectedSemester));
            }), fn($query) => $query->where('id_semester', $selectedSemester))
            ->orderBy('id')
            ->get();
        $komponenByPenilai = $komponens->groupBy(fn($komponen) => $this->normalizeAssessor($komponen->penilai));
        $totalBobotByPenilai = collect(['Penguji', 'Pembimbing', 'Instansi'])
            ->mapWithKeys(fn($penilai) => [$penilai => round(($komponenByPenilai[$penilai] ?? collect())->sum('bobot'), 2)]);

        return view('pkl.admin_komponen_penilaian', [
            'komponens' => $komponens,
            'komponenByPenilai' => $komponenByPenilai,
            'totalBobotByPenilai' => $totalBobotByPenilai,
            'semesters' => Semester::query()->orderByDesc('tahun_akademik')->orderBy('periode')->get(),
            'selectedSemester' => $selectedSemester,
            'selectedSemesterMasterId' => $semester?->id,
            'totalBobot' => round($komponens->sum('bobot'), 2),
        ]);
    }

    public function componentStore(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'semester_master_id' => ['required', 'exists:semesters,id'],
            'penilai' => ['required', Rule::in(['Penguji', 'Pembimbing', 'Instansi'])],
            'nama_komponen' => ['required', 'string', 'max:255'],
            'bobot' => ['required', 'numeric', 'between:0,100'],
        ]);
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $component = KomponenPenilaianPkl::create($validated + [
            'bobot_persen' => $validated['bobot'],
            'is_aktif' => true,
        ]);

        return $this->successResponse($request, 'Komponen penilaian berhasil ditambahkan.', $component, 201);
    }

    public function componentUpdate(Request $request, KomponenPenilaianPkl $komponen): JsonResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'semester_master_id' => ['required', 'exists:semesters,id'],
            'penilai' => ['required', Rule::in(['Penguji', 'Pembimbing', 'Instansi'])],
            'nama_komponen' => ['required', 'string', 'max:255'],
            'bobot' => ['required', 'numeric', 'between:0,100'],
        ]);
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $komponen->update($validated + [
            'bobot_persen' => $validated['bobot'],
            'is_aktif' => true,
        ]);

        return response()->json(['message' => 'Komponen penilaian berhasil diperbarui.']);
    }

    public function componentDestroy(KomponenPenilaianPkl $komponen): JsonResponse
    {
        $this->authorizeAdmin();
        $komponen->delete();

        return response()->json(['message' => 'Komponen penilaian berhasil dihapus.']);
    }

    public function dosenIndex(Request $request): View|JsonResponse
    {
        $isPrivileged = $this->canSelectActingProfile();
        if ($isPrivileged && $request->filled('id_dosen')) {
            $selected = Dosen::query()->where('aktif', true)->find($request->input('id_dosen'));
            abort_unless($selected, 404, 'Profil dosen aktif tidak ditemukan.');
            $request->session()->put('pkl.dosen_profile_id', $selected->id_dosen);
        }
        $dosen = $isPrivileged && $request->session()->has('pkl.dosen_profile_id')
            ? Dosen::query()->where('aktif', true)->find($request->session()->get('pkl.dosen_profile_id'))
            : $this->dosenProfilesForUser()->first();

        if (! $dosen && $isPrivileged) {
            $request->session()->forget('pkl.dosen_profile_id');
            return view('pkl.profile_selector', [
                'jenis' => 'dosen',
                'profiles' => Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get(),
                'route' => route('pkl.dosen.index'),
                'parameter' => 'id_dosen',
                'title' => 'Pilih Profil Dosen',
                'description' => 'Akun ini tidak terhubung langsung ke satu profil dosen. Pilih profil untuk melihat dashboard dosen.',
            ]);
        }
        abort_unless($dosen, 403, 'Profil dosen aktif untuk akun ini belum tersedia. Hubungi administrator untuk memperbarui identitas akun.');
        if ($isPrivileged) {
            $request->session()->put('pkl.dosen_profile_id', $dosen->id_dosen);
        }
        $query = PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPenguji', 'dosenPembimbing'])
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_pembimbing_id', $dosen->id_dosen)
                    ->orWhereHas('dosenPenguji', fn($query) => $query->where('dosens.id_dosen', $dosen->id_dosen));
            });
        $this->applySemesterFilter($query, $request->input('id_semester'));
        $assignments = $query->latest()->get();
        $bimbinganList = $assignments->where('dosen_pembimbing_id', $dosen->id_dosen)->values();
        $pengujiList = $assignments->filter(fn($item) => $item->dosenPenguji->contains('id_dosen', $dosen->id_dosen))->values();
        $totalBimbingan = $bimbinganList->count();
        $totalPenguji = $pengujiList->count();

        if ($request->ajax()) {
            return view('pkl.partials.dosen_content_tables', compact('bimbinganList', 'pengujiList', 'totalBimbingan', 'totalPenguji'));
        }

        return view('pkl.dosen_dashboard', [
            'bimbinganList' => $bimbinganList,
            'pengujiList' => $pengujiList,
            'totalBimbingan' => $totalBimbingan,
            'totalPenguji' => $totalPenguji,
            'semesters' => Semester::query()->orderByDesc('tahun_akademik')->orderBy('periode')->get(),
            'dosen' => $dosen,
            'isActingAsProfile' => $isPrivileged,
            'availableProfiles' => $isPrivileged
                ? Dosen::query()->where('aktif', true)->orderBy('nama_dosen')->get()
                : collect(),
        ]);
    }

    public function dosenDetail(PengajuanPkl $pengajuan): View
    {
        $user = auth()->user();
        $isAdmin = $user?->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'])
            || $user?->can('academic-edit');
        $dosen = $this->actingDosenProfile();
        $isAssignedDosen = $dosen && $this->isAssignedDosen($pengajuan, $dosen);
        abort_unless($isAdmin || $isAssignedDosen, 403);
        $canApproveFinal = $isAssignedDosen && $pengajuan->dosen_pembimbing_id === $dosen->id_dosen;
        $pengajuan->load([
            'mahasiswaRegistrasi',
            'logAktivitas.aktor',
            'bimbingan.tanggapan.pengirim',
        ]);

        return view('pkl.dosen_bimbingan_detail', compact('pengajuan', 'isAdmin', 'canApproveFinal'));
    }

    public function dosenFinalApproval(PengajuanPkl $pengajuan): RedirectResponse
    {
        return $this->routeMapComplete($pengajuan);
    }

    public function dosenComponents(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        $categories = collect();
        if ($pengajuan->dosen_pembimbing_id === $dosen->id_dosen) {
            $categories->push('Pembimbing');
        }
        if ($pengajuan->dosenPenguji()->where('dosens.id_dosen', $dosen->id_dosen)->exists()) {
            $categories->push('Penguji');
        }
        $komponen = $this->componentsForSubmission($pengajuan)
            ->whereIn('penilai', $categories->unique())
            ->with('detailNilai')
            ->get();
        $existing = $pengajuan->detailNilai()->where('dosen_id', $dosen->id_dosen)->get()->keyBy('komponen_penilaian_pkl_id');
        $catatan = $pengajuan->logAktivitas()
            ->where('aksi', 'Penilaian Seminar')
            ->latest()
            ->value('deskripsi');
        $html = view('pkl.partials.komponen_nilai_items', [
            'komponen' => $komponen,
            'dosenId' => $dosen->id_dosen,
            'existingNilai' => $existing,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'nama_mahasiswa' => $pengajuan->mahasiswaRegistrasi?->nama_mahasiswa ?? '-',
            'nim' => $pengajuan->mahasiswaRegistrasi?->nim ?? '-',
            'catatan' => ['catatan' => $catatan ?? ''],
        ]);
    }

    public function dosenSaveGrades(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeLecturerAction();
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        $validated = $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.' . $dosen->id_dosen => ['required', 'array'],
            'nilai.' . $dosen->id_dosen . '.*' => ['required', 'numeric', 'between:0,100'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);
        $components = KomponenPenilaianPkl::query()
            ->whereIn('id', array_keys($validated['nilai'][$dosen->id_dosen]))
            ->when($pengajuan->id_semester, fn($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->whereIn('penilai', collect([
                $pengajuan->dosen_pembimbing_id === $dosen->id_dosen ? 'Pembimbing' : null,
                $pengajuan->dosenPenguji()->where('dosens.id_dosen', $dosen->id_dosen)->exists() ? 'Penguji' : null,
            ])->filter()->unique())
            ->get()
            ->keyBy('id');
        abort_unless($components->count() === count($validated['nilai'][$dosen->id_dosen]), 422, 'Komponen nilai tidak valid.');

        DB::transaction(function () use ($validated, $dosen, $pengajuan) {
            foreach ($validated['nilai'][$dosen->id_dosen] as $componentId => $score) {
                DetailNilaiPkl::updateOrCreate(
                    [
                        'pengajuan_pkl_id' => $pengajuan->id,
                        'dosen_id' => $dosen->id_dosen,
                        'komponen_penilaian_pkl_id' => $componentId,
                    ],
                    ['nilai_angka' => $score]
                );
            }
            if (filled($validated['catatan'] ?? null)) {
                LogAktivitasPkl::create([
                    'pengajuan_pkl_id' => $pengajuan->id,
                    'aktor_id' => auth()->id(),
                    'aksi' => 'Penilaian Seminar',
                    'deskripsi' => $validated['catatan'],
                ]);
            }
        });
        $this->markRevisionAfterGradeEntry($pengajuan);
        $this->refreshFinalGrade($pengajuan);
        $this->logPklActivity($pengajuan, 'Penilaian PKL', 'Dosen ' . (auth()->user()->name ?? '') . ' menyimpan nilai seminar dan evaluasi.');
        if ($pengajuan->dosen_pembimbing_id === $dosen->id_dosen) {
            $this->completePklIfReady($pengajuan);
        }

        return response()->json(['message' => 'Nilai seminar berhasil disimpan.']);
    }

    public function viewFile(string $path): StreamedResponse
    {
        $normalizedPath = str_replace('\\', '/', $path);
        abort_if(Str::contains($normalizedPath, ['..', "\0"]), 404);

        $pengajuan = $this->findSubmissionByFile($normalizedPath);
        abort_unless($pengajuan, 404);

        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'])
            || $user->can('academic-edit');

        $student = $this->mahasiswaProfilesForUser()->whereKey($pengajuan->mahasiswa_registrasi_id)->exists();

        $dosen = $this->dosenProfilesForUser()
            ->where(function ($query) use ($pengajuan) {
                $query->whereKey($pengajuan->dosen_pembimbing_id)
                    ->orWhereHas('pengajuanDiuji', fn($query) => $query->whereKey($pengajuan->id));
            })
            ->exists();

        abort_unless($isAdmin || $student || $dosen, 403);

        // Pastikan file benar-benar ada di disk local sebelum di-stream
        abort_unless(Storage::disk('local')->exists($normalizedPath), 404);

        // PERBAIKAN: Gunakan response() dengan disposition 'inline' agar tampil di iframe modal
        return Storage::disk('local')->response(
            $normalizedPath,
            basename($normalizedPath),
            [
                'Content-Disposition' => 'inline; filename="' . basename($normalizedPath) . '"'
            ]
        );
    }

    private function filteredSubmissions(Request $request)
    {
        $query = PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji', 'berkasSeminar'])
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->input('status')));
        $this->applySemesterFilter($query, $request->input('id_semester'));

        return $query
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->string('keyword')->toString();
                $query->where(function ($query) use ($keyword) {
                    $query->where('nama_instansi', 'like', "%{$keyword}%")
                        ->orWhereHas('mahasiswaRegistrasi', function ($query) use ($keyword) {
                            $query->where('nama_mahasiswa', 'like', "%{$keyword}%")
                                ->orWhere('nim', 'like', "%{$keyword}%");
                        });
                });
            })
            ->latest();
    }

    private function applySemesterFilter($query, mixed $semesterValue): void
    {
        if (! filled($semesterValue)) {
            return;
        }

        $semesterValue = (string) $semesterValue;
        $semester = Semester::query()
            ->where('kode_semester', $semesterValue)
            ->when(ctype_digit($semesterValue), fn($query) => $query->orWhereKey((int) $semesterValue))
            ->first();

        $query->where(function ($query) use ($semester, $semesterValue) {
            if ($semester) {
                $query->where('semester_master_id', $semester->id)
                    ->orWhere('id_semester', $semester->kode_semester);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
    }

    private function componentsForSubmission(PengajuanPkl $pengajuan)
    {
        return KomponenPenilaianPkl::query()
            ->when($pengajuan->semester_master_id || $pengajuan->id_semester, function ($query) use ($pengajuan) {
                $query->where(function ($query) use ($pengajuan) {
                    if ($pengajuan->semester_master_id) {
                        $query->where('semester_master_id', $pengajuan->semester_master_id);
                    }
                    if ($pengajuan->id_semester) {
                        $query->orWhere('id_semester', $pengajuan->id_semester);
                    }
                });
            }, fn($query) => $query->whereRaw('1 = 0'));
    }

    private function weightedFinalScore(PengajuanPkl $pengajuan): ?float
    {
        $components = $this->componentsForSubmission($pengajuan)
            ->with('detailNilai')
            ->get()
            ->map(function ($component) use ($pengajuan) {
                $scores = $component->detailNilai
                    ->where('pengajuan_pkl_id', $pengajuan->id)
                    ->pluck('nilai_angka');

                return $scores->isEmpty()
                    ? null
                    : ['score' => (float) $scores->avg(), 'weight' => (float) $component->bobot];
            })
            ->filter();
        $totalWeight = $components->sum('weight');
        if ($components->isEmpty() || $totalWeight <= 0) {
            return null;
        }

        return round($components->sum(fn($component) => $component['score'] * $component['weight']) / $totalWeight, 2);
    }

    private function refreshFinalGrade(PengajuanPkl $pengajuan): void
    {
        $score = $this->weightedFinalScore($pengajuan);
        if ($score === null) {
            return;
        }

        $pengajuan->update([
            'nilai_akhir' => $score,
            'nilai_huruf' => $this->letterGrade($score),
        ]);
    }

    private function markRevisionAfterGradeEntry(PengajuanPkl $pengajuan): void
    {
        if (! in_array($pengajuan->status, [self::STATUS_UJIAN, self::STATUS_SEMINAR], true)) {
            return;
        }

        $statusSebelumnya = $pengajuan->status;
        $pengajuan->update(['status' => self::STATUS_REVISI]);
        $this->logPklActivity(
            $pengajuan,
            'Status PKL Menjadi Revisi Laporan',
            'Status berubah dari ' . $statusSebelumnya . ' menjadi ' . self::STATUS_REVISI . ' setelah dosen menyimpan nilai ujian.'
        );
    }

    private function sendExamInvitation(PengajuanPkl $pengajuan): bool
    {
        $pengajuan->loadMissing(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji']);
        $recipients = collect([$pengajuan->dosenPembimbing])
            ->merge($pengajuan->dosenPenguji)
            ->filter(fn($dosen) => filled($dosen?->email))
            ->unique(fn($dosen) => strtolower($dosen->email))
            ->values();

        if ($recipients->isEmpty()) {
            $this->logPklActivity($pengajuan, 'Undangan Ujian Belum Terkirim', 'Tidak ada email dosen pembimbing atau penguji yang tersedia untuk pengiriman undangan.');

            return false;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new PklExamInvitation($pengajuan, $recipient->nama_dosen));
        }
        $this->logPklActivity($pengajuan, 'Undangan Ujian Dikirim', 'Undangan ujian dikirim melalui email kepada: ' . $recipients->pluck('email')->implode(', ') . '.');

        return true;
    }

    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function statusStats(): array
    {
        $counts = PengajuanPkl::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pengajuan' => $counts[self::STATUS_PENGAJUAN] ?? 0,
            'pelaksanaan' => $counts[self::STATUS_PELAKSANAAN] ?? 0,
            'verifikasi' => $counts[self::STATUS_VERIFIKASI] ?? 0,
            'ujian' => $counts[self::STATUS_UJIAN] ?? 0,
            'seminar' => $counts[self::STATUS_SEMINAR] ?? 0,
            'revisi' => $counts[self::STATUS_REVISI] ?? 0,
            'selesai' => $counts[self::STATUS_SELESAI] ?? 0,
        ];
    }

    private function normalizeAssessor(?string $assessor): string
    {
        return match (strtolower(trim((string) $assessor))) {
            'instansi', 'lapangan', 'penanggung jawab lapangan', 'pamong' => 'Instansi',
            'pembimbing', 'dosen pembimbing' => 'Pembimbing',
            'penguji', 'dosen penguji' => 'Penguji',
            default => 'Penguji',
        };
    }

    private function currentMahasiswa(): MahasiswaRegistrasi
    {
        $mahasiswa = $this->canSelectActingProfile() && session()->has('pkl.mahasiswa_profile_id')
            ? MahasiswaRegistrasi::query()->where('aktif', true)->find(session('pkl.mahasiswa_profile_id'))
            : $this->mahasiswaProfilesForUser()->first();
        abort_unless($mahasiswa, 403, 'Profil mahasiswa untuk akun ini belum tertaut. Pastikan email akun sama dengan email data mahasiswa atau identifier akun berisi NIM.');

        return $mahasiswa;
    }

    private function currentDosen(): Dosen
    {
        $dosen = $this->actingDosenProfile();
        abort_unless($dosen, 403, 'Pilih profil dosen terlebih dahulu atau hubungkan identitas dosen pada akun ini.');

        return $dosen;
    }

    private function actingDosenProfile(): ?Dosen
    {
        if ($this->canSelectActingProfile() && session()->has('pkl.dosen_profile_id')) {
            return Dosen::query()->where('aktif', true)->find(session('pkl.dosen_profile_id'));
        }

        $isLecturer = auth()->user()?->hasAnyRole(['dosen_pembimbing', 'dosen_penguji', 'dosen', 'Dosen', 'DosenLB', 'Guru Besar']);
        if (! $isLecturer) {
            return null;
        }

        return $this->dosenProfilesForUser()->first();
    }

    private function authorizeStudentAction(): void
    {
        $isStudent = auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']) ?? false;
        abort_unless($isStudent || $this->canActAsStudent(), 403, 'Pilih profil mahasiswa untuk bertindak sebagai mahasiswa PKL.');
    }

    private function authorizeLecturerAction(): void
    {
        $isLecturer = auth()->user()?->hasAnyRole(['dosen_pembimbing', 'dosen_penguji', 'dosen', 'Dosen', 'DosenLB', 'Guru Besar']) ?? false;
        abort_unless($isLecturer || $this->canActAsLecturer(), 403, 'Pilih profil dosen untuk bertindak sebagai dosen PKL.');
    }

    private function mahasiswaProfilesForUser()
    {
        $user = auth()->user();

        return MahasiswaRegistrasi::query()
            ->where('aktif', true)
            ->where(function ($query) use ($user) {
                $hasIdentity = false;
                if (filled($user->email)) {
                    $query->where('email', $user->email);
                    $hasIdentity = true;
                }
                if (filled($user->identifier_number)) {
                    $hasIdentity
                        ? $query->orWhere('nim', $user->identifier_number)
                        : $query->where('nim', $user->identifier_number);
                    $hasIdentity = true;
                }
                if (!$hasIdentity) {
                    $query->whereRaw('1 = 0');
                }
            });
    }

    private function dosenProfilesForUser()
    {
        $user = auth()->user();

        return Dosen::query()
            ->where('aktif', true)
            ->where(function ($query) use ($user) {
                $hasIdentity = false;
                if (filled($user->email)) {
                    $query->where('email', $user->email);
                    $hasIdentity = true;
                }
                if (filled($user->identifier_number)) {
                    $hasIdentity
                        ? $query->orWhere('nidn', $user->identifier_number)
                        : $query->where('nidn', $user->identifier_number);
                    $hasIdentity = true;
                }
                if (!$hasIdentity) {
                    $query->whereRaw('1 = 0');
                }
            });
    }

    private function assertOwnSubmission(PengajuanPkl $pengajuan): void
    {
        $this->authorizeStudentAction();
        abort_unless(
            $pengajuan->mahasiswa_registrasi_id === $this->currentMahasiswa()->id_registrasi_mahasiswa,
            403
        );
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([
            'Super Admin',
            'Admin',
            'BAAK',
            'Akademik',
            'Kemahasiswaan',
            'Kaprodi',
            'Kajur',
        ]) || auth()->user()?->can('academic-edit'), 403);
    }

    private function storeFile(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->extension();

        return $file->storeAs('pkl', $filename, 'local');
    }

    private function findSubmissionByFile(string $path): ?PengajuanPkl
    {
        return PengajuanPkl::query()
            ->where(function ($query) use ($path) {
                foreach (self::FILE_FIELDS as $field) {
                    $query->orWhere($field, $path);
                }
            })
            ->orWhereHas('berkasSeminar', fn($query) => $query->where('path_berkas', $path))
            ->first();
    }

    private function routeMapDocumentCatalog(): array
    {
        return [
            'proposal' => ['label' => 'Proposal PKL', 'stage' => 1],
            'draft_laporan' => ['label' => 'Draft Laporan PKL', 'stage' => 2],
            'kartu_konsultasi' => ['label' => 'Kartu Konsultasi / Bimbingan', 'stage' => 2],
            'transkrip_sementara' => ['label' => 'Transkrip Sementara', 'stage' => 2],
            'surat_jalan_pkl' => ['label' => 'Surat Jalan PKL', 'stage' => 1],
            'surat_balasan_instansi' => ['label' => 'Surat Balasan Instansi', 'stage' => 1],
            'acc_pembimbing_awal' => ['label' => 'ACC Pembimbing Awal', 'stage' => 1],
            'formulir_pendaftaran_ujian' => ['label' => 'Formulir Pendaftaran Ujian', 'stage' => 3],
            'berita_acara_ujian' => ['label' => 'Berita Acara Ujian', 'stage' => 3],
            'formulir_nilai' => ['label' => 'Formulir Nilai', 'stage' => 3],
            'permohonan_revisi' => ['label' => 'Formulir Permohonan Revisi', 'stage' => 3],
            'laporan_final' => ['label' => 'Laporan Final', 'stage' => 3],
            'lampiran_teknis' => ['label' => 'Lampiran Teknis (Gambar AutoCAD / RAB)', 'stage' => 3],
            'acc_pembimbing_akhir' => ['label' => 'ACC Pembimbing Akhir', 'stage' => 3],
            'rekap_nilai' => ['label' => 'Rekap Nilai', 'stage' => 3],
        ];
    }


    private function storePklDocument(PengajuanPkl $pengajuan, string $jenis, \Illuminate\Http\UploadedFile $file)
    {
        $berkas = $pengajuan->berkasSeminar()->updateOrCreate(
            ['jenis_berkas' => $jenis],
            [
                'path_berkas' => $this->storeFile($file),
                'status_verifikasi' => 'Pending',
                'catatan_verifikator' => null,
                'verified_by' => null,
                'verified_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]
        );
        $catalog = $this->routeMapDocumentCatalog();
        $label = $catalog[$jenis]['label'] ?? $jenis;
        $this->logPklActivity($pengajuan, 'Unggah Dokumen', (auth()->user()?->name ?? 'Pengguna') . ' mengunggah atau mengganti dokumen: ' . $label . '.');

        return $berkas;
    }

    private function completePklIfReady(PengajuanPkl $pengajuan): bool
    {
        if (! $pengajuan->final_approved_at || ! $this->hasFinalDocumentsAndScore($pengajuan)) {
            return false;
        }

        $requiredFinalDocuments = [
            'formulir_pendaftaran_ujian',
            'berita_acara_ujian',
            'formulir_nilai',
            'permohonan_revisi',
            'laporan_final',
            'lampiran_teknis',
            'acc_pembimbing_akhir',
            'rekap_nilai',
        ];
        $documents = $pengajuan->berkasSeminar()->whereIn('jenis_berkas', $requiredFinalDocuments)->get()->keyBy('jenis_berkas');
        if (collect($requiredFinalDocuments)->contains(fn($jenis) => ! $documents->get($jenis)?->path_berkas)) {
            return false;
        }
        if (collect($requiredFinalDocuments)->contains(
            fn($jenis) => $documents->get($jenis)?->status_verifikasi !== 'Valid'
        )) {
            return false;
        }

        $sudahSelesai = $pengajuan->status === self::STATUS_SELESAI;
        $statusSebelumnya = $pengajuan->status;
        $pengajuan->update(['status' => self::STATUS_SELESAI]);
        $pengajuan->mahasiswaRegistrasi?->update(['status_pkl' => 'Lulus PKL']);
        if (! $sudahSelesai) {
            $this->logPklActivity($pengajuan, 'PKL Lulus', (auth()->user()?->name ?? 'Pengguna') . ' menyelesaikan proses PKL dari status ' . $statusSebelumnya . '. Syarat dokumen akhir, ACC pembimbing, dan penilaian pembimbing telah lengkap.');
        }

        return true;
    }

    private function hasFinalDocumentsAndScore(PengajuanPkl $pengajuan): bool
    {
        $requiredFinalDocuments = [
            'formulir_pendaftaran_ujian',
            'berita_acara_ujian',
            'formulir_nilai',
            'permohonan_revisi',
            'laporan_final',
            'lampiran_teknis',
            'acc_pembimbing_akhir',
            'rekap_nilai',
        ];
        $documents = $pengajuan->berkasSeminar()
            ->whereIn('jenis_berkas', $requiredFinalDocuments)
            ->pluck('path_berkas', 'jenis_berkas');

        $pembimbingComponents = $this->componentsForSubmission($pengajuan)
            ->where('penilai', 'Pembimbing')
            ->pluck('id');
        $hasAllSupervisorScores = $pembimbingComponents->isNotEmpty()
            && $pengajuan->detailNilai()
            ->where('dosen_id', $pengajuan->dosen_pembimbing_id)
            ->whereIn('komponen_penilaian_pkl_id', $pembimbingComponents)
            ->distinct('komponen_penilaian_pkl_id')
            ->count('komponen_penilaian_pkl_id') === $pembimbingComponents->count();

        $statuses = $pengajuan->berkasSeminar()
            ->whereIn('jenis_berkas', $requiredFinalDocuments)
            ->pluck('status_verifikasi', 'jenis_berkas');

        return collect($requiredFinalDocuments)->every(fn($jenis) => filled($documents->get($jenis)))
            && collect($requiredFinalDocuments)->every(fn($jenis) => $statuses->get($jenis) === 'Valid')
            && $hasAllSupervisorScores
            && $pengajuan->fresh()->nilai_akhir !== null;
    }

    private function successResponse(Request $request, string $message, mixed $data, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'data' => $data], $status);
        }

        return back()->with('success', $message);
    }

    private function statuses(): array
    {
        return [
            self::STATUS_PENGAJUAN,
            self::STATUS_PELAKSANAAN,
            self::STATUS_VERIFIKASI,
            self::STATUS_UJIAN,
            self::STATUS_SEMINAR,
            self::STATUS_REVISI,
            self::STATUS_SELESAI,
        ];
    }

    private function letterGrade(float $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'C+',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }
}
