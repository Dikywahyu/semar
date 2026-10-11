@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Manajemen Kegiatan & Sesi</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('apps.activities.index') }}">Apps</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Kegiatan</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center">
                    <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                    <h4 class="card-title mb-0 d-flex align-items-center">
                        <iconify-icon icon="solar:calendar-mark-line-duotone" class="me-2 text-primary fs-6"></iconify-icon>
                        Daftar Kegiatan Rapat, Matrikulasi & Acara
                    </h4>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddActivity">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="me-1 fs-5"></iconify-icon>
                        Tambah Kegiatan Baru
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-activities" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Judul Kegiatan</th>
                                    <th>Tipe Kegiatan</th>
                                    <th>Lokasi / Ruangan</th>
                                    <th>Jumlah Sesi</th>
                                    <th width="210" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: TAMBAH KEGIATAN --}}
<div class="modal fade" id="modalAddActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 800px !important; width: 100%;">
        <form id="formAddActivity" action="{{ route('apps.activities.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">Tambah Kegiatan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-bold">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: Rapat Koordinasi Kurikulum / Matrikulasi MTI 2026" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold">Tipe Kegiatan <span class="text-danger">*</span></label>
                            <select name="activity_type_id" class="form-select" required>
                                <option value="">-- Pilih Tipe --</option>
                                @foreach($activityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Lokasi / Ruangan / Link Zoom</label>
                            <input type="text" name="location" class="form-control" placeholder="Contoh: Ruang Rapat Lt. 2 / Zoom Meeting ID: 8912xxxx">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Deskripsi / Agenda Kegiatan</label>
                            <textarea name="description" class="form-control summernote-editor" rows="5"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveActivity">Simpan Kegiatan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: EDIT KEGIATAN --}}
<div class="modal fade" id="modalEditActivity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 800px !important; width: 100%;">
        <form id="formEditActivity" method="POST" class="w-100">
            @csrf
            @method('PUT')
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">
                        <iconify-icon icon="solar:pen-new-square-line-duotone" class="me-2"></iconify-icon>
                        Edit Kegiatan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-bold">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="edit-title" class="form-control" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold">Tipe Kegiatan <span class="text-danger">*</span></label>
                            <select name="activity_type_id" id="edit-activity_type_id" class="form-select" required>
                                <option value="">-- Pilih Tipe --</option>
                                @foreach($activityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Lokasi / Ruangan</label>
                            <input type="text" name="location" id="edit-location" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Deskripsi / Agenda</label>
                            <textarea name="description" id="edit-description" class="form-control summernote-editor" rows="5"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" id="btnUpdateActivity">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 3: MANAJEMEN SESI ABSENSI & QR CODE --}}
<div class="modal fade" id="modalManageSessions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1100px !important; width: 100%;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title d-flex align-items-center text-white" id="session-modal-title">
                    <iconify-icon icon="solar:qr-code-line-duotone" class="me-2"></iconify-icon>
                    Sesi Absensi & Token QR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                {{-- Form Tambah Sesi --}}
                <form id="formAddSession" method="POST" class="card card-body bg-light-subtle border mb-4">
                    @csrf
                    <h6 class="fw-bold mb-3 text-primary">+ Tambah Sesi Presensi Baru</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Sesi</label>
                            <input type="text" name="session_name" class="form-control form-control-sm" placeholder="Sesi Pagi / Hari ke-1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Mulai Absensi</label>
                            <input type="datetime-local" name="start_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tutup Absensi</label>
                            <input type="datetime-local" name="end_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Akses Form</label>
                            <select name="access_type" class="form-select form-select-sm">
                                <option value="public">Publik (Tamu/Bebas)</option>
                                <option value="restricted">Restricted (Login Only)</option>
                            </select>
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-sm btn-primary" id="btnSaveSession">
                                <iconify-icon icon="solar:add-circle-line-duotone" class="me-1"></iconify-icon> Tambah Sesi
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Tabel Daftar Sesi --}}
                <h6 class="fw-bold mb-2">Daftar Sesi yang Sudah Dibuat</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0" id="table-sessions-list">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Sesi</th>
                                <th>Waktu Dibuka</th>
                                <th>Waktu Ditutup</th>
                                <th>Akses</th>
                                <th class="text-center" width="120">Hadir</th>
                                <th class="text-center" width="200">Layar & Undangan</th>
                                <th class="text-center" width="110">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="session-rows-container">
                            {{-- Content loaded via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 4: EDIT SESI PRESENSI --}}
