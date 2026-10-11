<!-- MODAL ATUR JADWAL & DOSEN PENGUJI (PARTIAL VIEW) -->
<div class="modal fade" id="modalJadwalPkl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold text-slate-800">Atur Jadwal Seminar & Dosen Penguji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formJadwalPkl" method="POST">
                @csrf
                <input type="hidden" id="jadwal_id" name="id">
                <div class="modal-body p-4">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal & Jam Seminar <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="tanggal_seminar" name="tanggal_seminar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ruangan / Link Seminar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ruangan_seminar" name="ruangan_seminar" placeholder="Cth: Ruang Sidang Lt. 2 / Zoom Meeting" required>
                        </div>
                    </div>

                    <hr class="my-4 text-muted opacity-25">

                    <!-- BAGIAN DINAMIS DOSEN PENGUJI -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">Dosen Penguji Seminar <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="tambahPengujiBtn">
                            <i class="ti ti-plus me-1"></i> Tambah Penguji
                        </button>
                    </div>
                    <small class="text-muted d-block mb-3">Tentukan minimal 1 ketua atau anggota penguji untuk seminar mahasiswa ini.</small>

                    <div id="listPengujiContainer">
                        <!-- Baris penguji akan ditambahkan secara dinamis via JS -->
                    </div>

                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitJadwal" class="btn btn-primary fw-semibold px-4">
                        <span class="btn-text">Simpan Jadwal & Penguji</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template baris penguji tersembunyi untuk duplikasi script -->
<template id="templatePengujiRow">
    <div class="row g-2 align-items-center mb-2 penguji-row">
        <div class="col-md-6">
            <select class="form-select select2-penguji-dynamic" name="penguji[INDEX][dosen_id]" required>
                <option value="">-- Pilih Dosen Penguji --</option>
                @isset($dosens)
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                    @endforeach
                @endisset
            </select>
        </div>
        <div class="col-md-5">
            <select class="form-select" name="penguji[INDEX][peran]" required>
                <option value="Ketua Penguji">Ketua Penguji</option>
                <option value="Anggota Penguji">Anggota Penguji</option>
            </select>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm h-100 w-100 hapus-penguji-btn">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </div>
</template>

@push('scripts')
<script>
    let pengujiIndex = 0;

    function tambahBarisPenguji(dosenId = '', peran = 'Anggota Penguji') {
        let template = $('#templatePengujiRow').html();
        template = template.replace(/INDEX/g, pengujiIndex);
        
        let $newRow = $(template);
        $('#listPengujiContainer').append($newRow);

        // Inisialisasi Select2 pada baris baru
        $newRow.find('.select2-penguji-dynamic').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalJadwalPkl'),
            placeholder: '-- Pilih Dosen Penguji --',
            width: '100%'
        });

        // Set nilai jika ada data dari database
        if (dosenId) {
            $newRow.find('.select2-penguji-dynamic').val(dosenId).trigger('change');
        }
        $newRow.find('select[name*="[peran]"]').val(peran);

        pengujiIndex++;
    }

    $(document).ready(function() {
        // Tombol tambah baris manual
        $('#tambahPengujiBtn').click(function() {
            tambahBarisPenguji();
        });

        // Hapus baris penguji
        $(document).on('click', '.hapus-penguji-btn', function() {
            if ($('.penguji-row').length > 1) {
                $(this).closest('.penguji-row').remove();
            } else {
                Swal.fire('Perhatian', 'Minimal harus ada 1 dosen penguji.', 'warning');
            }
        });

        // 1. Handler Klik Tombol Jadwal
        $(document).on('click', '.btn-jadwal', function() {
            let id = $(this).data('id');
            let editUrl = "{{ url('pkl/admin/pengajuan') }}/" + id + "/edit";

            $.ajax({
                url: editUrl,
                type: 'GET',
                success: function(response) {
                    let data = response.data;
                    
                    $('#jadwal_id').val(data.id);
                    $('#tanggal_seminar').val(data.tanggal_seminar ? data.tanggal_seminar.replace(' ', 'T').slice(0, 16) : '');
                    $('#ruangan_seminar').val(data.ruangan_seminar);

                    // Reset container penguji
                    $('#listPengujiContainer').empty();
                    pengujiIndex = 0;

                    // Jika data penguji sudah ada di database, tampilkan
                    if (data.dosen_penguji && data.dosen_penguji.length > 0) {
                        data.dosen_penguji.forEach(function(p) {
                            tambahBarisPenguji(p.id_dosen, p.pivot.peran);
                        });
                    } else {
                        tambahBarisPenguji(); // Minimal 1 baris kosong default
                    }

                    // Set Action URL endpoint
                    let updateUrl = "{{ url('pkl/admin/set-jadwal-penguji') }}/" + data.id;
                    $('#formJadwalPkl').attr('action', updateUrl);

                    $('#modalJadwalPkl').modal('show');
                },
                error: function() {
                    Swal.fire('Gagal', 'Gagal memuat data jadwal.', 'error');
                }
            });
        });

        // 2. Handler Submit Form Penjadwalan (Full AJAX)
        $('#formJadwalPkl').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let actionUrl = form.attr('action');
            let formData = new FormData(this);
            let btnSubmit = $('#btnSubmitJadwal');

            btnSubmit.prop('disabled', true);
            btnSubmit.find('.btn-text').text('Menyimpan...');
            btnSubmit.find('.spinner-border').removeClass('d-none');

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#modalJadwalPkl').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Jadwal seminar berhasil ditetapkan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Refresh untuk memperbarui status dan kolom jadwal di tabel
                    });
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan saat menyimpan jadwal.';
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        errorMsg = Object.values(errors).map(err => err[0]).join('<br>');
                    }
                    Swal.fire({ icon: 'error', title: 'Gagal', html: errorMsg });
                },
                complete: function() {
                    btnSubmit.prop('disabled', false);
                    btnSubmit.find('.btn-text').text('Simpan Jadwal & Penguji');
                    btnSubmit.find('.spinner-border').addClass('d-none');
                }
            });
        });
    });
</script>
@endpush