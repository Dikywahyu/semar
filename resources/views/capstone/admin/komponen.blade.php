@extends('layouts.app')

@push('styles')
<!-- DataTables CSS Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* Memperbaiki styling select length DataTables agar proporsional dan tidak bertumpuk */
    .dataTables_length select.form-select {
        display: inline-block;
        width: 75px !important;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        padding-left: 0.75rem;
        padding-right: 2rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.5;
        background-position: right 0.5rem center;
        background-size: 14px 12px;
    }

    .dataTables_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #5a6a85;
    }

    .dataTables_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        border: 1px solid #dfe5ef;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 py-md-4" style="  min-height: 100vh;">

    <!-- Breadcrumb Banner -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-2">Master Komponen Nilai Capstone</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="http://127.0.0.1:8000">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('capstone.admin.index') }}">Capstone</a></li>
                            <li class="breadcrumb-item" aria-current="page">Master Komponen Nilai</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center"><img src="http://127.0.0.1:8000/dist/assets/images/breadcrumb/ChatBc.png" alt="" class="img-fluid mb-n4"></div>
            </div>
        </div>
    </div>

    <!-- Header Page / Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-secondary small mb-0">Kelola komponen dan bobot persentase nilai yang dipisah antara Pembimbing dan Penguji.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="{{ route('capstone.admin.index') }}" class="btn btn-outline-secondary fw-semibold btn-sm btn-md-normal">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm btn-sm btn-md-normal" data-bs-toggle="modal" data-bs-target="#modalKomponen" id="btnTambahKomponen">
                <i class="ti ti-plus me-1"></i> Tambah Komponen
            </button>
        </div>
    </div>

    <!-- TABEL 1: KOMPONEN NILAI PEMBIMBING -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-light py-3 px-3 px-md-4">
            <h5 class="fw-bold text-slate-800 mb-0 fs-6 fs-md-5"><i class="ti ti-user-check me-2 text-info"></i> Komponen Nilai Dosen Pembimbing</h5>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table id="tablePembimbing" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">No</th>
                            <th class="py-3">Nama Komponen</th>
                            <th class="py-3">Bobot (%)</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyPembimbing">
                        <!-- Di-load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 2: KOMPONEN NILAI PENGUJI -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-light py-3 px-3 px-md-4">
            <h5 class="fw-bold text-slate-800 mb-0 fs-6 fs-md-5"><i class="ti ti-award me-2 text-primary"></i> Komponen Nilai Dosen Penguji</h5>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table id="tablePenguji" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">No</th>
                            <th class="py-3">Nama Komponen</th>
                            <th class="py-3">Bobot (%)</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBodyPenguji">
                        <!-- Di-load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Komponen -->
