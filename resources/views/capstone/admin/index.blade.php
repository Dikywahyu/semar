@extends('layouts.app')

@push('styles')
<style>
    .capstone-admin-page {
        background-color: #f8fafc;
        color: #0f172a;
    }

    /* Hero Banner */
    .admin-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
        border-radius: 20px;
        padding: 1.75rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    }
    .admin-hero::after {
        content: "";
        position: absolute;
        right: -30px;
        top: -30px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Card Styling */
    .modern-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }

    /* KPI Stat Cards */
    .kpi-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 1.25rem;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    /* Table Styling */
    .table-modern thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1rem;
    }
    .table-modern tbody td {
        padding: 0.95rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
    }
    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }

    /* SweetAlert Custom Inputs */
    .swal2-input, .swal2-select {
        font-size: 0.9rem !important;
        border-radius: 8px !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-3 px-md-4 capstone-admin-page min-vh-100">

    <!-- 1. HERO HEADER -->
    <div class="admin-hero text-white mb-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 mb-2">
                    <i class="ti ti-layout-dashboard me-1"></i> Admin & Koordinator Workspace
                </span>
                <h3 class="fw-bold text-white mb-1">Monitoring & Manajemen Capstone Design</h3>
                <p class="text-white-50 small mb-0">Kelola plotting dosen, verifikasi KPI, jadwal sidang, persetujuan multi-pihak, dan pencetakan dokumen.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('capstone.admin.komponen.index') }}" class="btn btn-light btn-sm fw-semibold rounded-pill px-3">
                    <i class="ti ti-adjustments text-primary me-1"></i> Master Komponen Nilai
                </a>
                <a href="{{ route('capstone.admin.grade-recap') }}" class="btn btn-success btn-sm fw-semibold rounded-pill px-3">
                    <i class="ti ti-report-analytics me-1"></i> Rekap Nilai CD
                </a>
                <a href="{{ route('capstone.admin.expertises.index') }}" class="btn btn-outline-light btn-sm fw-semibold rounded-pill px-3">
                    <i class="ti ti-tags me-1"></i> Bidang Keahlian
                </a>
            </div>
        </div>
    </div>

    <!-- 2. RINGKASAN KPI (4 KARTU BERWARNA) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase mb-1">Total Kelompok</div>
                    <h3 class="fw-bold text-dark mb-0" id="kpi-total">0</h3>
                </div>
                <div class="kpi-icon bg-primary-subtle text-primary"><i class="ti ti-folders"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase mb-1">Draft / Proses</div>
                    <h3 class="fw-bold text-secondary mb-0" id="kpi-draft">0</h3>
                </div>
                <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="ti ti-file-description"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="small fw-semibold text-info-emphasis text-uppercase mb-1">Terjadwal Ujian</div>
                    <h3 class="fw-bold text-info-emphasis mb-0" id="kpi-ujian">0</h3>
                </div>
                <div class="kpi-icon bg-info-subtle text-info-emphasis"><i class="ti ti-calendar-event"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="small fw-semibold text-success text-uppercase mb-1">Selesai / Lulus</div>
                    <h3 class="fw-bold text-success mb-0" id="kpi-selesai">0</h3>
                </div>
                <div class="kpi-icon bg-success-subtle text-success"><i class="ti ti-circle-check"></i></div>
            </div>
        </div>
    </div>

    <!-- 3. PANEL PLOTTING & IMPORT KHUSUS SUPER ADMIN (COLLAPSIBLE) -->
    @if(auth()->user()->hasRole('Super Admin'))
    <div class="modern-card mb-4 overflow-hidden">
        <div class="p-3 px-4 bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="p-2 rounded-3 bg-primary-subtle text-primary d-inline-flex"><i class="ti ti-users-plus fs-5"></i></span>
                <div>
                    <h6 class="fw-bold mb-0">Plotting Cepat & Import Excel</h6>
                    <span class="small text-muted">Tambah kelompok manual atau unggah massal via file Excel/CSV</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('capstone.admin.plotting.export') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
                </a>
                <button class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlottingTools">
                    <i class="ti ti-adjustments-horizontal me-1"></i> Buka Form Plotting
                </button>
            </div>
        </div>

        <div class="collapse border-top bg-light bg-opacity-50" id="collapsePlottingTools">
            <div class="p-4">
                <!-- Import Excel -->
                <div class="mb-4 pb-3 border-bottom">
                    <label class="form-label small fw-bold text-dark">1. Import Massal dari Excel / CSV</label>
                    <form id="formImportPlotting" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm bg-white" style="max-width: 360px;" accept=".xlsx,.xls,.csv" required>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                            <i class="ti ti-upload me-1"></i> Import File
                        </button>
                    </form>
                    <div class="form-text small text-muted mt-1">
                        Kolom: <code>group_id (opsional), leader_id, expertise_id, supervisor_id, examiner_id, project_location</code>. Baris dengan <code>group_id</code> akan memperbarui data.
                    </div>
                </div>

                <!-- Plotting Manual -->
                <div>
                    <label class="form-label small fw-bold text-dark">2. Tambah Plotting Kelompok Manual</label>
                    <form id="formManualPlotting" class="row g-2">
                        @csrf
                        <div class="col-md-2">
                            <input class="form-control form-control-sm bg-white" type="number" name="leader_id" placeholder="ID Mahasiswa Ketua" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm bg-white" name="expertise_id" required>
                                <option value="">-- Bidang Keahlian --</option>
                                @foreach($expertises as $expertise)
                                    <option value="{{ $expertise->id }}">{{ $expertise->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm bg-white" name="supervisor_id">
                                <option value="">-- Pembimbing --</option>
                                @foreach($dosens as $dosen)
                                    <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm bg-white" name="examiner_id">
                                <option value="">-- Penguji --</option>
                                @foreach($dosens as $dosen)
                                    <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input class="form-control form-control-sm bg-white" name="project_location" placeholder="Lokasi / Instansi">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 4. SECTION GRAFIK & FILTER -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="modern-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold mb-0">Distribusi Status Kelompok</h6>
                        <span class="small text-muted">Proporsi tahapan seluruh kelompok Capstone</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Live Data</span>
                </div>
                <div style="height: 240px; position: relative;">
                    <canvas id="capstoneStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="modern-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="ti ti-filter text-primary me-1"></i>Filter Pencarian</h6>
                        <button type="button" id="btnResetFilter" class="btn btn-link btn-sm text-decoration-none text-muted p-0">Reset</button>
                    </div>
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Bidang Keahlian</label>
                            <select name="expertise_id" class="form-select form-select-sm">
                                <option value="">-- Semua Keahlian --</option>
                                @foreach($expertises ?? [] as $exp)
                                <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Status Akhir</label>
                            <select name="final_status" class="form-select form-select-sm">
                                <option value="">-- Semua Status --</option>
                                <option value="proses">Dalam Proses</option>
                                <option value="selesai">Selesai / Lulus</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-secondary mb-1">Kata Kunci</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="ti ti-search"></i></span>
                                <input type="text" name="keyword" class="form-control" placeholder="Cari judul proyek atau nama ketua...">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. TABEL DATA UTAMA -->
    <div class="modern-card overflow-hidden">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Daftar Kelompok & Progress Capstone</h5>
                <span class="small text-muted">Klik menu aksi di sebelah kanan tiap baris untuk plotting, jadwal, ACC, atau cetak berkas.</span>
            </div>
            <button type="button" onclick="loadTableData()" class="btn btn-light btn-sm border rounded-pill px-3">
                <i class="ti ti-refresh me-1"></i> Muat Ulang
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 240px;">Judul & Ketua Kelompok</th>
                        <th style="min-width: 170px;">Bidang & Lokasi</th>
                        <th style="min-width: 210px;">Dewan Dosen</th>
                        <th style="min-width: 200px;">KPI & Jadwal Sidang</th>
                        <th style="min-width: 180px;">Status ACC</th>
                        <th class="text-end" style="min-width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBodyGroups">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data kelompok...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let capstoneChart = null;
    const canApproveKps = @json(auth()->user()->hasAnyRole(['Super Admin', 'KPS', 'Kaprodi']));
    const canPrintSk = @json(auth()->user()->hasRole('Super Admin'));
    const capstoneDosen = @json($dosens->map(fn ($dosen) => ['id' => $dosen->id_dosen, 'name' => $dosen->nama_dosen])->values());

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('capstoneStatusChart').getContext('2d');
        capstoneChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Draft / Submitted', 'Ujian Dijadwalkan', 'Proses Sidang', 'Selesai'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#94a3b8', '#0ea5e9', '#f59e0b', '#10b981'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { usePointStyle: true, padding: 16, font: { size: 12 } }
                    }
                }
            }
        });

        loadTableData();

        // Import Plotting Excel
        $('#formImportPlotting').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('capstone.admin.plotting.import') }}",
                type: 'POST',
                data: new FormData(this),
                contentType: false,
                processData: false,
                success: function(res) {
                    Swal.fire('Berhasil', res.message, 'success');
                    $('#formImportPlotting')[0].reset();
                    loadTableData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Import plotting gagal.', 'error');
                }
            });
        });

        // Tambah Plotting Manual (Sudah dipisah dari dalam submit import)
        $('#formManualPlotting').on('submit', function(e) {
            e.preventDefault();
            $.post("{{ route('capstone.admin.groups.store') }}", $(this).serialize())
                .done(function(res) {
                    Swal.fire('Berhasil', res.message, 'success');
                    $('#formManualPlotting')[0].reset();
                    loadTableData();
                })
                .fail(function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Plotting manual gagal.', 'error');
                });
        });

        // Filter Live & Reset
        $('#filterForm select').on('change', loadTableData);
        $('#btnResetFilter').on('click', function() {
            $('#filterForm')[0].reset();
            loadTableData();
        });

        let searchTimer;
        $('#filterForm input[name="keyword"]').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadTableData, 400);
        });
    });

    function loadTableData() {
        let expertiseId = $('select[name="expertise_id"]').val();
        let finalStatus = $('select[name="final_status"]').val();
        let keyword = $('input[name="keyword"]').val();

        $.ajax({
            url: "{{ route('capstone.admin.index') }}",
            type: 'GET',
            data: { expertise_id: expertiseId, final_status: finalStatus, keyword: keyword },
            success: function(response) {
                let rows = '';
                let countDraft = 0;
                let countUjian = 0;
                let countSidang = 0;
                let countSelesai = 0;
                let totalGroups = response.data ? response.data.length : 0;

                if (totalGroups > 0) {
                    response.data.forEach(function(group) {
                        if (group.final_status === 'selesai') {
                            countSelesai++;
                        } else if (group.exam_room) {
                            countUjian++;
                        } else if (group.status_pendaftaran === 'approved') {
                            countSidang++;
                        } else {
                            countDraft++;
                        }

                        // Badge KPI
                        let kpiBadge = group.kpi_approval === 'approved'
                            ? '<span class="badge bg-success-subtle text-success rounded-pill">KPI: Approved</span>'
                            : (group.kpi_approval === 'rejected'
                                ? '<span class="badge bg-danger-subtle text-danger rounded-pill">KPI: Rejected</span>'
                                : '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">KPI: Pending</span>');

                        // Jadwal Info
                        let jadwalHtml = '';
                        if (group.exam_date) {
                            jadwalHtml = `<div class="fw-semibold text-dark mt-1"><i class="ti ti-calendar-check text-success me-1"></i>${escapeHtml(group.exam_date)} · ${escapeHtml(String(group.exam_time || '').slice(0,5))}</div>
                                          <div class="small text-muted"><i class="ti ti-map-pin text-danger me-1"></i>${escapeHtml(group.exam_room || 'Ruang TBD')}</div>`;
                        } else if (group.proposed_exam_date) {
                            jadwalHtml = `<div class="small text-warning-emphasis mt-1"><i class="ti ti-clock me-1"></i>Usulan: ${escapeHtml(group.proposed_exam_date)} (${escapeHtml(String(group.proposed_exam_time || '').slice(0,5))})</div>`;
                        } else {
                            jadwalHtml = `<div class="small text-muted mt-1">Belum ada jadwal</div>`;
                        }

                        // Status ACC Pills
                        const renderAccPill = (label, isAcc, roleText, approverName) => {
                            let badgeClass = isAcc ? 'bg-success-subtle text-success' : 'bg-light text-muted border';
                            let icon = isAcc ? 'ti-circle-check-filled' : 'ti-circle-dashed';
                            let title = isAcc ? `ACC oleh ${roleText || ''} ${approverName || ''}` : 'Belum ACC';
                            return `<span class="badge ${badgeClass} rounded-pill mb-1 d-inline-flex align-items-center gap-1" title="${escapeHtml(title)}">
                                        <i class="ti ${icon}"></i> ${label}
                                    </span>`;
                        };

                        rows += `<tr>
                            <td>
                                <div class="fw-bold text-dark mb-1" style="line-height: 1.35;">${escapeHtml(group.title ?? 'Judul belum ditetapkan')}</div>
                                <div class="small text-muted"><i class="ti ti-user text-primary me-1"></i>${escapeHtml(group.leader?.name ?? '-')}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary rounded-pill mb-1">${escapeHtml(group.expertise?.name ?? 'Belum memilih')}</span>
                                <div class="small text-muted"><i class="ti ti-building me-1"></i>${escapeHtml(group.project_location ?? 'Lokasi belum diatur')}</div>
                            </td>
                            <td>
                                <div class="small mb-1">
                                    <span class="text-muted">Pembimbing:</span><br>
                                    <strong class="text-dark">${group.supervisor?.nama_dosen ? escapeHtml(group.supervisor.nama_dosen) : '<span class="text-danger fw-normal">Belum diplot</span>'}</strong>
                                </div>
                                <div class="small">
                                    <span class="text-muted">Penguji:</span><br>
                                    <strong class="text-dark">${group.examiner?.nama_dosen ? escapeHtml(group.examiner.nama_dosen) : '<span class="text-danger fw-normal">Belum diplot</span>'}</strong>
                                </div>
                            </td>
                            <td>
                                ${kpiBadge}
                                ${jadwalHtml}
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    ${renderAccPill('Koordinator', group.acc_coordinator, group.coordinator_approved_by_role, group.coordinator_approver?.name)}
                                    ${renderAccPill('Pembimbing', group.acc_supervisor, group.supervisor_approved_by_role, group.supervisor_approver?.name)}
                                    ${renderAccPill('KPS', group.acc_kps, group.kps_approved_by_role, group.kps_approver?.name)}
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Kelola
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li><h6 class="dropdown-header small text-uppercase">Plotting & Jadwal</h6></li>
                                        <li>
                                            <button class="dropdown-item btn-admin-plot" data-id="${group.id}" data-supervisor="${group.supervisor_id ?? ''}" data-examiner="${group.examiner_id ?? ''}" data-location="${escapeHtml(group.project_location ?? '')}">
                                                <i class="ti ti-user-check text-primary me-2"></i>Plotting Dosen & Tempat
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item btn-set-room" data-id="${group.id}" data-date="${group.proposed_exam_date ?? ''}" data-time="${group.proposed_exam_time ?? ''}">
                                                <i class="ti ti-calendar-time text-info me-2"></i>Atur Jadwal & Ruangan
                                            </button>
                                        </li>
                                        ${canApproveKps ? `
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header small text-uppercase">Persetujuan</h6></li>
                                        <li>
                                            <button class="dropdown-item btn-kps-approval" data-id="${group.id}" data-approved="${group.acc_kps ? 1 : 0}">
                                                <i class="ti ${group.acc_kps ? 'ti-circle-x text-danger' : 'ti-circle-check text-success'} me-2"></i>${group.acc_kps ? 'Cabut ACC KPS' : 'ACC Final KPS'}
                                            </button>
                                        </li>` : ''}
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header small text-uppercase">Dokumen & Undangan</h6></li>
                                        <li>
                                            <a class="dropdown-item" target="_blank" href="{{ url('capstone/admin/invitation') }}/${group.id}">
                                                <i class="ti ti-file-text text-secondary me-2"></i>Lihat Undangan
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item btn-send-invitation" data-id="${group.id}">
                                                <i class="ti ti-send text-primary me-2"></i>Kirim Undangan
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item btn-print-document" data-id="${group.id}" data-type="berita-acara">
                                                <i class="ti ti-printer text-dark me-2"></i>Cetak Berita Acara
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item btn-print-document" data-id="${group.id}" data-type="kesepakatan">
                                                <i class="ti ti-printer text-dark me-2"></i>Cetak Kesepakatan
                                            </button>
                                        </li>
                                        ${canPrintSk ? `
                                        <li>
                                            <button class="dropdown-item btn-print-document" data-id="${group.id}" data-type="sk">
                                                <i class="ti ti-award text-warning me-2"></i>Cetak SK Pembimbing
                                            </button>
                                        </li>` : ''}
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger btn-delete-group" data-id="${group.id}">
                                                <i class="ti ti-trash me-2"></i>Hapus Kelompok
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="ti ti-folder-off fs-2 d-block mb-2"></i>Belum ada data kelompok Capstone sesuai filter.</td></tr>`;
                }

                $('#tableBodyGroups').html(rows);

                // Update KPI Cards
                $('#kpi-total').text(totalGroups);
                $('#kpi-draft').text(countDraft + countSidang);
                $('#kpi-ujian').text(countUjian);
                $('#kpi-selesai').text(countSelesai);

                // Update Chart
                if (capstoneChart) {
                    capstoneChart.data.datasets[0].data = [countDraft, countUjian, countSidang, countSelesai];
                    capstoneChart.update();
                }
            }
        });
    }

    // Event Listener Plotting (Didefinisikan 1 kali di luar loadTableData)
    $(document).on('click', '.btn-admin-plot', function() {
        const button = $(this);
        const supervisorId = String(button.data('supervisor') ?? '');
        const examinerId = String(button.data('examiner') ?? '');
        const options = capstoneDosen.map(dosen => {
            return `<option value="${escapeHtml(dosen.id)}">${escapeHtml(dosen.name)}</option>`;
        }).join('');

        Swal.fire({
            title: 'Plotting Tempat & Dewan Dosen',
            html: `
                <div class="text-start">
                    <label class="form-label small fw-semibold mb-1">Tempat / Lokasi Proyek</label>
                    <input id="project-location" class="form-control mb-3" maxlength="255" placeholder="Contoh: Dinas PUPR Kota Malang" value="${escapeHtml(button.data('location'))}">
                    
                    <label class="form-label small fw-semibold mb-1">Dosen Pembimbing</label>
                    <select id="plot-supervisor" class="form-select mb-3"><option value="">-- Pilih Pembimbing --</option>${options}</select>
                    
                    <label class="form-label small fw-semibold mb-1">Dosen Penguji</label>
                    <select id="plot-examiner" class="form-select"><option value="">-- Pilih Penguji --</option>${options}</select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan Plotting',
            cancelButtonText: 'Batal',
            didOpen: () => {
                document.getElementById('plot-supervisor').value = supervisorId;
                document.getElementById('plot-examiner').value = examinerId;
            },
            preConfirm: () => ({
                project_location: document.getElementById('project-location').value,
                supervisor_id: document.getElementById('plot-supervisor').value,
                examiner_id: document.getElementById('plot-examiner').value
            })
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('capstone/admin/groups') }}/" + button.data('id') + "/plotting",
                type: 'PUT',
                data: {...result.value, _token: '{{ csrf_token() }}'},
                success: res => {
                    Swal.fire('Berhasil', res.message, 'success');
                    loadTableData();
                },
                error: err => Swal.fire('Gagal', err.responseJSON?.message || 'Plotting gagal disimpan.', 'error')
            });
        });
    });

    // Aksi Admin Set Ruangan Ujian
    $(document).on('click', '.btn-set-room', function() {
        let button = $(this);
        let groupId = button.data('id');
        let proposedDate = button.data('date') || '';
        let proposedTime = String(button.data('time') || '').slice(0, 5);

        Swal.fire({
            title: 'Jadwal & Ruang Sidang',
            html: `
                <div class="text-start">
                    <label class="form-label small fw-semibold mb-1">Tanggal Ujian</label>
                    <input id="exam-date" type="date" class="form-control mb-3" value="${proposedDate}">
                    
                    <label class="form-label small fw-semibold mb-1">Waktu Ujian</label>
                    <input id="exam-time" type="time" class="form-control mb-3" value="${proposedTime}">
                    
                    <label class="form-label small fw-semibold mb-1">Ruangan / Link Sidang</label>
                    <input id="exam-room" type="text" class="form-control" placeholder="Contoh: Ruang Sidang Teknik Lt. 2">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan Jadwal',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const exam_room = document.getElementById('exam-room').value;
                const exam_date = document.getElementById('exam-date').value;
                const exam_time = document.getElementById('exam-time').value;
                if (!exam_room || !exam_date || !exam_time) {
                    Swal.showValidationMessage('Tanggal, waktu, dan ruangan wajib diisi.');
                    return false;
                }
                return {exam_room, exam_date, exam_time};
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.ajax({
                    url: "{{ url('capstone/admin/room') }}/" + groupId,
                    type: 'POST',
                    data: {
                        exam_room: result.value.exam_room,
                        exam_date: result.value.exam_date,
                        exam_time: result.value.exam_time,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire('Berhasil', res.message, 'success');
                        loadTableData();
                    },
                    error: function(err) {
                        Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-kps-approval', function() {
        const groupId = $(this).data('id');
        const approved = !Number($(this).data('approved'));
        $.post("{{ url('capstone/admin/groups') }}/" + groupId + "/kps-approval", {
            approved: approved ? 1 : 0,
            _token: '{{ csrf_token() }}'
        }).done(function(res) {
            Swal.fire('Berhasil', res.message, 'success');
            loadTableData();
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'ACC KPS gagal disimpan.', 'error');
        });
    });

    $(document).on('click', '.btn-send-invitation', function() {
        $.post("{{ url('capstone/admin/invitation') }}/" + $(this).data('id') + "/send", {
            _token: '{{ csrf_token() }}'
        }).done(function(res) {
            Swal.fire('Berhasil', res.message, 'success');
        }).fail(function(err) {
            Swal.fire('Gagal', err.responseJSON?.message || 'Undangan gagal dikirim.', 'error');
        });
    });

    $(document).on('click', '.btn-print-document', function() {
        window.open("{{ url('capstone/admin/print') }}/" + $(this).data('id') + "/" + $(this).data('type'), '_blank', 'noopener');
    });

    $(document).on('click', '.btn-delete-group', function() {
        const groupId = $(this).data('id');
        Swal.fire({
            title: 'Hapus Kelompok Ini?',
            text: 'Data kelompok beserta plottingnya akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('capstone/admin/groups') }}/" + groupId,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(res) {
                    Swal.fire('Terhapus', res.message, 'success');
                    loadTableData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Ajuan gagal dihapus.', 'error');
                }
            });
        });
    });
</script>
@endpush
@endsection