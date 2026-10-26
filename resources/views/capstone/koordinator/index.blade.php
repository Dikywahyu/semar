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
        <p class="text-secondary mb-0">Plotting dosen pembimbing/penguji dan validasi menu KPI setuju/belum untuk ujian.</p>
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
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                {{ optional($group->expertise)->name ?? 'Tanpa Keahlian' }}
                            </span>
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

                        <!-- Kolom Plotting Dosen -->
                        <td>
                            <form class="form-plotting-dosen" data-id="{{ $group->id }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-0 text-muted">Pembimbing:</label>
                                    <select name="supervisor_id" class="form-select select2-dosen" style="width: 100%;">
                                        <option value="">-- Pilih Pembimbing --</option>
                                        <!-- Cek apakah kelompok sudah punya keahlian dan keahlian tersebut punya data dosen -->
                                        @if($group->expertise && $group->expertise->dosens)
                                        @foreach($group->expertise->dosens as $dosen)
                                        <option value="{{ $dosen->id_dosen }}" {{ $group->supervisor_id == $dosen->id_dosen ? 'selected' : '' }}>
                                            {{ $dosen->nama_dosen }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="" disabled>Belum ada dosen di keahlian ini</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label small mb-0 text-muted">Penguji:</label>
                                    <select name="examiner_id" class="form-select select2-dosen" style="width: 100%;">
                                        <option value="">-- Pilih Penguji --</option>
                                        @if($group->expertise && $group->expertise->dosens)
                                        @foreach($group->expertise->dosens as $dosen)
                                        <option value="{{ $dosen->id_dosen }}" {{ $group->examiner_id == $dosen->id_dosen ? 'selected' : '' }}>
                                            {{ $dosen->nama_dosen }}
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="" disabled>Belum ada dosen di keahlian ini</option>
                                        @endif
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2 py-1">
                                    <i class="ti ti-device-floppy"></i> Simpan Plotting
                                </button>
                            </form>
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

        // 1. Submit Plotting Dosen
        $('.form-plotting-dosen').on('submit', function(e) {
            e.preventDefault();
            let groupId = $(this).data('id');
            let formData = $(this).serialize();

            $.ajax({
                url: "{{ url('capstone/koordinator/plot-lecturers') }}/" + groupId,
                type: 'POST',
                data: formData,
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Reload halaman untuk update grafik
                    });
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan saat menyimpan plotting.', 'error');
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