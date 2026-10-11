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

    public function mahasiswaIndex(): View
    {
        $mahasiswa = $this->currentMahasiswa();
        $pengajuan = $mahasiswa->pengajuanPkl()->with([
            'dosenPembimbing',
            'dosenPenguji',
            'detailNilai.komponen',
            'bimbingan',
            'logAktivitas',
        ])->latest()->first();

        return view('pkl.mahasiswa_dashboard', compact('pengajuan', 'mahasiswa'));
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

        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'alamat_instansi' => ['required', 'string', 'max:5000'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'berkas_proposal' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $validated['mahasiswa_registrasi_id'] = $mahasiswa->id_registrasi_mahasiswa;
        $semester = Semester::query()->where('aktif', true)->first();
        abort_unless($semester, 409, 'Semester aktif belum diatur. Hubungi administrator sebelum mengajukan PKL.');
        $validated['semester_master_id'] = $semester->id;
        $validated['id_semester'] = $semester->kode_semester;
        $validated['berkas_proposal'] = $this->storeFile($request->file('berkas_proposal'));
        $validated['status'] = self::STATUS_PENGAJUAN;
        $pengajuan = PengajuanPkl::create($validated);

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

        return $this->successResponse($request, 'Berkas revisi berhasil diunggah.', $pengajuan);
    }

    public function uploadBerkasSatuan(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->assertOwnSubmission($pengajuan);
        abort_unless(in_array($pengajuan->status, [self::STATUS_PELAKSANAAN, self::STATUS_VERIFIKASI], true), 409, 'Berkas seminar belum dapat diunggah pada tahap ini.');

        $validated = $request->validate([
            'field' => ['required', Rule::in([
                'berkas_logbook',
                'berkas_kartu_bimbingan',
                'berkas_draft_laporan',
                'berkas_nilai_pamong',
                'berkas_persetujuan_pamong',
            ])],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $pengajuan->update([
            $validated['field'] => $this->storeFile($request->file('file')),
        ]);

        return response()->json([
            'message' => 'Berkas berhasil diunggah.',
            'file_url' => url('pkl/view-file/' . $pengajuan->{$validated['field']}),
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

        return response()->json(['message' => 'Berkas revisi berhasil diajukan ulang.']);
    }

    public function selesaiPelaksanaan(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->assertOwnSubmission($pengajuan);
        abort_unless($pengajuan->status === self::STATUS_PELAKSANAAN, 409, 'Pengajuan tidak sedang dalam tahap pelaksanaan.');

        $pengajuan->update(['status' => self::STATUS_VERIFIKASI]);

        return response()->json(['message' => 'Pelaksanaan PKL ditandai selesai. Silakan lengkapi berkas seminar.']);
    }

    public function adminIndex(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();
        $submissions = $this->filteredSubmissions($request)->paginate(15);
        $stats = $this->statusStats();

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
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $pengajuan->update($validated);
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

    public function verifyDocuments(Request $request, PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_admin' => ['required_if:keputusan,ditolak', 'nullable', 'string', 'max:5000'],
        ]);

        if ($validated['keputusan'] === 'disetujui') {
            $requiredFiles = [
                'berkas_logbook',
                'berkas_kartu_bimbingan',
                'berkas_draft_laporan',
                'berkas_nilai_pamong',
                'berkas_persetujuan_pamong',
            ];
            abort_if(collect($requiredFiles)->contains(fn ($field) => !$pengajuan->{$field}), 422, 'Semua berkas seminar harus diunggah sebelum disetujui.');
            $pengajuan->update(['catatan_admin' => null]);
        } else {
            $pengajuan->update([
                'status' => self::STATUS_REVISI,
                'catatan_admin' => $validated['catatan_admin'],
            ]);
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

        DB::transaction(function () use ($pengajuan, $validated) {
            $pengajuan->dosenPenguji()->sync(collect($validated['penguji'])->mapWithKeys(
                fn ($penguji) => [$penguji['dosen_id'] => ['peran' => $penguji['peran']]]
            )->all());
            $pengajuan->update([
                'tanggal_seminar' => $validated['tanggal_seminar'],
                'ruangan_seminar' => $validated['ruangan_seminar'],
                'status' => self::STATUS_UJIAN,
            ]);
        });

        return $this->successResponse($request, 'Jadwal seminar dan dosen penguji berhasil disimpan.', $pengajuan->load('dosenPenguji'));
    }

    public function adminGrades(PengajuanPkl $pengajuan): JsonResponse
    {
        $this->authorizeAdmin();
        $pengajuan->load(['dosenPembimbing', 'dosenPenguji']);
        $komponens = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn ($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->orderBy('id')
            ->get();
        $existingNilai = $pengajuan->detailNilai()
            ->get()
            ->groupBy('dosen_id')
            ->map(fn ($rows) => $rows->keyBy('komponen_penilaian_pkl_id'));

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
            ->values();
        $expectedComponentIds = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn ($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        abort_if(empty($expectedComponentIds), 422, 'Belum ada komponen penilaian untuk semester ini.');
        foreach ($allowedDosenIds as $dosenId) {
            $submittedIds = array_map('strval', array_keys($validated['nilai'][$dosenId] ?? []));
            abort_if(array_diff($expectedComponentIds, $submittedIds), 422, 'Nilai seluruh komponen untuk setiap dosen penilai wajib diisi.');
        }
        $komponens = KomponenPenilaianPkl::query()
            ->whereIn('id', collect($validated['nilai'])->flatMap(fn ($scores) => array_keys($scores))->unique())
            ->when($pengajuan->id_semester, fn ($query) => $query->where('id_semester', $pengajuan->id_semester))
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($validated, $pengajuan, $allowedDosenIds, $komponens) {
            $weightedValues = [];
            foreach ($validated['nilai'] as $dosenId => $scores) {
                abort_unless($allowedDosenIds->contains($dosenId), 422, 'Dosen penilai tidak sesuai dengan pengajuan ini.');
                foreach ($scores as $komponenId => $score) {
                    $komponen = $komponens->get($komponenId);
                    abort_unless($komponen, 422, 'Komponen penilaian tidak valid.');
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
                    fn ($item) => (array_sum($item['scores']) / count($item['scores'])) * $item['bobot']
                ) / $bobotTotal;
                $pengajuan->update([
                    'nilai_akhir' => round($nilaiAkhir, 2),
                    'nilai_huruf' => $this->letterGrade($nilaiAkhir),
                ]);
            }
        });

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
            ->when($semester, fn ($query) => $query->where(function ($query) use ($semester, $selectedSemester) {
                $query->where('semester_master_id', $semester->id)
                    ->orWhere(fn ($query) => $query->whereNull('semester_master_id')->where('id_semester', $selectedSemester));
            }), fn ($query) => $query->where('id_semester', $selectedSemester))
            ->orderBy('id')
            ->get();

        return view('pkl.admin_komponen_penilaian', [
            'komponens' => $komponens,
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
            'nama_komponen' => ['required', 'string', 'max:255'],
            'bobot' => ['required', 'numeric', 'between:0,100'],
        ]);
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $component = KomponenPenilaianPkl::create($validated + [
            'penilai' => 'Penguji',
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
            'nama_komponen' => ['required', 'string', 'max:255'],
            'bobot' => ['required', 'numeric', 'between:0,100'],
        ]);
        $semester = Semester::findOrFail($validated['semester_master_id']);
        $validated['id_semester'] = $semester->kode_semester;
        $komponen->update($validated + [
            'penilai' => 'Penguji',
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
        $dosen = $this->currentDosen();
        $query = PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPenguji', 'dosenPembimbing'])
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_pembimbing_id', $dosen->id_dosen)
                    ->orWhereHas('dosenPenguji', fn ($query) => $query->where('dosens.id_dosen', $dosen->id_dosen));
            })
            ->when($request->filled('id_semester'), fn ($query) => $query->where('id_semester', $request->input('id_semester')));
        $assignments = $query->latest()->get();
        $bimbinganList = $assignments->where('dosen_pembimbing_id', $dosen->id_dosen)->values();
        $pengujiList = $assignments->filter(fn ($item) => $item->dosenPenguji->contains('id_dosen', $dosen->id_dosen))->values();
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
        $pengajuan->load(['mahasiswaRegistrasi', 'logbooks']);

        return view('pkl.dosen_bimbingan_detail', compact('pengajuan'));
    }

    public function dosenComponents(PengajuanPkl $pengajuan): JsonResponse
    {
        $dosen = $this->currentDosen();
        abort_unless($this->isAssignedDosen($pengajuan, $dosen), 403);
        $komponen = KomponenPenilaianPkl::query()
            ->when($pengajuan->id_semester, fn ($query) => $query->where('id_semester', $pengajuan->id_semester))
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
            ->when($pengajuan->id_semester, fn ($query) => $query->where('id_semester', $pengajuan->id_semester))
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
                    ->orWhereHas('pengajuanDiuji', fn ($query) => $query->whereKey($pengajuan->id));
            })
            ->exists();
        abort_unless($isAdmin || $student || $dosen, 403);

        return Storage::disk('local')->download($normalizedPath);
    }

    private function filteredSubmissions(Request $request)
    {
        return PengajuanPkl::query()
            ->with(['mahasiswaRegistrasi', 'dosenPembimbing', 'dosenPenguji'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('id_semester'), fn ($query) => $query->where('id_semester', $request->input('id_semester')))
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

    private function currentMahasiswa(): MahasiswaRegistrasi
    {
        return $this->mahasiswaProfilesForUser()->firstOrFail();
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
            ->first();
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
