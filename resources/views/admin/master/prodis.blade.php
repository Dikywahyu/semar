@extends('layouts.app')

@section('title', 'Master Program Studi')

@section('content')
<div class="container-fluid py-4">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3"><div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-2">Master Program Studi</h4>
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Master Data</li><li class="breadcrumb-item" aria-current="page">Program Studi</li>
                </ol></nav>
            </div>
            <div class="col-3 text-center"><img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4"></div>
        </div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="fw-semibold mb-1">Program Studi</h5><p class="text-muted mb-0">Kelola program studi yang digunakan sebagai master dosen dan mahasiswa.</p></div>
        <button type="button" class="btn btn-primary" id="btn-add-prodi" data-bs-toggle="modal" data-bs-target="#modalProdi"><i class="ti ti-plus me-1"></i> Tambah Prodi</button>
    </div></div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Daftar Program Studi</h5></div><div class="card-body px-4">
        <div class="table-responsive"><table id="table-prodis" class="table table-hover align-middle w-100">
            <thead class="table-light"><tr><th>Kode</th><th>Program Studi</th><th>Jenjang</th><th>Fakultas</th><th>Dosen</th><th>Mahasiswa</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="modalProdi" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">
    <form id="formProdi" action="{{ route('master.prodis.store') }}" method="POST" class="modal-content border-0 shadow-lg">
        @csrf<input type="hidden" name="_method" id="prodi-method" value="POST"><input type="hidden" name="aktif" value="0">
        <div class="modal-header bg-primary text-white"><div><h5 class="modal-title text-white" id="modalProdiTitle">Tambah Program Studi</h5><small class="text-white-50">Isi data identitas program studi.</small></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4"><div class="row g-3">
            <div class="col-md-5"><label class="form-label">Kode Prodi</label><input class="form-control" name="kode_prodi" maxlength="30" required></div>
            <div class="col-md-7"><label class="form-label">Jenjang</label><select class="form-select" name="jenjang" required><option value="">Pilih jenjang</option>@foreach(['D1','D2','D3','D4','S1','S2','S3','Profesi','Lainnya'] as $jenjang)<option value="{{ $jenjang }}">{{ $jenjang }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Nama Program Studi</label><input class="form-control" name="nama_prodi" maxlength="255" required></div>
            <div class="col-12"><label class="form-label">Fakultas</label><input class="form-control" name="fakultas" maxlength="255"></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" value="1" checked id="prodi-aktif"><label class="form-check-label" for="prodi-aktif">Program studi aktif</label></div></div>
        </div></div>
        <div class="modal-footer bg-light"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="btn-save-prodi">Simpan</button></div>
    </form>
</div></div>

@push('scripts')
<script>
$(function () {
    const baseUrl = @json(url('admin/master/prodis'));
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProdi'));
    const table = $('#table-prodis').DataTable({
        processing: true, serverSide: true, pageLength: 10, ordering: false, ajax: baseUrl,
        language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ prodi', infoEmpty: 'Belum ada prodi', zeroRecords: 'Prodi tidak ditemukan', processing: 'Memuat data...', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } },
        columns: [
            { data: 'kode_prodi', render: safe },
            { data: 'nama_prodi', render: value => `<span class="fw-semibold">${safe(value)}</span>` },
            { data: 'jenjang', render: value => `<span class="badge bg-primary-subtle text-primary">${safe(value)}</span>` },
            { data: 'fakultas', defaultContent: '—', render: safe },
            { data: 'dosens_count' }, { data: 'mahasiswas_count' },
            { data: 'aktif', render: value => value ? '<span class="badge bg-success-subtle text-success rounded-pill px-3">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">Nonaktif</span>' },
            { data: null, orderable: false, searchable: false, className: 'text-center text-nowrap', render: (data, type, row) => `<button type="button" class="btn btn-sm btn-light-primary btn-edit-prodi me-1" title="Edit"><i class="ti ti-edit"></i></button><button type="button" class="btn btn-sm btn-light-danger btn-delete-prodi" title="Hapus"><i class="ti ti-trash"></i></button>` }
        ]
    });
    function safe(value) { return value === null || value === undefined || value === '' ? '—' : $('<span>').text(String(value)).html(); }
    function fail(xhr) { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Terjadi kesalahan.') }); }
    function resetForm() { $('#formProdi')[0].reset(); $('#formProdi').attr('action', baseUrl); $('#prodi-method').val('POST'); $('#modalProdiTitle').text('Tambah Program Studi'); $('#prodi-aktif').prop('checked', true); }
    $('#btn-add-prodi').on('click', resetForm);
    $('#table-prodis').on('click', '.btn-edit-prodi', function () {
        const row = table.row($(this).closest('tr')).data(); if (!row) return;
        resetForm(); $('#modalProdiTitle').text('Edit Program Studi'); $('#formProdi').attr('action', `${baseUrl}/${row.id}`); $('#prodi-method').val('PUT');
        Object.entries(row).forEach(([key, value]) => $(`#formProdi [name="${key}"]`).val(value ?? ''));
        $('#formProdi input[type="hidden"][name="aktif"]').val(0); $('#prodi-aktif').val(1).prop('checked', Boolean(row.aktif)); modal.show();
    });
    $('#formProdi').on('submit', function (event) {
        event.preventDefault(); const button = $('#btn-save-prodi').prop('disabled', true).text('Menyimpan...');
        $.ajax({ url: $(this).attr('action'), method: 'POST', headers: { Accept: 'application/json' }, data: $(this).serialize() })
            .done(response => { modal.hide(); table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false }); })
            .fail(fail).always(() => button.prop('disabled', false).text('Simpan'));
    });
    $('#table-prodis').on('click', '.btn-delete-prodi', function () {
        const row = table.row($(this).closest('tr')).data(); if (!row) return;
        Swal.fire({ title: 'Hapus program studi?', text: row.nama_prodi, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: `${baseUrl}/${row.id}`, method: 'POST', headers: { Accept: 'application/json' }, data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') } })
                .done(response => { table.ajax.reload(null, false); Swal.fire('Terhapus', response.message, 'success'); }).fail(fail);
        });
    });
});
</script>
@endpush
@endsection