<div class="modal fade" id="modalEditSession" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title d-flex align-items-center text-white">
                    <iconify-icon icon="solar:pen-2-line-duotone" class="me-2"></iconify-icon>
                    Edit Sesi Presensi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditSession">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_session_id" name="session_id">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Sesi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_session_name" name="session_name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Waktu Buka <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Waktu Tutup <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hak Akses <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_access_type" name="access_type" required>
                            <option value="public">Publik (Tamu & Umum)</option>
                            <option value="restricted">Restricted (Hanya Login)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" id="btnUpdateSession">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 5: DETAIL DAFTAR PESERTA PRESENSI --}}
<div class="modal fade" id="modalAttendeesList" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title d-flex align-items-center text-white" id="attendees-modal-title">
                    <iconify-icon icon="solar:users-group-two-rounded-line-duotone" class="me-2"></iconify-icon>
                    Daftar Peserta Hadir
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Total Kehadiran: <strong id="attendees-count" class="text-primary fs-5">0</strong> orang</span>
                </div>
                <div class="table-responsive border rounded-3" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Peserta</th>
                                <th>Email / Whatsapp</th>
                                <th>Instansi / Jabatan</th>
                                <th>Waktu Presensi</th>
                            </tr>
                        </thead>
                        <tbody id="attendees-rows-container">
                            <!-- Render via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 6: TULIS BERITA ACARA & NOTULENSI KEGIATAN --}}
