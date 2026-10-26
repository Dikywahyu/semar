<?php

namespace App\Http\Controllers;

use App\Models\Dosen\Dosen;
use App\Models\Mahasiswa\MahasiswaRegistrasi;
use App\Models\Pkl\DetailNilaiPkl;
use App\Models\Pkl\KomponenPenilaianPkl;
use App\Models\Pkl\LogAktivitasPkl;
use App\Models\Pkl\PengajuanPkl;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function mahasiswaIndex(Request $request): View
    {
        $user = auth()->user();
        $isPrivileged = $user?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin'])
            || $user?->can('academic-edit');
        $canSelectStudentProfile = $isPrivileged || $user?->hasAnyRole(['mahasiswa', 'Mahasiswa']);
        $mahasiswa = $canSelectStudentProfile && $request->filled('id_registrasi_mahasiswa')
            ? MahasiswaRegistrasi::query()->where('aktif', true)->find($request->input('id_registrasi_mahasiswa'))
            : $this->mahasiswaProfilesForUser()->first();

        if (! $mahasiswa && $canSelectStudentProfile) {
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

        $pengajuan = $mahasiswa->pengajuanPkl()->with([
            'dosenPembimbing',
            'dosenPenguji',
            'detailNilai.komponen',
            'bimbingan.tanggapan.pengirim',
            'logAktivitas.aktor',
            'berkasSeminar',
        ])->latest()->first();

        return view('pkl.mahasiswa_dashboard', compact('pengajuan', 'mahasiswa'));
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
            'komponen_penilaian_pkl' => KomponenPenilaianPkl::query()->get(),
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
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $mahasiswa = $this->mahasiswaProfilesForUser()->where('id_registrasi_mahasiswa', $pengajuan->mahasiswa_registrasi_id)->exists();
        abort_unless($mahasiswa, 403);
        $allowedStageStatus = match ($tahap) {
            1 => [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN],
            2 => [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI],
            3 => [self::STATUS_UJIAN, self::STATUS_SEMINAR, self::STATUS_REVISI],
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
        abort_unless(in_array($jenis, $fields, true) && $request->hasFile('file') && $request->file('file')->isValid(), 422, 'Pilih berkas yang valid.');

        if ($field === 'berkas_proposal') {
            $pengajuan->update(['berkas_proposal' => $this->storeFile($request->file('file'))]);
            $this->logPklActivity($pengajuan, 'Unggah Dokumen', (auth()->user()?->name ?? 'Mahasiswa') . ' mengunggah dokumen: Proposal PKL.');
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
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_PENGAJUAN, 409, 'Pembimbing hanya dapat diusulkan sebelum pelaksanaan PKL dimulai.');
        $validated = $request->validate([
            'dosen_pembimbing_id' => ['required', 'exists:dosens,id_dosen'],
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

    private function advanceAfterExamDocuments(PengajuanPkl $pengajuan): void
    {
        $required = ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'];
        if ($pengajuan->status !== self::STATUS_VERIFIKASI || $pengajuan->berkasSeminar()->whereIn('jenis_berkas', $required)->count() !== count($required)) {
            return;
        }

        $pengajuan->update(['status' => self::STATUS_UJIAN]);
        $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Status berubah dari ' . self::STATUS_VERIFIKASI . ' menjadi ' . self::STATUS_UJIAN . ' setelah mahasiswa melengkapi draft laporan, kartu konsultasi, dan transkrip sementara.');
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
        ]);
        $berkas = $pengajuan->berkasSeminar()->find($validated['berkas_id']);
        $this->logPklActivity($pengajuan, 'Verifikasi Dokumen', 'Admin ' . auth()->user()->name . ' memverifikasi dokumen ' . ($berkas?->jenis_berkas ?? '-') . ' dengan status ' . $validated['status'] . (($validated['catatan'] ?? '') !== '' ? '. Catatan: ' . $validated['catatan'] : '.'));
        $this->completePklIfReady($pengajuan);

        return back()->with('success', 'Status berkas diperbarui.');
    }

    public function routeMapAcc(PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['dosen_pembimbing', 'Dosen']), 403);
        $dosen = $this->currentDosen();
        abort_unless($pengajuan->dosen_pembimbing_id === $dosen->id_dosen, 403);
        $wasAcc = (bool) $pengajuan->is_acc_seminar;
        $pengajuan->update(['is_acc_seminar' => true]);
        if (! $wasAcc) {
            $this->logPklActivity($pengajuan, 'ACC Seminar', 'Dosen pembimbing ' . (auth()->user()->name ?? '') . ' menyetujui pendaftaran ujian seminar.');
        }

        return back()->with('success', 'ACC seminar berhasil diberikan.');
    }

    public function routeMapProposeSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $this->assertOwnSubmission($pengajuan);
        abort_unless(in_array($pengajuan->status, [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI, self::STATUS_UJIAN], true), 409, 'Usulan jadwal belum tersedia pada tahap ini.');
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date', 'after:now'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
            'dosen_pembimbing_id' => ['nullable', 'exists:dosens,id_dosen'],
        ]);
        $dosenUsulan = ! empty($validated['dosen_pembimbing_id']) ? Dosen::find($validated['dosen_pembimbing_id']) : null;
        $changes = 'Usulan tanggal ujian: ' . $validated['tanggal_seminar'] . '; usulan ruangan: ' . $validated['ruangan_seminar'];
        if ($dosenUsulan) {
            $changes .= '; usulan dosen pembimbing: ' . $dosenUsulan->nama_dosen;
        }
        $this->logPklActivity($pengajuan, 'Usulan Mahasiswa', $changes . '. Penetapan resmi menunggu admin.');

        return back()->with('success', 'Usulan jadwal seminar tersimpan dan menunggu penetapan admin.');
    }

    public function mahasiswaProposeSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_UJIAN, 409, 'Tanggal ujian, ruangan, dan dosen penguji dapat dipilih setelah berkas praujian lengkap.');
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date', 'after:now'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
            'penguji_usulan' => ['nullable', 'array', 'max:5'],
            'penguji_usulan.*' => ['required', 'distinct', 'exists:dosens,id_dosen'],
        ]);
        abort_if(empty($validated['penguji_usulan']), 422, 'Pilih setidaknya satu dosen penguji.');
        $namaPenguji = collect($validated['penguji_usulan'])
            ->map(fn($id) => Dosen::find($id)?->nama_dosen)
            ->filter()
            ->values();
        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        $pengajuan->update([
            'tanggal_seminar' => $validated['tanggal_seminar'],
            'ruangan_seminar' => $validated['ruangan_seminar'],
        ]);
        $pengajuan->dosenPenguji()->sync(collect($validated['penguji_usulan'])->values()->mapWithKeys(
            fn($id, $index) => [$id => ['peran' => $index === 0 ? 'Ketua Penguji' : 'Anggota Penguji']]
        )->all());
        $this->logPklActivity($pengajuan, 'Penjadwalan Ujian oleh Mahasiswa', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' menetapkan jadwal ' . $validated['tanggal_seminar'] . ' di ' . $validated['ruangan_seminar'] . ' dan mengusulkan penguji: ' . $namaPenguji->implode(', ') . '. Jadwal sebelumnya: ' . ($tanggalSebelumnya ?: '-') . ' / ' . ($ruanganSebelumnya ?: '-') . '. Admin dapat mengubah jadwal dan daftar penguji.');

        return back()->with('success', 'Tanggal, ruangan, dan dosen penguji tersimpan. Admin dapat meninjau dan mengubah penetapan.');
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
        abort_unless(auth()->user()?->hasAnyRole(['mahasiswa', 'Mahasiswa']), 403);
        $this->assertOwnSubmission($pengajuan);
        $validated = $request->validate([
            'jenis_berkas' => ['required', Rule::in(array_keys($this->routeMapDocumentCatalog()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $stage = $this->routeMapDocumentCatalog()[$validated['jenis_berkas']]['stage'];
        $allowed = match ($stage) {
            1 => in_array($pengajuan->status, [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN], true),
            2 => in_array($pengajuan->status, [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI], true),
            3 => in_array($pengajuan->status, [self::STATUS_UJIAN, self::STATUS_SEMINAR, self::STATUS_REVISI], true),
            default => false,
        };
        abort_unless($allowed, 409, 'Unggahan dokumen tidak tersedia pada status PKL saat ini.');

        $kind = $validated['jenis_berkas'];
        if ($kind === 'proposal') {
            $pengajuan->update(['berkas_proposal' => $this->storeFile($request->file('file'))]);
            $this->logPklActivity($pengajuan, 'Unggah Dokumen', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengunggah dokumen: Proposal PKL.');
        } else {
            $this->storePklDocument($pengajuan, $kind, $request->file('file'));
        }

        return back()->with('success', 'Dokumen berhasil diunggah dan menunggu verifikasi.');
    }

    public function routeMapSchedule(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']), 403);
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date'],
            'ruangan_seminar' => ['required', 'string', 'max:255'],
        ]);
        $pengajuan->update($validated);
        $this->logPklActivity($pengajuan, 'Jadwal Ujian Ditetapkan', 'Admin ' . (auth()->user()->name ?? '') . ' menetapkan jadwal ' . $validated['tanggal_seminar'] . ' di ' . $validated['ruangan_seminar'] . '.');

        return back()->with('success', 'Jadwal seminar ditetapkan.');
    }

    public function routeMapGrade(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['dosen_pembimbing', 'dosen_penguji', 'Dosen']), 403);
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
            $komponen = KomponenPenilaianPkl::query()
                ->whereKey($komponenId)
                ->where('penilai', $validated['kategori_penilai'])
                ->firstOrFail();
            DetailNilaiPkl::query()->updateOrCreate(
                ['pengajuan_pkl_id' => $pengajuan->id, 'dosen_id' => $dosen->id_dosen, 'komponen_penilaian_pkl_id' => $komponen->id],
                ['nilai_angka' => $nilai]
            );
        }
        if ($request->filled('catatan_evaluasi')) {
            $pengajuan->update(['catatan_admin' => $validated['catatan_evaluasi']]);
        }
        $this->logPklActivity($pengajuan, 'Penilaian PKL', 'Dosen ' . (auth()->user()->name ?? '') . ' menyimpan nilai kategori ' . $validated['kategori_penilai'] . ($request->filled('catatan_evaluasi') ? ' dan catatan evaluasi.' : '.'));
        $this->completePklIfReady($pengajuan);

        return back()->with('success', 'Penilaian berhasil disimpan.');
    }

    public function routeMapValidateRevision(PengajuanPkl $pengajuan): RedirectResponse
    {
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        abort_unless($pengajuan->berkas_laporan_revisi, 409, 'Laporan revisi belum diunggah.');
        $this->logPklActivity($pengajuan, 'Revisi Divalidasi', 'Dosen ' . (auth()->user()->name ?? '') . ' memvalidasi berkas revisi pasca-ujian.');
        abort_unless($this->completePklIfReady($pengajuan), 409, 'Kelulusan menunggu seluruh dokumen akhir, ACC pembimbing yang valid, dan nilai pembimbing.');

        return back()->with('success', 'Revisi divalidasi.');
    }

    public function routeMapComplete(PengajuanPkl $pengajuan): RedirectResponse
    {
        $user = auth()->user();
        $isStudentOwner = $this->mahasiswaProfilesForUser()->whereKey($pengajuan->mahasiswa_registrasi_id)->exists();
        $isAssignedPembimbing = $user?->hasAnyRole(['dosen_pembimbing', 'Dosen'])
            && $pengajuan->dosen_pembimbing_id === $this->dosenProfilesForUser()->value('id_dosen');
        abort_unless($isStudentOwner || $isAssignedPembimbing || $user?->hasAnyRole(['admin', 'Admin', 'Super Admin', 'Kaprodi']), 403);
        abort_unless(in_array($pengajuan->status, [self::STATUS_REVISI, self::STATUS_SELESAI], true), 409, 'Kelulusan akhir hanya dapat diproses setelah ujian dan revisi.');
        abort_unless($this->completePklIfReady($pengajuan), 409, 'Kelulusan menunggu seluruh dokumen akhir, ACC pembimbing yang valid, dan nilai pembimbing.');

        return back()->with('success', 'Status PKL mahasiswa diperbarui menjadi Lulus.');
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
        abort_unless(in_array($pengajuan->status, [self::STATUS_PENGAJUAN, self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI], true), 409, 'Berkas belum dapat diunggah pada tahap ini.');

        $validated = $request->validate([
            'field' => ['required', Rule::in([
                'berkas_proposal',
                'surat_jalan_pkl',
                'surat_balasan_instansi',
                'acc_pembimbing_awal',
                'berkas_kartu_bimbingan',
                'berkas_draft_laporan',
                'transkrip_sementara',
            ])],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $fileMaxBytes = in_array($validated['field'], ['berkas_proposal', 'surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal'], true)
            ? 2 * 1024 * 1024
            : 5 * 1024 * 1024;
        abort_if($request->file('file')->getSize() > $fileMaxBytes, 422, 'Ukuran file melebihi batas yang diizinkan.');
        abort_if($request->file('file')->getSize() > 2 * 1024 * 1024, 422, 'Ukuran file melebihi batas server lokal 2MB.');

        if (in_array($validated['field'], ['surat_jalan_pkl', 'surat_balasan_instansi', 'acc_pembimbing_awal', 'transkrip_sementara'], true)) {
            $berkas = $this->storePklDocument($pengajuan, $validated['field'], $request->file('file'));
        } else {
            if (in_array($validated['field'], ['berkas_proposal', 'berkas_draft_laporan', 'berkas_kartu_bimbingan'], true)) {
                $storedPath = $this->storeFile($request->file('file'));
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
        abort_unless($pengajuan->status === self::STATUS_REVISI, 409, 'Pengajuan ini tidak sedang menunggu revisi.');
        abort_unless($pengajuan->berkas_laporan_revisi && $pengajuan->berkas_lembar_pengesahan, 422, 'Unggah laporan revisi dan lembar pengesahan sebelum mengajukan ulang.');

        $pengajuan->update([
            'status' => self::STATUS_VERIFIKASI,
            'catatan_admin' => null,
        ]);
        $this->logPklActivity($pengajuan, 'Pengajuan Ulang Revisi', 'Mahasiswa ' . (auth()->user()->name ?? '') . ' mengajukan ulang berkas revisi untuk diverifikasi.');

        return response()->json(['message' => 'Berkas revisi berhasil diajukan ulang.']);
    }

    public function selesaiPelaksanaan(PengajuanPkl $pengajuan): JsonResponse
    {
        $user = auth()->user();
        $isMahasiswaPemilik = $this->mahasiswaProfilesForUser()->whereKey($pengajuan->mahasiswa_registrasi_id)->exists();
        $isDosenPembimbing = $user?->hasAnyRole(['dosen_pembimbing', 'Dosen'])
            && $pengajuan->dosen_pembimbing_id === $this->dosenProfilesForUser()->value('id_dosen');
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
        $submissions = $this->filteredSubmissions($request)->with(['logAktivitas.aktor'])->paginate(15);
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
            $pengajuan->setAttribute('usulan_tanggal_seminar', trim($tanggal[1] ?? ''));
            $pengajuan->setAttribute('usulan_ruangan_seminar', trim($ruangan[1] ?? ''));
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
            'dosen_pembimbing_id' => ['nullable', 'exists:dosens,id_dosen'],
            'semester_master_id' => ['required', 'exists:semesters,id'],
            'sk_tugas' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statuses())],
            'catatan_admin' => ['nullable', 'string', 'max:5000'],
        ]);
        $statusSebelumnya = $pengajuan->status;
        $pembimbingSebelumnya = $pengajuan->dosen_pembimbing_id;
        $tanggalSebelumnya = $pengajuan->tanggal_seminar?->format('Y-m-d H:i:s');
        $ruanganSebelumnya = $pengajuan->ruangan_seminar;
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $pengajuan->update($validated);
        if ($pembimbingSebelumnya && $pembimbingSebelumnya !== $pengajuan->dosen_pembimbing_id) {
            $pengajuan->dosenPenguji()->detach();
            $pengajuan->detailNilai()->delete();
            $pengajuan->update(['is_acc_seminar' => false]);
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
        $pengajuan->update([
            'dosen_pembimbing_id' => $validated['dosen_pembimbing_id'],
            'tanggal_seminar' => $validated['tanggal_seminar'],
            'ruangan_seminar' => $validated['ruangan_seminar'],
        ]);
        $dokumenUjianLengkap = $pengajuan->berkasSeminar()->whereIn('jenis_berkas', ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'])->count() === 3;
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

        return back()->with('success', 'Usulan mahasiswa diterapkan sebagai jadwal PKL.');
    }

    public function verifyDocuments(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_admin' => ['required_if:keputusan,ditolak', 'nullable', 'string', 'max:5000'],
        ]);

        if ($validated['keputusan'] === 'disetujui') {
            $requiredDocumentKinds = ['draft_laporan', 'kartu_konsultasi', 'transkrip_sementara'];
            abort_unless($pengajuan->berkasSeminar()->whereIn('jenis_berkas', $requiredDocumentKinds)->count() === count($requiredDocumentKinds), 422, 'Draft laporan, kartu konsultasi, dan transkrip sementara wajib diunggah sebelum disetujui.');
            $statusSebelumnya = $pengajuan->status;
            $pengajuan->update(['status' => self::STATUS_UJIAN, 'catatan_admin' => null]);
            $this->logPklActivity($pengajuan, 'Verifikasi Berkas Disetujui', 'Admin ' . (auth()->user()->name ?? '') . ' menyetujui tiga berkas persyaratan ujian.');
            if ($statusSebelumnya !== self::STATUS_UJIAN) {
                $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Admin ' . auth()->user()->name . ' mengubah status dari ' . $statusSebelumnya . ' menjadi ' . self::STATUS_UJIAN . '.');
            }
        } else {
            $statusSebelumnya = $pengajuan->status;
            $pengajuan->update([
                'status' => self::STATUS_REVISI,
                'catatan_admin' => $validated['catatan_admin'],
            ]);
            $this->logPklActivity($pengajuan, 'Berkas Perlu Revisi', 'Admin ' . (auth()->user()->name ?? '') . ' meminta perbaikan berkas: ' . $validated['catatan_admin']);
            if ($statusSebelumnya !== self::STATUS_REVISI) {
                $this->logPklActivity($pengajuan, 'Perubahan Status PKL', 'Admin ' . auth()->user()->name . ' mengubah status dari ' . $statusSebelumnya . ' menjadi ' . self::STATUS_REVISI . '.');
            }
        }

        return response()->json(['message' => 'Keputusan verifikasi berhasil disimpan.']);
    }

    public function setSchedule(Request $request, PengajuanPkl $pengajuan): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'tanggal_seminar' => ['required', 'date'],
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
            ]);
        });
        $this->logPklActivity($pengajuan, 'Jadwal Ujian Ditetapkan', 'Admin ' . auth()->user()->name . ' menetapkan jadwal ' . $validated['tanggal_seminar'] . ' di ' . $validated['ruangan_seminar'] . ' dengan ' . count($validated['penguji']) . ' dosen penguji.');

        return $this->successResponse($request, 'Jadwal seminar dan dosen penguji berhasil disimpan.', $pengajuan->load('dosenPenguji'));
    }

    public function adminGrades(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $pengajuan->load(['dosenPembimbing', 'dosenPenguji']);
        $komponens = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->orderBy('id')
            ->get();
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
        $components = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->get()
            ->keyBy('id');
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
        $this->completePklIfReady($pengajuan);

        return response()->json(['message' => 'Nilai seminar berhasil disimpan.']);
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
        $isPrivileged = auth()->user()?->hasAnyRole(['Super Admin', 'Kaprodi', 'Admin'])
            || auth()->user()?->can('academic-edit');
        $dosen = $isPrivileged && $request->filled('id_dosen')
            ? Dosen::query()->where('aktif', true)->find($request->input('id_dosen'))
            : $this->dosenProfilesForUser()->first();

        if (! $dosen && $isPrivileged) {
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
        $query = PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPenguji', 'dosenPembimbing'])
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_pembimbing_id', $dosen->id_dosen)
                    ->orWhereHas('dosenPenguji', fn($query) => $query->where('dosens.id_dosen', $dosen->id_dosen));
            })
            ->when($request->filled('id_semester'), fn($query) => $query->where('id_semester', $request->input('id_semester')));
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
        ]);
    }

    public function dosenDetail(PengajuanPkl $pengajuan): View
    {
        $dosen = $this->currentDosen();
        abort_unless($pengajuan->dosen_pembimbing_id === $dosen->id_dosen, 403);
        $pengajuan->load([
            'mahasiswaRegistrasi',
            'logAktivitas.aktor',
            'bimbingan.tanggapan.pengirim',
        ]);

        return view('pkl.dosen_bimbingan_detail', compact('pengajuan'));
    }

    public function dosenComponents(PengajuanPkl $pengajuan): JsonResponse
    {
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        $categories = collect();
        if ($pengajuan->dosen_pembimbing_id === $dosen->id_dosen) {
            $categories->push('Pembimbing');
        }
        if ($pengajuan->dosenPenguji()->where('dosens.id_dosen', $dosen->id_dosen)->exists()) {
            $categories->push('Penguji');
        }
        $komponen = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn($query) => $query->where('id_semester', $pengajuan->id_semester))
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
        $isAdmin = $user->hasAnyRole(['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'])
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
        return PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji'])
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('id_semester'), fn($query) => $query->where('id_semester', $request->input('id_semester')))
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
        $mahasiswa = $this->mahasiswaProfilesForUser()->first();
        abort_unless($mahasiswa, 403, 'Profil mahasiswa untuk akun ini belum tertaut. Pastikan email akun sama dengan email data mahasiswa atau identifier akun berisi NIM.');

        return $mahasiswa;
    }

    private function currentDosen(): Dosen
    {
        return $this->dosenProfilesForUser()->firstOrFail();
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
        abort_unless(
            $pengajuan->mahasiswa_registrasi_id === $this->currentMahasiswa()->id_registrasi_mahasiswa,
            403
        );
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([
            'Super Admin',
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
            ]
        );
        $catalog = $this->routeMapDocumentCatalog();
        $label = $catalog[$jenis]['label'] ?? $jenis;
        $this->logPklActivity($pengajuan, 'Unggah Dokumen', (auth()->user()?->name ?? 'Pengguna') . ' mengunggah atau mengganti dokumen: ' . $label . '.');

        return $berkas;
    }

    private function completePklIfReady(PengajuanPkl $pengajuan): bool
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
        $documents = $pengajuan->berkasSeminar()->whereIn('jenis_berkas', $requiredFinalDocuments)->get()->keyBy('jenis_berkas');
        if (collect($requiredFinalDocuments)->contains(fn($jenis) => ! $documents->get($jenis)?->path_berkas)) {
            return false;
        }
        if ($documents->get('acc_pembimbing_akhir')->status_verifikasi !== 'Valid') {
            return false;
        }

        $hasPembimbingScore = $pengajuan->detailNilai()
            ->whereHas('komponen', fn($query) => $query->where('penilai', 'Pembimbing'))
            ->exists();
        if (! $hasPembimbingScore) {
            return false;
        }

        $sudahSelesai = $pengajuan->status === self::STATUS_SELESAI;
        $statusSebelumnya = $pengajuan->status;
        $pengajuan->update(['status' => self::STATUS_SELESAI]);
        $pengajuan->mahasiswaRegistrasi?->update(['status_pkl' => 'Lulus']);
        if (! $sudahSelesai) {
            $this->logPklActivity($pengajuan, 'PKL Lulus', (auth()->user()?->name ?? 'Pengguna') . ' menyelesaikan proses PKL dari status ' . $statusSebelumnya . '. Syarat dokumen akhir, ACC pembimbing, dan penilaian pembimbing telah lengkap.');
        }

        return true;
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
