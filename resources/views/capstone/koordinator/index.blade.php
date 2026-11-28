@extends('layouts.app')

@push('styles')
<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.875rem;
        min-height: 34px;
    }

    /* Memastikan dropdown teks berwarna gelap jika terjadi bentrok tema */
    .select2-dropdown,
    .select2-results__option {
        color: #212529 !important;
        background-color: #ffffff !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-3 px-md-4">
    <div class="mb-4">
        <h3 class="fw-bold text-slate-800 mb-1">Panel Koordinator Keahlian</h3>
        <p class="text-secondary mb-0">Menu KPI Setujui untuk Ujian, ACC kelulusan Capstone, dan monitoring berkas sesuai bidang keahlian.</p>
    </div>

    <!-- Grafik Beban Bimbingan -->
    @if(isset($chartLabels) && count($chartLabels) > 0)
    <div class="row mb-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-3"><i class="ti ti-chart-bar text-primary me-2"></i>Statistik Mahasiswa Bimbingan per Dosen</h6>
                <canvas id="bimbinganChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold mb-3"><i class="ti ti-users text-primary me-2"></i>Daftar Kelompok di Keahlian Anda</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th width="25%">Kelompok / Judul</th>
                        <th width="20%">Mahasiswa</th>
                        <th width="25%">Plotting Dosen</th>
                        <th width="15%">KPI Ujian</th>
                        <th width="15%" class="text-center">Aksi ACC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                    <tr>
                        <!-- Kolom Judul & Keahlian -->
                        <td>
                            <strong class="text-dark d-block mb-1">{{ $group->title }}</strong>
                            <span class="badge bg-info-subtle text-info border border-info-subtle mb-1">
                                {{ optional($group->expertise)->name ?? 'Tanpa Keahlian' }}
                            </span>
                            @if($group->draft_report_file)
                            <a class="d-block small text-primary mb-1" href="{{ route('capstone.file.view', ['path' => $group->draft_report_file]) }}" target="_blank" rel="noopener">
                                <i class="ti ti-file-text me-1"></i>Draft Proposal
                            </a>
                            @else
                            <span class="d-block text-muted small mb-1">Draft belum diunggah</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-0 mt-1 btn-coord-view-files" style="font-size: 0.75rem;" data-files="{{ json_encode([
                                'title' => $group->title,
                                'leader' => $group->leader?->name,
                                'consultation_card' => $group->consultation_card_file,
                                'draft_report' => $group->draft_report_file,
                                'drawings' => $group->drawings_file,
                                'rab' => $group->rab_file,
                                'structural_calculation' => $group->structural_calculation_file,
                                'final_report' => $group->final_report_file,
                                'berita_acara' => $group->berita_acara_file,
                                'agreement' => $group->agreement_file,
                            ]) }}">
                                <i class="ti ti-folders me-1"></i>Semua Berkas
                            </button>
                        </td>

                        <!-- Kolom Mahasiswa -->
                        <td>
                            <div class="small">
                                <span class="fw-bold text-primary">Ketua:</span><br>
                                {{ optional($group->leader)->name ?? '-' }}<br>

                                @if($group->members->count() > 0)
                                <span class="fw-bold text-secondary mt-2 d-block">Anggota:</span>
                                @foreach($group->members as $member)
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span>- {{ optional($member->student)->name ?? '-' }}</span>
                                    @if($member->status == 'accepted')
                                    <span class="badge bg-success ms-1" style="font-size: 0.65rem;">ACC</span>
                                    @elseif($member->status == 'rejected')
                                    <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Tolak</span>
                                    @else
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Pending</span>
                                    @endif
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </td>

                        <!-- Kolom Plotting Dosen & Lokasi Proyek -->
                        <td>
                            <div class="small">
                                <form class="form-project-location mb-3" data-id="{{ $group->id }}">
                                    @csrf
                                    <label class="form-label text-muted d-block mb-1 fw-semibold" style="font-size: 0.75rem;">Tempat / Lokasi Proyek:</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="project_location" value="{{ $group->project_location }}" maxlength="255" class="form-control form-control-sm" placeholder="Isi tempat/lokasi proyek...">
                                        <button type="submit" class="btn btn-primary btn-sm px-2" title="Simpan Lokasi Proyek">
                                            <i class="ti ti-device-floppy"></i>
                                        </button>
                                    </div>
                                </form>
                                <div class="mb-2">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Dosen Pembimbing:</span>
                                    <strong class="text-dark">{{ $group->supervisor?->nama_dosen ?? 'Belum diplot' }}</strong>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Dosen Penguji:</span>
                                    <strong class="text-dark">{{ $group->examiner?->nama_dosen ?? 'Belum diplot' }}</strong>
                                </div>
                                <div class="mt-1 text-muted" style="font-size: 0.7rem;">
                                    <i class="ti ti-info-circle me-1"></i>Plotting dosen oleh Super Admin / Kaprodi
                                </div>
                            </div>
                        </td>

                        <!-- Kolom KPI Ujian -->
                        <td>
                            <form class="form-kpi-approval" data-id="{{ $group->id }}">
                                @csrf
                                <select name="kpi_approval" class="form-select form-select-sm mb-2 {{ $group->kpi_approval == 'approved' ? 'border-success text-success' : ($group->kpi_approval == 'rejected' ? 'border-danger text-danger' : 'border-warning text-warning') }}">
                                    <option value="pending" {{ $group->kpi_approval == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $group->kpi_approval == 'approved' ? 'selected' : '' }}>Disetujui (ACC)</option>
                                    <option value="rejected" {{ $group->kpi_approval == 'rejected' ? 'selected' : '' }}>Belum Layak</option>
                                </select>
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 py-1">
                                    <i class="ti ti-check"></i> Update KPI
                                </button>
                            </form>
                            @if($group->kpi_approval === 'approved')
                            <small class="text-muted">{{ $group->kpi_approved_by_role }}{{ $group->kpiApprover ? ' — '.$group->kpiApprover->name : '' }}</small>
                            @endif
                        </td>

                        <!-- Kolom Aksi Koordinator (ACC Sidang) -->
                        <td class="text-center">
                            <span class="d-block small text-muted mb-2">Status ACC Sidang:</span>
                            <button class="btn btn-sm w-100 btn-toggle-acc {{ $group->acc_coordinator ? 'btn-success' : 'btn-warning' }}" data-id="{{ $group->id }}">
                                @if($group->acc_coordinator)
                                <i class="ti ti-check me-1"></i> Sudah ACC
                                @else
                                <i class="ti ti-clock me-1"></i> Belum ACC
                                @endif
                            </button>
                            @if($group->acc_coordinator)
                            <small class="d-block mt-2">{{ $group->coordinator_approved_by_role }}{{ $group->coordinatorApprover ? ' — '.$group->coordinatorApprover->name : '' }}</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="ti ti-inbox fs-1 d-block mb-2"></i> Belum ada kelompok yang terdaftar di bidang keahlian Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Lihat Berkas Mahasiswa (Koordinator) -->
<div class="modal fade" id="modalCoordStudentFiles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold" id="modalCoordFilesTitle">Berkas Mahasiswa</h5>
                    <div class="small text-muted" id="modalCoordFilesSubtitle">Daftar berkas Capstone Design yang telah diunggah mahasiswa</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Jenis Dokumen</th>
                                <th>Tahapan</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="coordFilesTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {

        // Inisialisasi Select2
        $('.select2-dosen').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // --- Inisialisasi Grafik Chart.js ---
        @if(isset($chartLabels) && count($chartLabels) > 0)
        const ctx = document.getElementById('bimbinganChart').getContext('2d');
        const bimbinganChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                        label: 'Sebagai Pembimbing',
                        data: @json($pembimbingStats),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'As Sebagai Penguji',
                        data: @json($pengujiStats),
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                // --- TAMBAHKAN BAGIAN PLUGINS INI UNTUK MENGUBAH TOOLTIP ---
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + ' mahasiswa';
                                }
                                return label;
                            }
                        }
                    }
                }
                // -----------------------------------------------------------
            }
        });
        @endif

        // 1. Modal Lihat Semua Berkas Mahasiswa
        $(document).on('click', '.btn-coord-view-files', function() {
            const data = $(this).data('files');
            $('#modalCoordFilesTitle').text(data.title || 'Berkas Kelompok');
            $('#modalCoordFilesSubtitle').text('Ketua Kelompok: ' + (data.leader || '-'));

            const fileConfigs = [
                { key: 'consultation_card', label: 'Kartu Konsultasi', stage: 'Proposal / Bimbingan' },
                { key: 'draft_report', label: 'Draft Proposal', stage: 'Proposal / Bimbingan' },
                { key: 'drawings', label: 'Gambar Kerja / Desain', stage: 'Proposal / Pelaksanaan' },
                { key: 'rab', label: 'Rencana Anggaran Biaya (RAB)', stage: 'Proposal / Pelaksanaan' },
                { key: 'structural_calculation', label: 'Perhitungan Struktural', stage: 'Proposal / Pelaksanaan' },
                { key: 'final_report', label: 'Laporan Final Capstone', stage: 'Sidang Akhir' },
                { key: 'berita_acara', label: 'Berita Acara Ujian', stage: 'Sidang Akhir' },
                { key: 'agreement', label: 'Kesepakatan Bersama', stage: 'Sidang Akhir' },
            ];

            const fileViewBaseUrl = "{{ route('capstone.file.view') }}";
            let rows = '';
            fileConfigs.forEach((cfg, idx) => {
                const filePath = data[cfg.key];
                const isUploaded = !!filePath;
                rows += `<tr>
                    <td>${idx + 1}</td>
                    <td><strong class="text-dark">${cfg.label}</strong></td>
                    <td><span class="badge bg-light text-secondary border">${cfg.stage}</span></td>
                    <td>
                        ${isUploaded
                            ? '<span class="badge bg-success-subtle text-success"><i class="ti ti-check me-1"></i>Sudah Diunggah</span>'
                            : '<span class="badge bg-secondary-subtle text-secondary">Belum Ada</span>'}
                    </td>
                    <td class="text-end">
                        ${isUploaded
                            ? `<a href="${fileViewBaseUrl}?path=${encodeURIComponent(filePath)}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1">
                                    <i class="ti ti-eye me-1"></i>Buka File
                               </a>`
                            : '<span class="text-muted small">-</span>'}
                    </td>
                </tr>`;
            });

            $('#coordFilesTableBody').html(rows);
            $('#modalCoordStudentFiles').modal('show');
        });

        // Submit Update Tempat / Lokasi Proyek oleh Koordinator
        $('.form-project-location').on('submit', function(e) {
            e.preventDefault();
            let groupId = $(this).data('id');
            let formData = $(this).serialize();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: "{{ url('capstone/koordinator/project-location') }}/" + groupId,
                type: 'POST',
                data: formData,
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="ti ti-device-floppy"></i>');
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(err) {
                    btn.prop('disabled', false).html('<i class="ti ti-device-floppy"></i>');
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat menyimpan lokasi proyek.', 'error');
                }
            });
        });

        // 2. Submit Update KPI
        $('.form-kpi-approval').on('submit', function(e) {
            e.preventDefault();
            let groupId = $(this).data('id');
            let formData = $(this).serialize();
            let selectElement = $(this).find('select');

            $.ajax({
                url: "{{ url('capstone/koordinator/kpi-approval') }}/" + groupId,
                type: 'POST',
                data: formData,
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Update warna select agar responsif
                    let val = selectElement.val();
                    selectElement.removeClass('border-success text-success border-danger text-danger border-warning text-warning');
                    if (val === 'approved') selectElement.addClass('border-success text-success');
                    else if (val === 'rejected') selectElement.addClass('border-danger text-danger');
                    else selectElement.addClass('border-warning text-warning');
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat update KPI.', 'error');
                }
            });
        });

        // 3. Toggle ACC Koordinator
        $('.btn-toggle-acc').on('click', function() {
            let groupId = $(this).data('id');
            let btn = $(this);

            Swal.fire({
                title: 'Ubah Status ACC?',
                text: "Status ACC Koordinator untuk kelompok ini akan diubah.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ubah!',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('capstone/koordinator/toggle-acc') }}/" + groupId,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // Ubah tampilan tombol secara otomatis tanpa reload
                            if (res.acc_coordinator) {
                                btn.removeClass('btn-warning').addClass('btn-success').html('<i class="ti ti-check me-1"></i> Sudah ACC');
                            } else {
                                btn.removeClass('btn-success').addClass('btn-warning').html('<i class="ti ti-clock me-1"></i> Belum ACC');
                            }
                        },
                        error: function(err) {
                            Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    });
                }
            });
        });

    });
</script>
@endpush
@endsection