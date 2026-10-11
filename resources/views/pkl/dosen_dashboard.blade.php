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
        border: 1px solid rgba(226,232,240,.85) !important;
        border-radius: 18px !important;
        box-shadow: 0 5px 18px rgba(20, 33, 61, .045) !important;
    }
    .pkl-page .table > :not(caption) > * > * { padding: .9rem .75rem; }
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
                                <option value="{{ $sem->kode_semester }}" {{ request('id_semester') == $sem->kode_semester ? 'selected' : '' }}>
                                    {{ $sem->nama_semester }} {{ $sem->aktif ? '✨ (Aktif)' : '' }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 d-flex align-items-end">
                <div class="w-100">
                    <!-- Tombol Reset dengan transisi kemunculan yang rapi -->
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


<!-- MODAL FORM PENILAIAN SEMINAR -->
<div class="modal fade" id="modalNilaiSeminar" tabindex="-1" aria-labelledby="modalNilaiSeminarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white border-0 rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalNilaiSeminarLabel">
                    <i class="ti ti-award me-2"></i> Input Nilai Ujian Seminar PKL
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form untuk submit nilai -->
            <form id="formNilaiSeminar" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Mahasiswa</label>
                        <input type="text" id="mhsNama" class="form-control bg-light fw-bold text-dark" readonly>
                    </div>
            
                    <hr class="my-3">
                    <h6 class="fw-bold text-slate-800 mb-3"><i class="ti ti-list-check me-1"></i> Komponen Penilaian PKL</h6>
            
                    <!-- Looping Komponen Penilaian dari Database -->
                    <div id="komponen-penilaian-container">
                        <!-- Akan di-generate dinamis atau diloop via Blade jika form bersifat statis per mahasiswa -->
                    </div>
            
                    <div class="mb-3 mt-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan / Masukan Umum Penguji</label>
                        <textarea name="catatan" id="inputCatatan" class="form-control" rows="3" placeholder="Masukkan catatan atau revisi untuk mahasiswa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light border text-secondary px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnSimpanNilai">
                        <i class="ti ti-check me-1"></i> Simpan Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')

<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ theme: 'bootstrap-5' });
        }

        // Fungsi AJAX Filter Semester
        function fetchDosenData(idSemester = '') {
            $('#dosen-dashboard-content').css('opacity', '0.4');

            $.ajax({
                url: "{{ route('pkl.dosen.index') }}",
                type: 'GET',
                data: { id_semester: idSemester },
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

        $(document).on('change', '#filterSemesterDosen', function() {
            let semesterId = $(this).val();
            fetchDosenData(semesterId);
        });

        $(document).on('click', '#btnResetFilter', function() {
            $('#filterSemesterDosen').val(null).trigger('change');
            fetchDosenData('');
        });

        // ==========================================
        // EVENT: KLIK TOMBOL "BERI NILAI" SEMINAR
        // ==========================================
        $(document).on('click', '.btn-nilai-seminar', function() {
            let pengajuanId = $(this).data('id');
            
            // Kosongkan dan beri indikator loading
            $('#komponen-penilaian-container').html('<div class="text-center py-3 text-muted">Memuat komponen penilaian...</div>');
            $('#formNilaiSeminar').attr('action', "{{ url('pkl/dosen/penilaian') }}/" + pengajuanId);
        
            // Ambil data komponen via AJAX
            $.ajax({
                url: "{{ url('pkl/dosen/komponen-penilaian') }}/" + pengajuanId,
                type: 'GET',
                success: function(response) {
                    if(response.success) {
                        $('#mhsNama').val(response.nama_mahasiswa + ' (' + response.nim + ')');
                        $('#inputCatatan').val(response.catatan.catatan );
                        // Masukkan hasil looping HTML ke dalam container
                        $('#komponen-penilaian-container').html(response.html);
                    }
                },
                error: function() {
                    $('#komponen-penilaian-container').html('<div class="text-danger small">Gagal memuat komponen penilaian.</div>');
                }
            });
        
            let modalNilai = new bootstrap.Modal(document.getElementById('modalNilaiSeminar'));
            modalNilai.show();
        });

        // Submit form penilaian via AJAX (Opsional agar mulus tanpa reload)
        $('#formNilaiSeminar').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let url = form.attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#btnSimpanNilai').prop('disabled', true).html('<i class="ti ti-loader animate-spin me-1"></i> Menyimpan...');
                },
                success: function(response) {
                    $('#btnSimpanNilai').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');
                    
                    // Tutup modal
                    let modalElement = document.getElementById('modalNilaiSeminar');
                    let modalObj = bootstrap.Modal.getInstance(modalElement);
                    modalObj.hide();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Berhasil!', 'Nilai ujian seminar berhasil disimpan.', 'success');
                    } else {
                        alert('Nilai berhasil disimpan.');
                    }

                    // Refresh data tabel dosen via AJAX
                    let currentSemester = $('#filterSemesterDosen').val();
                    fetchDosenData(currentSemester);
                },
                error: function(xhr) {
                    $('#btnSimpanNilai').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Simpan Nilai');
                    let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan nilai.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2 jika digunakan di project Anda
        if ($.fn.select2) {
            $('.select2').select2({ theme: 'bootstrap-5' });
        }

        // Fungsi utama untuk mengambil data via AJAX berdasarkan id_semester
        function fetchDosenData(idSemester = '') {
            // Berikan efek transisi redup (fade) pada kontainer konten saat memuat data
            $('#dosen-dashboard-content').css('opacity', '0.4');

            $.ajax({
                url: "{{ route('pkl.dosen.index') }}",
                type: 'GET',
                data: { id_semester: idSemester },
                success: function(response) {
                    // Masukkan respons view HTML langsung ke dalam kontainer
                    $('#dosen-dashboard-content').html(response).css('opacity', '1');

                    // Munculkan atau sembunyikan tombol reset secara dinamis
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
                    } else {
                        alert('Terjadi kesalahan saat memuat data.');
                    }
                }
            });
        }

        // Trigger saat pilihan dropdown semester diubah
        $(document).on('change', '#filterSemesterDosen', function() {
            let semesterId = $(this).val();
            fetchDosenData(semesterId);
        });

        // Trigger saat tombol "Reset Filter" diklik
        $(document).on('click', '#btnResetFilter', function() {
            $('#filterSemesterDosen').val(null).trigger('change'); // Kosongkan select2 & nilai form
            fetchDosenData(''); // Muat ulang data tanpa filter semester
        });
    });
</script>
@endpush
@endsection