<div class="modal fade" id="modalWriteReport" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 900px !important;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title d-flex align-items-center text-white" id="report-modal-title">
                    <iconify-icon icon="solar:document-text-line-duotone" class="me-2"></iconify-icon>
                    Berita Acara & Notulensi Kegiatan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSaveReport" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="report_activity_id" name="activity_id">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor Surat / Dokumen Berita Acara</label>
                        <input type="text" name="document_number" id="report_document_number" class="form-control" placeholder="Contoh: 045/BA/BAAK/VII/2026">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ringkasan Kegiatan</label>
                        <textarea name="summary" id="report_summary" class="form-control" rows="2" placeholder="Gambaran umum pelaksanaan kegiatan..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hasil Pembahasan & Keputusan <span class="text-danger">*</span></label>
                        <textarea name="decisions" id="report_decisions" class="form-control summernote-editor" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rencana Tindak Lanjut (Action Items)</label>
                        <textarea name="action_items" id="report_action_items" class="form-control summernote-editor" rows="3"></textarea>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold text-dark mb-3">Pengesahan / Penandatangan Berita Acara</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Pejabat Pengesah</label>
                            <input type="text" name="approved_by_name" id="report_approved_by_name" class="form-control form-control-sm" placeholder="Nama Lengkap & Gelar">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jabatan Pejabat</label>
                            <input type="text" name="approved_by_position" id="report_approved_by_position" class="form-control form-control-sm" placeholder="Contoh: Kepala BAAK / Dekan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">NIP / NIDN / Identitas</label>
                            <input type="text" name="approved_by_identifier" id="report_approved_by_identifier" class="form-control form-control-sm" placeholder="Nomor Induk Pejabat">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">File Lampiran (Dokumentasi/Materi PDF/Gambar)</label>
                        <input type="file" name="attachment_file" class="form-control">
                        <div id="attachment-preview" class="mt-1 small"></div>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                    <div id="copy-link-container" style="display: none;">
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" id="btnCopyApprovalLink">
                            <iconify-icon icon="solar:copy-line-duotone"></iconify-icon> Salin Link Pengesahan
                        </button>
                        <small id="copy-status" class="text-success ms-2 fw-semibold" style="display: none;">✔ Link tersalin!</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success text-white" id="btnSaveReport">Simpan Berita Acara</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Summernote CSS & JS CDN --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentActivityId = null;
    let currentActivityTitle = '';
    let currentActivityDescription = '';
    let currentSessionsData = [];
    let currentApprovalUrl = '';

    function formatDatetime(isoString) {
        if (!isoString) return '-';
        let date = new Date(isoString);
        return date.toLocaleString('id-ID', {
            timeZone: 'Asia/Jakarta',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).replace('.', ':') + ' WIB';
    }

    function toDatetimeLocal(isoString) {
        if (!isoString) return '';
        let d = new Date(isoString);
        let pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // Helper Toast Notification
    function showToast(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2000
        });
    }

    $(document).ready(function() {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });

        // Inisialisasi Summernote Editor pada Textarea bertaraf CMS
        $('.summernote-editor').summernote({
            height: 180,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        });

        // 1. Init DataTables Kegiatan
        var table = $('#table-activities').DataTable({
            processing: true,
            serverSide: false,
            ajax: "{{ route('apps.activities.index') }}",
            columns: [
                {
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'title',
                    render: (data, type, row) => `
                        <div>
                            <span class="fw-bold text-dark fs-3">${data}</span>
                            ${row.description ? `<br><small class="text-muted">${$('<div>').html(row.description).text().substring(0, 60)}...</small>` : ''}
                        </div>
                    `
                },
                {
                    data: 'activity_type.name',
                    render: (data) => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">${data || 'Umum'}</span>`
                },
                {
                    data: 'location',
                    render: (data) => `<span class="text-muted"><iconify-icon icon="solar:map-point-line-duotone" class="me-1"></iconify-icon>${data || '-'}</span>`
                },
                {
                    data: 'sessions_count',
                    render: (data) => `<span class="badge bg-light text-dark border px-2 py-1">${data || 0} Sesi</span>`
                },
                {
                    data: 'id',
                    className: 'text-center',
                    render: function(data, type, row) {
                        // Cek apakah laporan/notulensi terkait sudah memiliki token digital signature
                        let report = row.report;
                        let isApproved = report && report.approval_status === 'approved';
                        let shareButton = '';
                
                        if (isApproved && report.digital_signature_token) {
                            let publicUrl = "{{ url('/approve-report') }}/" + report.digital_signature_token;
                            shareButton = `
                                <button type="button" class="btn btn-sm btn-outline-primary btn-share-report shadow-sm" 
                                        data-url="${publicUrl}" 
                                        data-title="${row.title}" 
                                        title="Bagikan Link Notulen Disahkan">
                                    <iconify-icon icon="solar:share-bold-duotone"></iconify-icon>
                                </button>
                            `;
                        }
                
                        return `
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-info btn-manage-session shadow-sm" data-id="${data}" data-title="${row.title}" title="Kelola Sesi & QR">
                                    <iconify-icon icon="solar:qr-code-line-duotone"></iconify-icon>
                                </button> 
                                <button type="button" class="btn btn-sm btn-outline-success btn-write-report shadow-sm" data-id="${data}" data-title="${row.title}" title="Berita Acara & Notulensi">
                                    <iconify-icon icon="solar:document-text-line-duotone"></iconify-icon>
                                </button>
                                ${shareButton}
                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit-activity shadow-sm" data-id="${data}" title="Edit Kegiatan">
                                    <iconify-icon icon="solar:pen-new-square-line-duotone"></iconify-icon>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-activity shadow-sm" data-id="${data}" title="Hapus Kegiatan">
                                    <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        // 2. Tambah Kegiatan
        $('#formAddActivity').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveActivity');
            btn.prop('disabled', true).html('Menyimpan...');

            $.post($(this).attr('action'), $(this).serialize(), function(res) {
                $('#modalAddActivity').modal('hide');
                $('#formAddActivity')[0].reset();
                $('#modalAddActivity').find('.summernote-editor').summernote('code', '');
                table.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message || 'Kegiatan berhasil dibuat',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).fail(xhr => {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }).always(() => btn.prop('disabled', false).html('Simpan Kegiatan'));
        });

        // 3. Edit Kegiatan - Fetch Data
        $(document).on('click', '.btn-edit-activity', function() {
            let id = $(this).data('id');
            let urlEdit = "{{ route('apps.activities.show', ':id') }}".replace(':id', id);
            let urlUpdate = "{{ route('apps.activities.update', ':id') }}".replace(':id', id);

            $.get(urlEdit, function(res) {
                let data = res.data;
                $('#edit-title').val(data.title);
                $('#edit-activity_type_id').val(data.activity_type_id);
                $('#edit-location').val(data.location);
                $('#edit-description').summernote('code', data.description || '');
                $('#formEditActivity').attr('action', urlUpdate);

                $('#modalEditActivity').modal('show');
            });
        });

        // 4. Update Kegiatan
        $('#formEditActivity').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnUpdateActivity');
            btn.prop('disabled', true).html('Updating...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalEditActivity').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Kegiatan diperbarui',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Cek kembali inputan', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Simpan Perubahan');
                }
            });
        });

        // 5. Delete Kegiatan
        $(document).on('click', '.btn-delete-activity', function() {
            let id = $(this).data('id');
            let urlDelete = "{{ route('apps.activities.destroy', ':id') }}".replace(':id', id);

            Swal.fire({
                title: 'Hapus Kegiatan?',
                text: "Semua sesi dan riwayat absensi pada kegiatan ini akan terhapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(urlDelete, {
                        _method: 'DELETE'
                    }, function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Dihapus!', res.message || 'Kegiatan berhasil dihapus', 'success');
                    });
                }
            });
        });

        // 6. Modal Kelola Sesi - Buka & Load Sesi
        $(document).on('click', '.btn-manage-session', function() {
            currentActivityId = $(this).data('id');
            currentActivityTitle = $(this).data('title');
            $('#session-modal-title').html(`<iconify-icon icon="solar:qr-code-line-duotone" class="me-2"></iconify-icon> Kelola Sesi: ${currentActivityTitle}`);
            
            let urlStoreSession = "{{ route('apps.activities.sessions.store', ':id') }}".replace(':id', currentActivityId);
            $('#formAddSession').attr('action', urlStoreSession);

            loadSessions(currentActivityId);
            $('#modalManageSessions').modal('show');
        });

        // 7. Submit Sesi Baru
        $('#formAddSession').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveSession');
            btn.prop('disabled', true);

            $.post($(this).attr('action'), $(this).serialize(), function(res) {
                $('#formAddSession')[0].reset();
                loadSessions(currentActivityId);
                table.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Sesi Dibuat!',
                    text: 'Token QR Code berhasil di-generate secara otomatis.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).fail(xhr => {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal membuat sesi', 'error');
            }).always(() => btn.prop('disabled', false));
        });

        // 8. Edit Sesi - Buka Modal Edit Sesi
        $(document).on('click', '.btn-edit-session', function() {
            let id     = $(this).data('id');
            let name   = $(this).data('name');
            let start  = $(this).data('start');
            let end    = $(this).data('end');
            let access = $(this).data('access');

            $('#edit_session_id').val(id);
            $('#edit_session_name').val(name);
            $('#edit_start_time').val(toDatetimeLocal(start));
            $('#edit_end_time').val(toDatetimeLocal(end));
            $('#edit_access_type').val(access);

            $('#modalEditSession').modal('show');
        });

        // 9. Update Sesi Presensi
        $('#formEditSession').on('submit', function(e) {
            e.preventDefault();
            let id = $('#edit_session_id').val();
            let updateUrl = "{{ route('apps.sessions.update', ':id') }}".replace(':id', id);
            let btn = $('#btnUpdateSession');
            btn.prop('disabled', true).html('Updating...');

            $.ajax({
                url: updateUrl,
                type: 'PUT',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalEditSession').modal('hide');
                    loadSessions(currentActivityId);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Sesi presensi berhasil diperbarui',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal memperbarui sesi', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Simpan Perubahan');
                }
            });
        });

        // 10. Delete Sesi Presensi
        $(document).on('click', '.btn-delete-session', function() {
            let id = $(this).data('id');
            let deleteUrl = "{{ route('apps.sessions.destroy', ':id') }}".replace(':id', id);

            Swal.fire({
                title: 'Hapus Sesi Presensi?',
                text: "Data kehadiran peserta pada sesi ini akan ikut terhapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        success: function(res) {
                            loadSessions(currentActivityId);
                            table.ajax.reload(null, false);
                            Swal.fire('Dihapus!', res.message || 'Sesi berhasil dihapus', 'success');
                        },
                        error: function() {
                            Swal.fire('Gagal', 'Gagal menghapus sesi presensi', 'error');
                        }
                    });
                }
            });
        });

        // 11. Handler Buka Modal Berita Acara & Load Data Lama
        $(document).on('click', '.btn-write-report', function() {
            let actId = $(this).data('id');
            let title = $(this).data('title');
        
            $('#report_activity_id').val(actId);
            $('#report-modal-title').html(`<iconify-icon icon="solar:document-text-line-duotone" class="me-2"></iconify-icon> Notulensi: ${title}`);
        
            let getReportUrl = "{{ route('apps.activities.show', ':id') }}".replace(':id', actId);
        
            $.get(getReportUrl, function(res) {
                let r = res.data ? res.data.report : null;
        
                if (r) {
                    $('#report_document_number').val(r.document_number || '');
                    $('#report_summary').val(r.summary || '');
                    $('#report_decisions').summernote('code', r.decisions || '');
                    $('#report_action_items').summernote('code', r.action_items || '');
                    $('#report_approved_by_name').val(r.approved_by_name || '');
                    $('#report_approved_by_position').val(r.approved_by_position || '');
                    $('#report_approved_by_identifier').val(r.approved_by_identifier || '');
        
                    if (r.attachment_file) {
                        $('#attachment-preview').html(`<span class="text-success fw-bold">✔ Ada file lampiran tersimpan</span>`);
                    } else {
                        $('#attachment-preview').html('');
                    }
        
                    if (r.digital_signature_token) {
                        currentApprovalUrl = "{{ url('/approve-report') }}/" + r.digital_signature_token;
                        $('#copy-link-container').show();
                    } else {
                        $('#copy-link-container').hide();
                    }
                } else {
                    $('#formSaveReport')[0].reset();
                    $('#report_activity_id').val(actId);
                    $('#report_decisions').summernote('code', '');
                    $('#report_action_items').summernote('code', '');
                    $('#attachment-preview').html('');
                    $('#copy-link-container').hide();
                }
        
                $('#modalWriteReport').modal('show');
            });
        });

        // 12. Handler Tombol Salin Link Pengesahan
        $(document).on('click', '#btnCopyApprovalLink', function() {
            if (!currentApprovalUrl) return;

            navigator.clipboard.writeText(currentApprovalUrl).then(function() {
                $('#copy-status').fadeIn().delay(1500).fadeOut();
            }).catch(function() {
                alert('Silakan salin link berikut secara manual: ' + currentApprovalUrl);
            });
        });

        // 13. Submit Simpan / Update Berita Acara
        $('#formSaveReport').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveReport');
            btn.prop('disabled', true).html('Saving...');

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('apps.activity-reports.store') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $('#modalWriteReport').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: res.message || 'Berita Acara berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menyimpan berita acara', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Simpan Berita Acara');
                }
            });
        });

        // 14. EVENT HANDLER: SALIN UNDANGAN DARI TABEL UTAMA KEGIATAN
        $(document).on('click', '.btn-copy-activity-invitation', function() {
            let title = $(this).data('title');
            let location = $(this).data('location') || 'Ruangan Utama / Zoom';

            let textToCopy = `*UNDANGAN PRESENSI KEGIATAN*\n\n` +
                             `*Kegiatan:* ${title}\n` +
                             `*Lokasi:* ${location}\n\n` +
                             `Silakan hadir dan melakukan presensi kehadiran melalui scan QR Code yang disediakan panitia pada lokasi acara.\n\n` +
                             `_Sistem Informasi Akademik & Presensi Kegiatan_`;

            navigator.clipboard.writeText(textToCopy).then(() => {
                showToast('Teks undangan kegiatan berhasil disalin!');
            });
        });

        // 15. EVENT HANDLER: SALIN UNDANGAN SESI (DARI MODAL KELOLA SESI)
        $(document).on('click', '.btn-copy-session-invitation', function() {
            let sessionName = $(this).data('session-name');
            let qrUrl       = $(this).data('qr-url');
            let start       = $(this).data('start');
            let end         = $(this).data('end');
        
            let descriptionText = '';
            if (typeof currentActivityDescription !== 'undefined' && currentActivityDescription) {
                let plainDesc = $('<div>').html(currentActivityDescription).text();
                descriptionText = `\n📋 *Agenda Kegiatan:*\n${plainDesc}\n`;
            }
        
            let invitationText = `*UNDANGAN KEGIATAN*\n\n` +
                                 `📌 *Kegiatan:* ${currentActivityTitle}\n` +
                                 `⏱️ *Sesi:* ${sessionName}\n` +
                                 `🕒 *Waktu Absensi    :* ${start} - ${end}\n` +
                                 `${descriptionText}\n` +
                                 `Silakan lakukan presensi kehadiran untuk sesi ini melalui tautan berikut:\n` +
                                 `👉 ${qrUrl}\n\n` +
                                 `_Mohon mengisi presensi tepat waktu. Terima kasih!_`;
        
            navigator.clipboard.writeText(invitationText).then(() => {
                showToast('Teks undangan sesi berhasil disalin!');
            });
        });
        
        // EVENT HANDLER: TOMBOL BAGIKAN / SHARE NOTULEN TER-ACC
        $(document).on('click', '.btn-share-report', function() {
            let reportUrl = $(this).data('url');
            let actTitle = $(this).data('title');
        
            let shareText = `*NOTULENSI & BERITA ACARA RESMI*\n\n` +
                            `📌 *Kegiatan:* ${actTitle}\n` +
                            `Status: ✅ *Telah Disahkan secara Digital*\n\n` +
                            `Silakan akses dan tinjau dokumen lengkap melalui tautan berikut:\n` +
                            `👉 ${reportUrl}\n\n` +
                            `_Dokumen Resmi Akademik_`;
        
            // Cek apakah browser mendukung Web Share API (biasanya di HP / mobile)
            if (navigator.share) {
                navigator.share({
                    title: 'Notulensi Disahkan: ' + actTitle,
                    text: shareText,
                    url: reportUrl,
                }).catch(() => {
                    // Fallback jika dibatalkan
                });
            } else {
                // Fallback salin ke Clipboard jika di Desktop
                navigator.clipboard.writeText(shareText).then(() => {
                    showToast('Link & teks notulensi ter-ACC berhasil disalin ke clipboard!');
                }).catch(() => {
                    alert('Gagal menyalin. Link: ' + reportUrl);
                });
            }
        });
    });

    // Helper Function Load Sesi (Ke Tabel Modal Sesi)
    function loadSessions(activityId) {
        let urlShow = "{{ route('apps.activities.show', ':id') }}".replace(':id', activityId);
        $.get(urlShow, function(res) {
            let sessions = res.data.sessions || [];
            currentSessionsData = sessions;
            currentActivityDescription = res.data.description || '';
            let html = '';

            if (sessions.length === 0) {
                html = `<tr><td colspan="7" class="text-center text-muted py-3">Belum ada sesi absensi yang dibuat.</td></tr>`;
            } else {
                sessions.forEach(s => {
                    let qrUrl = "{{ route('apps.sessions.qr_display', ':sid') }}".replace(':sid', s.id);
                    let formattedStart = formatDatetime(s.start_time);
                    let formattedEnd   = formatDatetime(s.end_time);
                    
                    let attendancesCount = 0;
                    if (s.attendances_count !== undefined) {
                        attendancesCount = s.attendances_count;
                    } else if (s.attendances && Array.isArray(s.attendances)) {
                        attendancesCount = s.attendances.length;
                    }

                    html += `
                        <tr>
                            <td class="fw-bold text-dark">${s.session_name}</td>
                            <td><small class="text-muted fw-medium">${formattedStart}</small></td>
                            <td><small class="text-muted fw-medium">${formattedEnd}</small></td>
                            <td>
                                <span class="badge ${s.access_type === 'public' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'} px-2">
                                    ${s.access_type}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-view-attendees d-inline-flex align-items-center gap-1 px-2 py-1" data-id="${s.id}">
                                    <iconify-icon icon="solar:users-group-two-rounded-line-duotone"></iconify-icon>
                                    <strong>${attendancesCount}</strong> Peserta
                                </button>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="${qrUrl}" target="_blank" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1">
                                        <iconify-icon icon="solar:qr-code-line-duotone"></iconify-icon> Layar QR
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary btn-copy-session-invitation" 
                                            data-session-name="${s.session_name}" 
                                            data-qr-url="${qrUrl}" 
                                            data-start="${formattedStart}" 
                                            data-end="${formattedEnd}" 
                                            title="Salin Teks Undangan WA">
                                        <iconify-icon icon="solar:copy-line-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-edit-session" 
                                            data-id="${s.id}" 
                                            data-name="${s.session_name}" 
                                            data-start="${s.start_time}" 
                                            data-end="${s.end_time}" 
                                            data-access="${s.access_type}"
                                            title="Edit Sesi">
                                        <iconify-icon icon="solar:pen-2-line-duotone"></iconify-icon>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-session" data-id="${s.id}" title="Hapus Sesi">
                                        <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#session-rows-container').html(html);
        });
    }

    // HANDLER KLIK TOMBOL PESERTA (Membuka Modal Daftar Peserta)
    $(document).on('click', '.btn-view-attendees', function() {
        let sessionId = $(this).data('id');
        let session = currentSessionsData.find(s => s.id == sessionId);

        if (!session) return;

        let attendees = session.attendances || [];
        $('#attendees-modal-title').html(`<iconify-icon icon="solar:users-group-two-rounded-line-duotone" class="me-2"></iconify-icon> Peserta: ${session.session_name}`);
        $('#attendees-count').text(attendees.length);

        let html = '';
        if (attendees.length === 0) {
            html = `<tr><td colspan="5" class="text-center text-muted py-4">Belum ada peserta yang melakukan presensi pada sesi ini.</td></tr>`;
        } else {
            attendees.forEach((att, index) => {
                let name = att.user ? att.user.name : (att.guest_name || '-');
                let email = att.user ? att.user.email : (att.guest_email || '-');
                let phone = att.guest_phone || (att.user ? att.user.phone : '-');
                let inst = att.guest_organization || (att.user ? att.user.institution : '-');
                let position = att.guest_position || (att.user ? att.user.position_title : '-');
                let checkIn = formatDatetime(att.check_in_time);

                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <strong class="text-dark d-block">${name}</strong>
                            ${att.user_id ? '<span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">Internal</span>' : '<span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">Tamu</span>'}
                        </td>
                        <td>
                            <small class="d-block text-dark">${email}</small>
                            <small class="text-muted">${phone}</small>
                        </td>
                        <td>
                            <small class="d-block text-dark fw-semibold">${inst || '-'}</small>
                            <small class="text-muted">${position || '-'}</small>
                        </td>
                        <td><small class="text-muted fw-medium">${checkIn}</small></td>
                    </tr>
                `;
            });
        }

        $('#attendees-rows-container').html(html);
        $('#modalAttendeesList').modal('show');
    });
</script>
@endpush
@endsection