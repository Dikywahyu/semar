@extends('layouts.app')

@section('content')
<style>
    .pkl-page {
        --pkl-ink: #172033;
        --pkl-muted: #687386;
        --pkl-primary: #3758d6;
        color: var(--pkl-ink);
        background: #f5f7fb;
        min-height: 100vh;
    }

    .pkl-page .pkl-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(120deg, #172554 0%, #243b80 55%, #3758d6 100%) !important;
        border-radius: 22px !important;
        padding: clamp(1.35rem, 3vw, 2.25rem) !important;
        box-shadow: 0 14px 35px rgba(28, 47, 111, .15) !important;
    }

    .pkl-page .card {
        border: 1px solid rgba(226, 232, 240, .85) !important;
        border-radius: 18px !important;
        box-shadow: 0 5px 18px rgba(20, 33, 61, .045) !important;
    }

    .pkl-page .table> :not(caption)>*>* {
        padding: .9rem .75rem;
    }
</style>

<div class="container-fluid py-4 min-vh-100 pkl-page">
    <!-- Header Banner Dosen -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-4 bg-gradient text-white p-4 pkl-hero">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-1 text-white"><i class="ti ti-chalkboard text-info me-2"></i>Portal Dosen Pembimbing & Penguji PKL</h2>
                        <p class="text-white-50 mb-0">Pantau bimbingan mahasiswa bimbingan Anda serta jadwalkan/berikan penilaian ujian seminar PKL.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER PERIODE SEMESTER MODERN -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-lg-5 col-md-6">
                <label class="form-label small fw-bold text-uppercase text-secondary tracking-wider mb-2">
                    <i class="ti ti-filter text-primary me-1 fs-5 align-middle"></i> Filter Periode Semester Akademik
                </label>
                <div class="input-group">
                    <select name="id_semester" id="filterSemesterDosen" class="form-select border select2">
                        <option value="">-- Semua Semester Akademik --</option>
                        @isset($semesters)
                        @foreach($semesters as $sem)
                        @php
                        $semId = $sem->id_semester ?? $sem->kode_semester;
                        $isAktif = ($sem->a_periode_aktif ?? $sem->aktif ?? 0) == 1;
                        @endphp
                        <option value="{{ $semId }}" {{ request('id_semester') == $semId ? 'selected' : '' }}>
                            {{ $sem->nama_semester }} {{ $isAktif ? '✨ (Aktif)' : '' }}
                        </option>
                        @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 d-flex align-items-end">
                <div class="w-100">
                    <button type="button" id="btnResetFilter" class="btn btn-light border text-secondary w-100 fw-semibold py-2 rounded-3 shadow-none {{ request('id_semester') ? '' : 'd-none' }}">
                        <i class="ti ti-rotate-clockwise me-1 align-middle"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KONTEN UTAMA YANG AKAN DI-UPDATE VIA AJAX -->
    <div id="dosen-dashboard-content" style="transition: opacity 0.2s ease;">
        @include('pkl.partials.dosen_content_tables', compact('bimbinganList', 'pengujiList', 'totalBimbingan', 'totalPenguji'))
    </div>
</div>

<!-- ========================================== -->
<!-- KUMPULAN MODAL (Diletakkan di luar container AJAX) -->
<!-- ========================================== -->

<!-- 1. MODAL FORM PENILAIAN SEMINAR (PENGUJI) -->
<div class="modal fade" id="modalNilaiSeminar" tabindex="-1" aria-labelledby="modalNilaiSeminarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white border-0 rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalNilaiSeminarLabel">
                    <i class="ti ti-award me-2"></i> Input Nilai Ujian Seminar PKL
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formNilaiSeminar" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Mahasiswa (NIM)</label>
                        <input type="text" id="mhsNama" class="form-control bg-light fw-bold text-dark" readonly placeholder="Memuat data mahasiswa...">
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-slate-800 mb-0">
                            <i class="ti ti-list-check text-success me-1"></i> Komponen Penilaian Seminar
                        </h6>
                        <span class="badge bg-success-subtle text-success small px-2 py-1">Skala Nilai: 0 - 100</span>
                    </div>

                    <div id="komponen-penilaian-container" class="mb-3">
                        <div class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2 text-success"></span> Memuat komponen penilaian...
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan / Masukan Umum Penguji</label>
                        <textarea name="catatan" id="inputCatatan" class="form-control" rows="3" placeholder="Masukkan catatan evaluasi atau revisi untuk mahasiswa..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border text-secondary px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnSimpanNilai">
                        <i class="ti ti-check me-1"></i> Simpan Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL INPUT NILAI BIMBINGAN (PEMBIMBING) -->
