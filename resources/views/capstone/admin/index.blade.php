@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background: linear-gradient(135deg, #f6f8fd 0%, #f1f5f9 100%); min-height: 100vh;">

    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-slate-800 mb-1">Dashboard & Monitoring Capstone Design</h3>
            <p class="text-secondary mb-0">Kelola kelompok, plotting dosen, verifikasi KPI, penjadwalan ujian, dan komponen nilai.</p>
        </div>
        <div>
            <a href="{{ route('capstone.admin.komponen.index') }}" class="btn btn-outline-primary fw-semibold me-2">
                <i class="ti ti-adjustments me-1"></i> Master Komponen Nilai
            </a>
        </div>
    </div>

    <!-- 1. SECTION GRAFIK & FILTER -->
    <div class="row g-4 mb-4">
        <!-- Grafik Status Kelompok Capstone -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-slate-800 mb-0">Statistik Status Kelompok Capstone</h5>
                    <span class="badge bg-light text-secondary">Real-time</span>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="capstoneStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filter Data Cepat -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-slate-800 mb-3"><i class="ti ti-filter me-2"></i>Filter Data Capstone</h5>
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keahlian</label>
                        <select name="expertise_id" class="form-select">
                            <option value="">-- Semua Keahlian --</option>
                            @foreach($expertises ?? [] as $exp)
                            <option value="{{ $exp->id }}">{{ $exp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Final</label>
                        <select name="final_status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cari Judul / Ketua</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Judul / Nama Ketua...">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. TABEL DATA UTAMA -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-4" id="tableContainer">
            <h5 class="fw-bold text-slate-800 mb-3">Daftar Kelompok & Progress Capstone Design</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Judul / Ketua</th>
                            <th>Keahlian</th>
                            <th>Dosen Pembimbing / Penguji</th>
                            <th>Status KPI & Ujian</th>
                            <th>Status ACC Multi-Pihak</th>
                            <th>Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyGroups">
                        <!-- Di-load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let capstoneChart = null;

    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('capstoneStatusChart').getContext('2d');
        capstoneChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Draft / Submitted', 'Ujian Dijadwalkan', 'Proses Sidang', 'Selesai'],
                datasets: [{
                    data: [0, 0, 0, 0], // Data dinamis atau default
                    backgroundColor: ['#64748b', '#0891b2', '#f59e0b', '#16a34a'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        loadTableData();

        // Event listener untuk filter live
        $('#filterForm select').on('change', function() {
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
            data: {
                expertise_id: expertiseId,
                final_status: finalStatus,
                keyword: keyword
            },
            success: function(response) {
                let rows = '';
                let countDraft = 0;
                let countUjian = 0;
                let countSidang = 0;
                let countSelesai = 0;

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(group) {
                        // Kategorisasi statistik untuk chart 4 status
                        if (group.final_status === 'selesai') {
                            countSelesai++;
                        } else if (group.exam_room) {
                            countUjian++;
                        } else if (group.status_pendaftaran === 'approved') {
                            countSidang++;
                        } else {
                            countDraft++;
                        }

                        rows += `<tr>
                            <td><strong>${group.title ?? 'Belum ada judul'}</strong><br><small class="text-muted">Ketua: ${group.leader?.name ?? '-'}</small></td>
                            <td>${group.expertise?.name ?? 'Belum memilih'}</td>
                            <td>
                                Pembimbing: ${group.supervisor?.nama_dosen ?? '<span class="text-danger">Belum ada</span>'}<br>
                                Penguji: ${group.examiner?.nama_dosen ?? '<span class="text-danger">Belum ada</span>'}
                            </td>
                            <td>
                                KPI: <span class="badge bg-${group.kpi_approval == 'approved' ? 'success' : 'warning'}">${group.kpi_approval}</span><br>
                                Ruang: ${group.exam_room ?? '-'}
                            </td>
                            <td>
                                Koor: ${group.acc_coordinator ? '✅' : '❌'} | 
                                Pembimbing: ${group.acc_supervisor ? '✅' : '❌'} | 
                                KPS: ${group.acc_kps ? '✅' : '❌'}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary btn-set-room" data-id="${group.id}">Atur Ruangan</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data kelompok capstone.</td></tr>`;
                }

                $('#tableBodyGroups').html(rows);

                // Perbarui data chart secara real-time
                if (capstoneChart) {
                    capstoneChart.data.datasets[0].data = [countDraft, countUjian, countSidang, countSelesai];
                    capstoneChart.update();
                }
            }
        });
    }

    // Aksi Admin Set Ruangan Ujian via AJAX SweetAlert
    $(document).on('click', '.btn-set-room', function() {
        let groupId = $(this).data('id');
        Swal.fire({
            title: 'Tentukan Ruang Ujian',
            input: 'text',
            inputPlaceholder: 'Nama / Nomor Ruangan...',
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.ajax({
                    url: "{{ url('capstone/admin/room') }}/" + groupId,
                    type: 'POST',
                    data: {
                        exam_room: result.value,
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
</script>
@endpush
@endsection