<div class="modal fade" id="modalKomponen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Komponen Nilai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="formKomponen">
                @csrf
                <input type="hidden" id="komponenId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Komponen</label>
                        <input type="text" name="name" id="inputName" class="form-control" required placeholder="Contoh: Presentasi, Produk, Laporan...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Penilai</label>
                        <select name="category" id="inputCategory" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="pembimbing">Pembimbing</option>
                            <option value="penguji">Penguji</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bobot Persentase (%)</label>
                        <input type="number" step="0.01" name="percentage" id="inputPercentage" class="form-control" required placeholder="Contoh: 25.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan Komponen</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<!-- DataTables JS & Bootstrap 5 JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let dataTablePembimbing = null;
    let dataTablePenguji = null;

    document.addEventListener("DOMContentLoaded", function() {
        // Inisialisasi awal DataTables dengan konfigurasi class form-select Bootstrap 5
        dataTablePembimbing = $('#tablePembimbing').DataTable({
            responsive: true,
            language: {
                lengthMenu: "Show _MENU_ entries",
                search: "Search:",
                paginate: {
                    previous: "Prev",
                    next: "Next"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
        dataTablePenguji = $('#tablePenguji').DataTable({
            responsive: true,
            language: {
                lengthMenu: "Show _MENU_ entries",
                search: "Search:",
                paginate: {
                    previous: "Prev",
                    next: "Next"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });

        // Tambahkan kelas form-select Bootstrap secara dinamis ke elemen length DataTables
        $('.dataTables_length select').addClass('form-select form-select-sm');

        loadKomponenData();

        // Reset form saat modal dibuka untuk tambah baru
        $('#modalKomponen').on('show.bs.modal', function(event) {
            let button = $(event.relatedTarget);
            if (button.attr('id') === 'btnTambahKomponen') {
                $('#modalTitle').text('Tambah Komponen Nilai');
                $('#formKomponen')[0].reset();
                $('#komponenId').val('');
            }
        });

        // Submit form tambah / edit via AJAX
        $('#formKomponen').on('submit', function(e) {
            e.preventDefault();
            let id = $('#komponenId').val();
            let url = id ? "{{ url('capstone/admin/komponen') }}/" + id : "{{ route('capstone.admin.komponen.store') }}";
            let type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: type,
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalKomponen').modal('hide');
                    Swal.fire('Berhasil!', res.message, 'success');
                    loadKomponenData();
                },
                error: function(err) {
                    Swal.fire('Gagal', err.responseJSON?.message || 'Terjadi kesalahan validasi.', 'error');
                }
            });
        });
    });

    function loadKomponenData() {
        $.ajax({
            url: "{{ route('capstone.admin.komponen.index') }}",
            type: 'GET',
            success: function(response) {
                // Hancurkan instance DataTables lama sebelum mengisi ulang data DOM
                if ($.fn.DataTable.isDataTable('#tablePembimbing')) {
                    $('#tablePembimbing').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#tablePenguji')) {
                    $('#tablePenguji').DataTable().destroy();
                }

                let rowsPembimbing = '';
                let rowsPenguji = '';
                let noPembimbing = 1;
                let noPenguji = 1;

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        let actionButtons = `
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning btn-edit mb-1 mb-sm-0" data-id="${item.id}" data-name="${item.name}" data-category="${item.category}" data-percentage="${item.percentage}"><i class="ti ti-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${item.id}"><i class="ti ti-trash"></i> Hapus</button>
                            </td>
                        `;

                        if (item.category === 'pembimbing') {
                            rowsPembimbing += `<tr>
                                <td>${noPembimbing++}</td>
                                <td><strong>${item.name}</strong></td>
                                <td>${item.percentage}%</td>
                                ${actionButtons}
                            </tr>`;
                        } else if (item.category === 'penguji') {
                            rowsPenguji += `<tr>
                                <td>${noPenguji++}</td>
                                <td><strong>${item.name}</strong></td>
                                <td>${item.percentage}%</td>
                                ${actionButtons}
                            </tr>`;
                        }
                    });
                }

                $('#tableBodyPembimbing').html(rowsPembimbing);
                $('#tableBodyPenguji').html(rowsPenguji);

                // Inisialisasi ulang DataTables setelah DOM diperbarui
                dataTablePembimbing = $('#tablePembimbing').DataTable({
                    responsive: true
                });
                dataTablePenguji = $('#tablePenguji').DataTable({
                    responsive: true
                });

                // Pastikan kelas form-select diterapkan ulang setelah re-inisialisasi
                $('.dataTables_length select').addClass('form-select form-select-sm');
            }
        });
    }

    // Klik Tombol Edit
    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let category = $(this).data('category');
        let percentage = $(this).data('percentage');

        $('#modalTitle').text('Edit Komponen Nilai');
        $('#komponenId').val(id);
        $('#inputName').val(name);
        $('#inputCategory').val(category);
        $('#inputPercentage').val(percentage);

        $('#modalKomponen').modal('show');
    });

    // Klik Tombol Hapus
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Hapus komponen ini?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('capstone/admin/komponen') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message, 'success');
                        loadKomponenData();
                    },
                    error: function(err) {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus data.', 'error');
                    }
                });
            }
        });
    });
</script>
@endpush
@endsection