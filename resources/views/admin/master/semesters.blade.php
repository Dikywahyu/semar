@extends('layouts.app')

@section('title', 'Master Semester')

@section('content')
<div class="container-fluid py-4">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3"><div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-2">Master Semester Akademik</h4>
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Master Data</li><li class="breadcrumb-item" aria-current="page">Semester Akademik</li>
                </ol></nav>
            </div>
            <div class="col-3 text-center"><img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4"></div>
        </div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h5 class="fw-semibold mb-1">Semester Akademik</h5><p class="text-muted mb-0">Semester aktif otomatis digunakan untuk pengajuan PKL baru dan komponen penilaian.</p></div>
        <button type="button" class="btn btn-primary" id="btn-add-semester" data-bs-toggle="modal" data-bs-target="#modalSemester"><i class="ti ti-plus me-1"></i> Tambah Semester</button>
    </div></div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Daftar Semester</h5></div><div class="card-body px-4">
        <div class="table-responsive"><table id="table-semesters" class="table table-hover align-middle w-100">
            <thead class="table-light"><tr><th>Tahun Akademik</th><th>Periode</th><th>Kode</th><th>Pengajuan PKL</th><th>Komponen Nilai</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="modalSemester" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">
    <form id="formSemester" action="{{ route('master.semesters.store') }}" method="POST" class="modal-content border-0 shadow-lg">
        @csrf<input type="hidden" name="_method" id="semester-method" value="POST"><input type="hidden" name="aktif" value="0">
        <div class="modal-header bg-primary text-white"><div><h5 class="modal-title text-white" id="modalSemesterTitle">Tambah Semester</h5><small class="text-white-50">Tentukan tahun akademik dan periodenya.</small></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <div class="mb-3"><label class="form-label">Tahun Akademik</label><input class="form-control" name="tahun_akademik" placeholder="2026/2027" pattern="\d{4}/\d{4}" required><div class="form-text">Format tahun harus berurutan.</div></div>
            <div class="mb-3"><label class="form-label">Periode</label><select class="form-select" name="periode" required><option value="">Pilih periode</option><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="semester-aktif"><label class="form-check-label" for="semester-aktif">Jadikan semester aktif</label><div class="form-text">Mengaktifkan semester ini akan menonaktifkan semester aktif sebelumnya.</div></div>
        </div>
        <div class="modal-footer bg-light"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="btn-save-semester">Simpan</button></div>
    </form>
</div></div>

@push('scripts')
<script>
$(function () {
    const baseUrl = @json(url('admin/master/semesters'));
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSemester'));
    const table = $('#table-semesters').DataTable({
        processing: true, serverSide: true, pageLength: 10, ordering: false, ajax: baseUrl,
        language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ semester', infoEmpty: 'Belum ada semester', zeroRecords: 'Semester tidak ditemukan', processing: 'Memuat data...', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } },
        columns: [
            { data: 'tahun_akademik', render: value => `<span class="fw-semibold">${safe(value)}</span>` },
            { data: 'periode', render: value => `<span class="badge bg-primary-subtle text-primary">${safe(value).toUpperCase()}</span>` },
            { data: 'kode_semester', render: safe },
            { data: 'pengajuan_pkl_count' }, { data: 'komponen_penilaian_pkl_count' },
            { data: 'aktif', render: value => value ? '<span class="badge bg-success-subtle text-success rounded-pill px-3">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">Nonaktif</span>' },
            { data: null, orderable: false, searchable: false, className: 'text-center text-nowrap', render: () => '<button type="button" class="btn btn-sm btn-light-primary btn-edit-semester me-1" title="Edit"><i class="ti ti-edit"></i></button><button type="button" class="btn btn-sm btn-light-danger btn-delete-semester" title="Hapus"><i class="ti ti-trash"></i></button>' }
        ]
    });
    function safe(value) { return value === null || value === undefined || value === '' ? '—' : $('<span>').text(String(value)).html(); }
    function fail(xhr) { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Terjadi kesalahan.') }); }
    function resetForm() { $('#formSemester')[0].reset(); $('#formSemester').attr('action', baseUrl); $('#semester-method').val('POST'); $('#modalSemesterTitle').text('Tambah Semester'); $('#semester-aktif').prop('checked', false); }
    $('#btn-add-semester').on('click', resetForm);
    $('#table-semesters').on('click', '.btn-edit-semester', function () {
        const row = table.row($(this).closest('tr')).data(); if (!row) return;
        resetForm(); $('#modalSemesterTitle').text('Edit Semester'); $('#formSemester').attr('action', `${baseUrl}/${row.id}`); $('#semester-method').val('PUT');
        Object.entries(row).forEach(([key, value]) => $(`#formSemester [name="${key}"]`).val(value ?? ''));
        $('#formSemester input[type="hidden"][name="aktif"]').val(0); $('#semester-aktif').val(1).prop('checked', Boolean(row.aktif)); modal.show();
    });
    $('#formSemester').on('submit', function (event) {
        event.preventDefault(); const button = $('#btn-save-semester').prop('disabled', true).text('Menyimpan...');
        $.ajax({ url: $(this).attr('action'), method: 'POST', headers: { Accept: 'application/json' }, data: $(this).serialize() })
            .done(response => { modal.hide(); table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false }); })
            .fail(fail).always(() => button.prop('disabled', false).text('Simpan'));
    });
    $('#table-semesters').on('click', '.btn-delete-semester', function () {
        const row = table.row($(this).closest('tr')).data(); if (!row) return;
        Swal.fire({ title: 'Hapus semester?', text: `${row.tahun_akademik} ${row.periode}`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: `${baseUrl}/${row.id}`, method: 'POST', headers: { Accept: 'application/json' }, data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') } })
                .done(response => { table.ajax.reload(null, false); Swal.fire('Terhapus', response.message, 'success'); }).fail(fail);
        });
    });
});
</script>
@endpush
@endsection
