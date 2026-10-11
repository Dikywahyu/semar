@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Master Tipe Kegiatan</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('apps.activity-types.index') }}">Apps</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Tipe Kegiatan</li>
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
                        <iconify-icon icon="solar:category-line-duotone" class="me-2 text-primary fs-6"></iconify-icon>
                        Daftar Tipe Kegiatan
                    </h4>
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddType">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="me-1 fs-5"></iconify-icon>
                        Tambah Tipe Kegiatan
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-activity-types" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Tipe Kegiatan</th>
                                    <th>Deskripsi</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH TIPE KEGIATAN --}}
{{-- MODAL TAMBAH TIPE KEGIATAN --}}
<div class="modal fade" id="modalAddType" tabindex="-1" aria-hidden="true">
    {{-- Di sini diset max-width 800px secara paksa agar menembus CSS tema --}}
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px !important; width: 100%;">
        <form id="formAddType" action="{{ route('apps.activity-types.store') }}" method="POST" class="w-100">
            @csrf
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">Tambah Tipe Kegiatan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Tipe Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Rapat Internal, Matrikulasi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Deskripsi singkat kegiatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveType">Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT TIPE KEGIATAN --}}
<div class="modal fade" id="modalEditType" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px !important; width: 100%;">
        <form id="formEditType" method="POST" class="w-100">
            @csrf 
            @method('PUT')
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title d-flex align-items-center text-white">
                        <iconify-icon icon="solar:pen-new-square-line-duotone" class="me-2"></iconify-icon>
                        Edit Tipe Kegiatan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Tipe Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" id="btnUpdateType">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // 1. Init DataTables
        var table = $('#table-activity-types').DataTable({
            processing: true,
            serverSide: false,
            ajax: "{{ route('apps.activity-types.index') }}",
            columns: [
                {
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name',
                    render: (data) => `<span class="fw-bold text-dark fs-3">${data}</span>`
                },
                {
                    data: 'description',
                    render: (data) => `<span class="text-muted">${data || '-'}</span>`
                },
                {
                    data: 'id',
                    className: 'text-center',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit-type shadow-sm" data-id="${data}" title="Edit Tipe">
                                    <iconify-icon icon="solar:pen-new-square-line-duotone"></iconify-icon>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-type shadow-sm" data-id="${data}" title="Hapus Tipe">
                                    <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        // 2. Tambah Data (AJAX)
        $('#formAddType').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveType');
            btn.prop('disabled', true).html('Menyimpan...');

            $.post($(this).attr('action'), $(this).serialize(), function(res) {
                $('#modalAddType').modal('hide');
                $('#formAddType')[0].reset();
                table.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message || 'Tipe kegiatan berhasil disimpan',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).fail(xhr => {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }).always(() => btn.prop('disabled', false).html('Simpan Data'));
        });

        // 3. Edit Data - Fetch Data
        $(document).on('click', '.btn-edit-type', function() {
            let id = $(this).data('id');
            let btn = $(this);
            btn.prop('disabled', true);

            let urlEdit = "{{ route('apps.activity-types.show', ':id') }}".replace(':id', id);
            let urlUpdate = "{{ route('apps.activity-types.update', ':id') }}".replace(':id', id);

            $.get(urlEdit, function(res) {
                let data = res.data;
                $('#edit-name').val(data.name);
                $('#edit-description').val(data.description);
                $('#formEditType').attr('action', urlUpdate);

                $('#modalEditType').modal('show');
            }).fail(xhr => {
                Swal.fire('Gagal', 'Tidak dapat mengambil data', 'error');
            }).always(() => btn.prop('disabled', false));
        });

        // 4. Update Data (AJAX)
        $('#formEditType').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnUpdateType');
            btn.prop('disabled', true).html('Updating...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalEditType').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Tipe kegiatan berhasil diperbarui',
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

        // 5. Delete Data (AJAX)
        $(document).on('click', '.btn-delete-type', function() {
            let id = $(this).data('id');
            let urlDelete = "{{ route('apps.activity-types.destroy', ':id') }}".replace(':id', id);

            Swal.fire({
                title: 'Hapus Tipe Kegiatan?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(urlDelete, {
                        _method: 'DELETE',
                        _token: "{{ csrf_token() }}"
                    }, function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Dihapus!', res.message || 'Data berhasil dihapus', 'success');
                    }).fail(xhr => {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus', 'error');
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection