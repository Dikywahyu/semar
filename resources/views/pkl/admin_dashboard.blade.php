@extends('layouts.app')

@section('content')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
@endpush

<div class="container-fluid py-4" style="background: linear-gradient(135deg, #f6f8fd 0%, #f1f5f9 100%); min-height: 100vh;">
    
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1">Dashboard & Monitoring PKL</h3>
            <p class="text-secondary mb-0">Kelola pengajuan, logbook bimbingan, verifikasi berkas, dan jadwal seminar mahasiswa.</p>
        </div>
        <div>
            <a href="{{ route('pkl.admin.komponen.index') }}" class="btn btn-outline-primary fw-semibold me-2">
                <i class="ti ti-adjustments me-1"></i> Master Komponen Nilai
            </a>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPkl">
                <i class="ti ti-plus me-2"></i> Pengajuan PKL Baru
            </button>
        </div>
    </div>

    <!-- 1. WIDGET RINGKASAN (STATISTIK KARTU - 7 TAHAPAN STATUS) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-secondary-subtle p-3 text-secondary me-3">
                        <i class="ti ti-file-description fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Pengajuan & Verifikasi</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-pengajuan">{{ $stats['pengajuan'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-primary-subtle p-3 text-primary me-3">
                        <i class="ti ti-briefcase fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Pelaksanaan & Bimbingan</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-pelaksanaan">{{ $stats['pelaksanaan'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-warning-subtle p-3 text-warning me-3">
                        <i class="ti ti-file-alert fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Verifikasi Berkas Admin</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-verifikasi">{{ $stats['verifikasi'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-info-subtle p-3 text-info me-3">
                        <i class="ti ti-calendar-event fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Dijadwalkan Ujian</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-ujian">{{ $stats['ujian'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-3 me-3" style="background-color: #f3e8ff; color: #7e22ce;">
                        <i class="ti ti-award fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Ujian Seminar & Penilaian</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-seminar">{{ $stats['seminar'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-danger-subtle p-3 text-danger me-3">
                        <i class="ti ti-edit-circle fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Revisi Laporan</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-revisi">{{ $stats['revisi'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-success-subtle p-3 text-success me-3">
                        <i class="ti ti-circle-check fs-2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small fw-semibold">Selesai</h6>
                        <h3 class="fw-bold mb-0 text-slate-800" id="stat-selesai">{{ $stats['selesai'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. SECTION GRAFIK (CHART.JS) & FILTER PENCARIAN -->
    <div class="row g-4 mb-4">
        <!-- Grafik Status PKL -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-slate-800 mb-0">Statistik Status Tahapan PKL</h5>
                    <span class="badge bg-light text-secondary">Real-time</span>
                </div>
                <div style="height: 320px; position: relative;">
                    <canvas id="pklStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filter Data Cepat -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-filter me-2"></i>Filter Data PKL</h5>
                <form id="filterForm" action="{{ route('pkl.admin.index') }}" method="GET">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Periode Semester</label>
                        <select name="id_semester" class="form-select select2">
                            <option value="">-- Semua Semester --</option>
                            @isset($semesters)
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem->kode_semester }}" {{ request('id_semester') == $sem->kode_semester ? 'selected' : '' }}>
                                        {{ $sem->nama_semester }} {{ $sem->aktif ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Tahapan</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="Pengajuan & Verifikasi Admin" {{ request('status') == 'Pengajuan & Verifikasi Admin' ? 'selected' : '' }}>Pengajuan & Verifikasi Admin</option>
                            <option value="Pelaksanaan PKL & Bimbingan" {{ request('status') == 'Pelaksanaan PKL & Bimbingan' ? 'selected' : '' }}>Pelaksanaan PKL & Bimbingan</option>
                            <option value="Verifikasi Berkas Admin" {{ request('status') == 'Verifikasi Berkas Admin' ? 'selected' : '' }}>Verifikasi Berkas Admin</option>
                            <option value="Dijadwalkan Ujian" {{ request('status') == 'Dijadwalkan Ujian' ? 'selected' : '' }}>Dijadwalkan Ujian</option>
                            <option value="Ujian Seminar & Penilaian" {{ request('status') == 'Ujian Seminar & Penilaian' ? 'selected' : '' }}>Ujian Seminar & Penilaian</option>
                            <option value="Revisi Laporan" {{ request('status') == 'Revisi Laporan' ? 'selected' : '' }}>Revisi Laporan</option>
                            <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cari Instansi / Mahasiswa</label>
                        <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Nama instansi / NIM...">
                    </div>
                    <div class="d-grid gap-2 mt-4"> 
                        <a href="{{ route('pkl.admin.index') }}" class="btn btn-light border text-secondary">Reset Filter</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. TABEL DATA UTAMA PKL -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-4" id="tableContainer">
            <h5 class="fw-bold text-slate-800 mb-3">Daftar Pengajuan & Progress PKL Mahasiswa</h5>
            @include('pkl.partials.admin_table')
        </div>
    </div>

</div>

<div class="modal fade" id="modalTambahPkl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Buat Pengajuan PKL untuk Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ url('pkl/admin/pengajuan') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Mahasiswa</label>
                        <select name="mahasiswa_registrasi_id" class="form-select select2-mahasiswa" required>
                            <option value="">-- Pilih Mahasiswa --</option>
                            @foreach($mahasiswas as $mahasiswa)
                                <option value="{{ $mahasiswa->id_registrasi_mahasiswa }}">{{ $mahasiswa->nama_mahasiswa }} ({{ $mahasiswa->nim }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Instansi</label><input name="nama_instansi" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Unit Kerja</label><input name="unit_kerja" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Alamat Instansi</label><textarea name="alamat_instansi" class="form-control" required></textarea></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CEK & VERIFIKASI BERKAS SEMINAR -->
<div class="modal fade" id="modalVerifikasiBerkas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 bg-light">
                <h5 class="modal-title fw-bold text-slate-800">Verifikasi Berkas Seminar Mahasiswa: <span id="mhs-nama-text" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formVerifikasiBerkas" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Silakan periksa kelengkapan dan keabsahan dokumen persyaratan seminar mahasiswa di bawah ini:</p>
                    
                    <div class="list-group mb-4">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="ti ti-file-text me-2 text-secondary"></i>1. Logbook Kegiatan Selama PKL</span>
                            <div>
                                <a href="#" id="link-logbook" target="_blank" class="btn btn-sm btn-outline-primary d-none"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a>
                                <span id="empty-logbook" class="badge bg-secondary-subtle text-secondary small">Belum diunggah</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="ti ti-file-text me-2 text-secondary"></i>2. Kartu Bimbingan (Min. 6 Kali)</span>
                            <div>
                                <a href="#" id="link-bimbingan" target="_blank" class="btn btn-sm btn-outline-primary d-none"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a>
                                <span id="empty-bimbingan" class="badge bg-secondary-subtle text-secondary small">Belum diunggah</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="ti ti-file-text me-2 text-secondary"></i>3. Draft Laporan PKL</span>
                            <div>
                                <a href="#" id="link-laporan" target="_blank" class="btn btn-sm btn-outline-primary d-none"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a>
                                <span id="empty-laporan" class="badge bg-secondary-subtle text-secondary small">Belum diunggah</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="ti ti-file-text me-2 text-secondary"></i>4. Lembar Penilaian Dosen Pamong</span>
                            <div>
                                <a href="#" id="link-pamong" target="_blank" class="btn btn-sm btn-outline-primary d-none"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a>
                                <span id="empty-pamong" class="badge bg-secondary-subtle text-secondary small">Belum diunggah</span>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="ti ti-file-text me-2 text-secondary"></i>5. Surat Persetujuan Semhas Pamong</span>
                            <div>
                                <a href="#" id="link-persetujuan" target="_blank" class="btn btn-sm btn-outline-primary d-none"><i class="ti ti-eye me-1"></i> Lihat Dokumen</a>
                                <span id="empty-persetujuan" class="badge bg-secondary-subtle text-secondary small">Belum diunggah</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keputusan Verifikasi <span class="text-danger">*</span></label>
                        <select name="keputusan" id="selectKeputusan" class="form-select" required>
                            <option value="disetujui">Setujui Berkas (Lanjut ke Penjadwalan Ujian)</option>
                            <option value="ditolak">Tolak Berkas / Minta Perbaikan</option>
                        </select>
                    </div>

                    <div class="mb-3" id="wrapperCatatan" style="display: none;">
                        <label class="form-label fw-semibold">Alasan Penolakan / Catatan Perbaikan <span class="text-danger">*</span></label>
                        <textarea name="catatan_admin" id="inputCatatan" class="form-control" rows="3" placeholder="Tuliskan bagian dokumen mana yang harus direvisi oleh mahasiswa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-light border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Partial Modals -->
@include('pkl.partials.modal_kelola')
@include('pkl.partials.modal_jadwal')
@include('pkl.partials.modal_penilaian_dinamis')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let pklStatusChart = null;

    document.addEventListener("DOMContentLoaded", function() {
        const chartElement = document.getElementById('pklStatusChart');
        if (chartElement) {
            const ctx = chartElement.getContext('2d');
            pklStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Pengajuan & Verifikasi Admin',
                        'Pelaksanaan PKL & Bimbingan', 
                        'Verifikasi Berkas Admin', 
                        'Dijadwalkan Ujian', 
                        'Ujian Seminar & Penilaian',
                        'Revisi Laporan',
                        'Selesai'
                    ],
                    datasets: [{
                        data: [
                            {{ $stats['pengajuan'] ?? 0 }}, 
                            {{ $stats['pelaksanaan'] ?? 0 }}, 
                            {{ $stats['verifikasi'] ?? 0 }}, 
                            {{ $stats['ujian'] ?? 0 }}, 
                            {{ $stats['seminar'] ?? 0 }}, 
                            {{ $stats['revisi'] ?? 0 }}, 
                            {{ $stats['selesai'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#64748b', '#2563eb', '#f59e0b', '#0891b2', '#7e22ce', '#dc2626', '#16a34a'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { font: { family: 'Inter', size: 11 }, usePointStyle: true, padding: 12 }
                        }
                    }
                }
            });
        }
    });

    $(document).ready(function() {
        $('.select2-mahasiswa').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalTambahPkl')
        });

        // Toggle Tampilan Catatan ketika Opsi Keputusan 'ditolak' dipilih
        $('#selectKeputusan').on('change', function() {
            if ($(this).val() === 'ditolak') {
                $('#wrapperCatatan').slideDown();
                $('#inputCatatan').prop('required', true);
            } else {
                $('#wrapperCatatan').slideUp();
                $('#inputCatatan').prop('required', false);
            }
        });

        // 1. EVENT KLIK TOMBOL CEK BERKAS (MODAL SHOW)
        $(document).on('click', 'button.btn-verifikasi-berkas', function(e) {
            e.preventDefault(); // Mencegah pemicu aksi bawaan tombol
            
            let id = $(this).data('id');
            let nama = $(this).data('nama');
            
            $('#mhs-nama-text').text(nama);
            $('#formVerifikasiBerkas').attr('action', "{{ url('pkl/admin/verifikasi-berkas-seminar') }}/" + id);
        
            function setFileLink(fileUrl, linkId, emptyId) {
                if (fileUrl && fileUrl !== '') {
                    $(linkId).attr('href', fileUrl).removeClass('d-none');
                    $(emptyId).addClass('d-none');
                } else {
                    $(linkId).addClass('d-none');
                    $(emptyId).removeClass('d-none');
                }
            }
        
            setFileLink($(this).data('logbook'), '#link-logbook', '#empty-logbook');
            setFileLink($(this).data('bimbingan'), '#link-bimbingan', '#empty-bimbingan');
            setFileLink($(this).data('laporan'), '#link-laporan', '#empty-laporan');
            setFileLink($(this).data('pamong'), '#link-pamong', '#empty-pamong');
            setFileLink($(this).data('persetujuan'), '#link-persetujuan', '#empty-persetujuan');
        
            $('#selectKeputusan').val('disetujui').trigger('change');
            $('#inputCatatan').val('');
            
            $('#modalVerifikasiBerkas').modal('show');
        });

        // 2. SUBMIT FORM VERIFIKASI BERKAS VIA AJAX (MENCEGAH REDIRECT / PINDAH HALAMAN)
        $(document).on('submit', '#formVerifikasiBerkas', function(e) {
            e.preventDefault(); // PENTING: Menghentikan form reload/redirect halaman!

            let form = $(this);
            let actionUrl = form.attr('action');
            let btnSubmit = form.find('button[type="submit"]');

            btnSubmit.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#modalVerifikasiBerkas').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Keputusan verifikasi berkas berhasil disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Muat ulang tabel dan widget via AJAX tanpa reload halaman
                    fetchTableData();
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan saat menyimpan verifikasi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: errorMsg
                    });
                },
                complete: function() {
                    btnSubmit.prop('disabled', false).text('Simpan Keputusan');
                }
            });
        });

        // 3. FUNGSI FETCH DATA TABEL & STATISTIK REAL-TIME
        let isFetching = false;

        // 1. FUNGSI AJAX FETCH TABLE & STATS DATA
        function fetchTableData() {
            if (isFetching) return; // Hentikan jika request sebelumnya masih berjalan
            isFetching = true;
    
            let status = $('select[name="status"]').val();
            let idSemester = $('select[name="id_semester"]').val();
            let keyword = $('input[name="keyword"]').val();
    
            $.ajax({
                url: "{{ route('pkl.admin.index') }}",
                type: 'GET',
                data: { 
                    status: status, 
                    id_semester: idSemester, 
                    keyword: keyword 
                },
                dataType: 'json',
                success: function(response) {
                    // Update Partial Tabel
                    if (response.html) {
                        $('#tableContainer .table-responsive, #tableContainer .mt-4').remove();
                        $('#tableContainer').append(response.html);
                    }
    
                    // Update Widget Kartu & Chart.js
                    if (response.stats) {
                        let s = response.stats;
                        $('#stat-pengajuan').text(s.pengajuan ?? 0);
                        $('#stat-pelaksanaan').text(s.pelaksanaan ?? 0);
                        $('#stat-verifikasi').text(s.verifikasi ?? 0);
                        $('#stat-ujian').text(s.ujian ?? 0);
                        $('#stat-seminar').text(s.seminar ?? 0);
                        $('#stat-revisi').text(s.revisi ?? 0);
                        $('#stat-selesai').text(s.selesai ?? 0);
    
                        if (pklStatusChart) {
                            pklStatusChart.data.datasets[0].data = [
                                s.pengajuan ?? 0,
                                s.pelaksanaan ?? 0,
                                s.verifikasi ?? 0,
                                s.ujian ?? 0,
                                s.seminar ?? 0,
                                s.revisi ?? 0,
                                s.selesai ?? 0
                            ];
                            pklStatusChart.update();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Fetch Error:", error);
                },
                complete: function() {
                    isFetching = false; // Buka kembali lock request
                }
            });
        }
    
        // 2. TRIGGER EVENT FILTER
        // Gunakan event 'change' langsung pada elemen form filter (bukan terikat dokumen global)
        $('#filterForm select[name="status"], #filterForm select[name="id_semester"]').off('change').on('change', function() {
            fetchTableData();
        });
    
        
    
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            fetchTableData();
        });

        // Live Filter Trigger
        $(document).on('change', 'select[name="status"], select[name="id_semester"]', function() {
            fetchTableData();
        });

        let searchTimer;
        $(document).on('keyup', 'input[name="keyword"]', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(fetchTableData, 500);
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            fetchTableData();
        });
    });
</script>
@endpush
@endsection