<div class="modal fade" id="modalNilaiPembimbing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark border-0 rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-award me-2"></i> Input Nilai Bimbingan PKL
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNilaiPembimbing" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Mahasiswa</label>
                        <input type="text" id="mhsNamaPembimbing" class="form-control bg-light fw-bold text-dark" readonly>
                    </div>

                    <div id="containerKomponenNilai">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Nilai Proses Bimbingan (0 - 100) <span class="text-danger">*</span></label>
                            <input type="number" name="nilai_bimbingan" id="inputNilaiBimbingan" class="form-control" min="0" max="100" step="0.01" placeholder="Cth: 85.5" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan / Evaluasi Pembimbing</label>
                        <textarea name="catatan_pembimbing" id="inputCatatanPembimbing" class="form-control" rows="3" placeholder="Masukkan evaluasi bimbingan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light border text-secondary px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-semibold px-4 shadow-sm" id="btnSimpanNilaiPembimbing">
                        <i class="ti ti-check me-1"></i> Simpan Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL PRATINJAU BERKAS (VIEWER MODAL) -->
<div class="modal fade" id="modalViewBerkas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow" style="height: 85vh;">
            <div class="modal-header border-bottom px-4 bg-light">
                <h5 class="modal-title fw-bold text-slate-800">
                    <i class="ti ti-file-text me-2 text-primary"></i>Pratinjau Berkas Mahasiswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 h-100 bg-dark">
                <iframe id="iframeViewer" src="" style="width: 100%; height: 100%; border: none;" frameborder="0"></iframe>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light">
                <a href="" id="btnDownloadFallback" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-download me-1"></i> Buka di Tab Baru / Download
                </a>
                <button type="button" class="btn btn-sm btn-light border text-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Fungsi Switcher Tampilan Card & Tabel Seminar
    function switchView(type) {
        if (type === 'card') {
            $('#seminarCardView').removeClass('d-none');
            $('#seminarTableView').addClass('d-none');
            $('#btnViewCard').addClass('active');
            $('#btnViewTable').removeClass('active');
        } else {
            $('#seminarTableView').removeClass('d-none');
            $('#seminarCardView').addClass('d-none');
            $('#btnViewTable').addClass('active');
            $('#btnViewCard').removeClass('active');
        }
    }

    $(document).ready(function() {
        // 1. Inisialisasi Select2
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }

        // 2. Pembersih Global Sisa Backdrop Modal Bootstrap 5
        $(document).on('hidden.bs.modal', '.modal', function() {
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({
                    'overflow': '',
                    'padding-right': ''
                });
            }
        });

        // 3. Fungsi Utama Fetch Data Dosen via AJAX
        function fetchDosenData(idSemester = '') {
            $('#dosen-dashboard-content').css('opacity', '0.4');

            $.ajax({
                url: "{{ route('pkl.dosen.index') }}",
                type: 'GET',
                data: {
                    id_semester: idSemester
                },
                success: function(response) {
                    $('#dosen-dashboard-content').html(response).css('opacity', '1');
                    if (idSemester) {
                        $('#btnResetFilter').removeClass('d-none');
                    } else {
                        $('#btnResetFilter').addClass('d-none');
                    }
                },
                error: function() {
                    $('#dosen-dashboard-content').css('opacity', '1');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat memuat data filter.', 'error');
                    }
                }
            });
        }

        // Trigger Dropdown Filter Semester & Reset
        $(document).on('change', '#filterSemesterDosen', function() {
            fetchDosenData($(this).val());
        });

        $(document).on('click', '#btnResetFilter', function() {
            $('#filterSemesterDosen').val(null).trigger('change');
        });

        // 4. Handler Filter Widget Agenda Ujian (Semua / Pembimbing / Penguji)
        $(document).on('click', '.btn-filter-agenda', function() {
            let filterValue = $(this).data('filter');

            $('.btn-filter-agenda')
                .removeClass('active btn-primary text-white')
                .addClass('text-secondary');
            $('.btn-filter-agenda .badge')
                .removeClass('bg-white text-primary')
                .addClass('bg-secondary-subtle text-secondary');

            $(this)
                .addClass('active btn-primary text-white')
                .removeClass('text-secondary');
            $(this).find('.badge')
                .removeClass('bg-secondary-subtle text-secondary')
                .addClass('bg-white text-primary');

            let visibleCount = 0;
            $('.agenda-card-item').each(function() {
                let itemRole = $(this).data('role');
                if (filterValue === 'all' || itemRole === filterValue) {
                    $(this).removeClass('d-none');
                    visibleCount++;
                } else {
                    $(this).addClass('d-none');
                }
            });

            if (visibleCount === 0) {
                $('#emptyFilterAgenda').removeClass('d-none');
            } else {
                $('#emptyFilterAgenda').addClass('d-none');
            }
        });

        // 5. Handler Tombol Lihat Berkas (Iframe Modal)
        $(document).on('click', '.btn-view-berkas', function() {
            let fileUrl = $(this).data('url');
            if (fileUrl) {
                $('#iframeViewer').attr('src', fileUrl);
                $('#btnDownloadFallback').attr('href', fileUrl);
                let modalEl = document.getElementById('modalViewBerkas');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                Swal.fire('Informasi', 'File berkas tidak ditemukan.', 'warning');
            }
        });

        $('#modalViewBerkas').on('hidden.bs.modal', function() {
            $('#iframeViewer').attr('src', '');
        });

        // 6. Handler Selesai Pelaksanaan PKL (Oleh Dosen Pembimbing)
        $(document).on('click', '.btn-selesai-pkl-dosen', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Konfirmasi PKL Selesai?',
                text: "Menandai pelaksanaan PKL mahasiswa ini telah tuntas untuk melanjutkan ke tahap verifikasi berkas seminar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#059669'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('pkl/dosen/selesai-pelaksanaan') }}/" + id,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message || 'Status PKL mahasiswa berhasil diperbarui.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                fetchDosenData($('#filterSemesterDosen').val());
                            });
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                }
            });
        });

        // 7. Handler Tolak Pelaksanaan PKL
        $(document).on('click', '.btn-tolak-pkl-dosen', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Tolak / Minta Perbaikan?',
                text: "Masukkan catatan alasan penolakan atau perbaikan untuk mahasiswa:",
                input: 'textarea',
                inputPlaceholder: 'Cth: Laporan bimbingan belum lengkap...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                preConfirm: (alasan) => {
                    if (!alasan) Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                    return alasan;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('pkl/dosen/tolak-pelaksanaan') }}/" + id,
                        type: 'POST',
                        data: {
                            catatan_dosen: result.value
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Ditolak',
                                text: res.message || 'Pengajuan bimbingan berhasil ditolak.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                fetchDosenData($('#filterSemesterDosen').val());
                            });
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                }
            });
        });

        // 8. Handler Buka Modal & Submit Nilai Bimbingan (Pembimbing)
        $(document).on('click', '.btn-nilai-pembimbing', function() {
            let id = $(this).data('id');
            let nama = $(this).data('nama');
            let nim = $(this).data('nim');

            $('#mhsNamaPembimbing').val(nama + ' (' + nim + ')');
            $('#formNilaiPembimbing').attr('action', "{{ url('pkl/dosen/simpan-nilai-bimbingan') }}/" + id);
            $('#containerKomponenNilai').html('<div class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat komponen penilaian...</div>');

            $.ajax({
                url: "{{ url('pkl/dosen/get-nilai-bimbingan') }}/" + id,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        $('#inputCatatanPembimbing').val(res.catatan_pembimbing ?? '');
                        if (res.komponen_nilai && res.komponen_nilai.length > 0) {
                            let html = '';
                            res.komponen_nilai.forEach(function(item) {
                                html += `
                                    <div class="mb-3 border-bottom pb-2">
                                        <label class="form-label small fw-semibold text-dark">${item.nama_komponen} (Bobot: ${item.bobot ?? 0}%) <span class="text-danger">*</span></label>
                                        <input type="number" name="nilai_komponen[${item.komponen_penilaian_pkl_id}]" 
                                               class="form-control" min="0" max="100" step="0.01" 
                                               value="${item.nilai_angka ?? ''}" placeholder="0 - 100" required>
                                    </div>
                                `;
                            });
                            $('#containerKomponenNilai').html(html);
                        } else if (res.nilai_bimbingan !== undefined) {
                            let html = `
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">Nilai Proses Bimbingan (0 - 100) <span class="text-danger">*</span></label>
                                    <input type="number" name="nilai_bimbingan" id="inputNilaiBimbingan" class="form-control" min="0" max="100" step="0.01" value="${res.nilai_bimbingan ?? ''}" placeholder="Cth: 85.5" required>
                                </div>
                            `;
                            $('#containerKomponenNilai').html(html);
                        }
                    }
                },
                error: function() {
                    $('#containerKomponenNilai').html('<div class="text-danger small py-2">Gagal memuat komponen penilaian.</div>');
                }
            });

            let modalEl = document.getElementById('modalNilaiPembimbing');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        $('#formNilaiPembimbing').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#btnSimpanNilaiPembimbing').prop('disabled', true).html('<i class="ti ti-loader animate-spin me-1"></i> Menyimpan...');
                },
                success: function(res) {
                    $('#btnSimpanNilaiPembimbing').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');
                    let modalEl = document.getElementById('modalNilaiPembimbing');
                    let modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Nilai bimbingan berhasil disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        fetchDosenData($('#filterSemesterDosen').val());
                    });
                },
                error: function(xhr) {
                    $('#btnSimpanNilaiPembimbing').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menyimpan nilai.', 'error');
                }
            });
        });

        // 9. Handler Buka Modal & Submit Nilai Seminar (Penguji)
        $(document).on('click', '.btn-nilai-seminar', function() {
            let pengajuanId = $(this).data('id');

            $('#mhsNama').val('Memuat...');
            $('#inputCatatan').val('');
            $('#komponen-penilaian-container').html(`
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    Memuat komponen penilaian semester...
                </div>
            `);
            $('#formNilaiSeminar').attr('action', "{{ url('pkl/dosen/penilaian') }}/" + pengajuanId);

            $.ajax({
                url: "{{ url('pkl/dosen/komponen-penilaian') }}/" + pengajuanId,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#mhsNama').val(response.nama_mahasiswa + ' (' + response.nim + ')');
                        let catatanText = '';
                        if (response.catatan) {
                            catatanText = typeof response.catatan === 'object' ? (response.catatan.catatan || '') : response.catatan;
                        }
                        $('#inputCatatan').val(catatanText);
                        $('#komponen-penilaian-container').html(response.html);
                    } else {
                        $('#komponen-penilaian-container').html(`
                            <div class="alert alert-warning small mb-0">${response.message || 'Gagal memuat komponen penilaian.'}</div>
                        `);
                    }
                },
                error: function(xhr) {
                    let errorMsg = xhr.responseJSON?.message || 'Gagal memuat komponen penilaian.';
                    $('#komponen-penilaian-container').html(`
                        <div class="alert alert-danger small mb-0"><i class="ti ti-alert-circle me-1"></i> ${errorMsg}</div>
                    `);
                }
            });

            let modalEl = document.getElementById('modalNilaiSeminar');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        $('#formNilaiSeminar').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#btnSimpanNilai').prop('disabled', true).html('<i class="ti ti-loader animate-spin me-1"></i> Menyimpan...');
                },
                success: function(response) {
                    $('#btnSimpanNilai').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');

                    let modalElement = document.getElementById('modalNilaiSeminar');
                    let modalObj = bootstrap.Modal.getInstance(modalElement);
                    if (modalObj) modalObj.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Nilai ujian seminar berhasil disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    fetchDosenData($('#filterSemesterDosen').val());
                },
                error: function(xhr) {
                    $('#btnSimpanNilai').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');
                    let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan nilai.';
                    Swal.fire('Gagal', errorMsg, 'error');
                }
            });
        });
    });
</script>
@endpush
@endsection