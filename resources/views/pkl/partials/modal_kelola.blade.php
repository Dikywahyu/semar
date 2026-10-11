<!-- MODAL KELOLA / EDIT DINAMIS (PARTIAL VIEW) -->
<div class="modal fade" id="modalKelolaPkl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold text-slate-800">Kelola Pengajuan PKL & Administrasi Feeder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formKelolaPkl" method="POST">
                @csrf
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Instansi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_nama_instansi" name="nama_instansi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Instansi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_alamat_instansi" name="alamat_instansi" rows="2" required></textarea>
                    </div>
                    
                    <!-- SELECT2 DOSEN PEMBIMBING -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dosen Pembimbing</label>
                        <select class="form-select select2-dosen" id="edit_dosen_pembimbing_id" name="dosen_pembimbing_id">
                            <option value="">-- Pilih Dosen Pembimbing --</option>
                            @isset($dosens)
                                @foreach($dosens as $dosen)
                                    <option value="{{ $dosen->id_dosen }}">{{ $dosen->nama_dosen }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- INPUT ID SEMESTER (UNTUK PDDikti) -->
                        <!-- SELECT DROPDOWN ID SEMESTER -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Semester Akademik (Feeder)</label>
                            <select class="form-select" id="edit_id_semester" name="semester_master_id" required>
                                <option value="">-- Pilih Semester Akademik --</option>
                                @isset($semesters)
                                    @foreach($semesters as $sem)
                                        @if($sem->a_periode_aktif == 1)
                                            <option value="{{ $sem->id }}" {{ $sem->aktif ? 'selected' : '' }}>
                                                {{ $sem->nama_semester }} {{ $sem->a_periode_aktif == 1 ? '(Aktif)' : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endisset
                            </select>
                            <small class="text-muted">Otomatis memilih periode aktif atau sesuaikan.</small>
                        </div>
                        <!-- INPUT NOMOR SK TUGAS -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor SK Tugas / Izin</label>
                            <input type="text" class="form-control" id="edit_sk_tugas" name="sk_tugas" placeholder="Cth: 043/SK/II/2026">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Tahapan <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="Pengajuan & Verifikasi Admin">Pengajuan & Verifikasi Admin</option>
                            <option value="Pelaksanaan PKL & Bimbingan">Pelaksanaan PKL & Bimbingan</option>
                            <option value="Verifikasi Berkas Admin">Verifikasi Berkas Admin</option>
                            <option value="Dijadwalkan Ujian">Dijadwalkan Ujian</option>
                            <option value="Ujian Seminar & Penilaian">Ujian Seminar & Penilaian</option>
                            <option value="Revisi Laporan">Revisi Laporan</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>
                    
                    <!-- Bagian di dalam Modal Kelola / Detail Admin -->
                    <div class="mb-4 p-3 bg-light rounded-4 border">
                        <h6 class="fw-bold text-slate-800 mb-2"><i class="ti ti-file-analytics me-1"></i> Berkas Revisi & Pengesahan Mahasiswa</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold text-secondary">Laporan Revisi</span>
                                    <div id="admin-view-revisi">
                                        <span class="text-muted small fst-italic">Belum diunggah</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold text-secondary">Lembar Pengesahan</span>
                                    <div id="admin-view-pengesahan">
                                        <span class="text-muted small fst-italic">Belum diunggah</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitKelola" class="btn btn-primary fw-semibold px-4">
                        <span class="btn-text">Simpan Perubahan</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {$('.select2-dosen').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalKelolaPkl'),
            placeholder: '-- Pilih Dosen Pembimbing --', 
        });

        // Handler Klik Tombol Kelola
        $(document).on('click', '.btn-kelola', function() {
            let id = $(this).data('id');
            let editUrl = "{{ url('pkl/admin/pengajuan') }}/" + id + "/edit";

            $.ajax({
                url: editUrl,
                type: 'GET',
                success: function(response) {
                    let data = response.data;
                    
                    // Render link Laporan Revisi jika ada
                    if (data.berkas_laporan_revisi) {
                        let urlRevisi = "{{ url('pkl/view-file') }}/" + data.berkas_laporan_revisi;
                        $('#admin-view-revisi').html(`
                            <a href="${urlRevisi}" target="_blank" class="btn btn-sm btn-outline-primary px-2 py-0">
                                <i class="ti ti-eye me-1"></i> Lihat
                            </a>
                        `);
                    } else {
                        $('#admin-view-revisi').html('<span class="text-muted small fst-italic">Belum diunggah</span>');
                    }
            
                    // Render link Lembar Pengesahan jika ada
                    if (data.berkas_lembar_pengesahan) {
                        let urlPengesahan = "{{ url('pkl/view-file') }}/" + data.berkas_lembar_pengesahan;
                        $('#admin-view-pengesahan').html(`
                            <a href="${urlPengesahan}" target="_blank" class="btn btn-sm btn-outline-success px-2 py-0">
                                <i class="ti ti-eye me-1"></i> Lihat
                            </a>
                        `);
                    } else {
                        $('#admin-view-pengesahan').html('<span class="text-muted small fst-italic">Belum diunggah</span>');
                    }
                    
                    // Masukkan nilai data ke form modal, termasuk field baru feeder
                    $('#edit_id').val(data.id);
                    $('#edit_nama_instansi').val(data.nama_instansi);
                    $('#edit_alamat_instansi').val(data.alamat_instansi);
                    $('#edit_id_semester').val(data.semester_master_id);
                    $('#edit_sk_tugas').val(data.sk_tugas);
                    $('#edit_status').val(data.status);

                    $('#edit_dosen_pembimbing_id').val(data.dosen_pembimbing_id).trigger('change');

                    let updateUrl = "{{ url('pkl/admin/pengajuan') }}/" + data.id + "/update";
                    $('#formKelolaPkl').attr('action', updateUrl);

                    $('#modalKelolaPkl').modal('show');
                },
                error: function() {
                    Swal.fire('Gagal', 'Gagal memuat data pengajuan.', 'error');
                }
            });
        });

        // Handler Submit Form Kelola / Update PKL 
        $('#formKelolaPkl').on('submit', function(e) {
            e.preventDefault();
        
            let form = $(this);
            let actionUrl = form.attr('action');
            let formData = new FormData(this);
            let btnSubmit = $('#btnSubmitKelola');
        
            btnSubmit.prop('disabled', true);
            btnSubmit.find('.btn-text').text('Memperbarui...');
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
                    $('#modalKelolaPkl').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Data berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    });
        
                    // 1. Update Baris Tabel
                    let id = $('#edit_id').val();
                    let row = $(`button[data-id="${id}"]`).closest('tr');
                    
                    row.find('td:nth-child(3) .fw-semibold').text(response.data.nama_instansi);
                    row.find('td:nth-child(3) small').text(response.data.alamat_instansi);
                    row.find('td:nth-child(5) .badge').text(response.data.status);
        
                    let dosenCol = row.find('td:nth-child(4)');
                    if (response.data.dosen_pembimbing) {
                        let namaDosen = response.data.dosen_pembimbing.nama_dosen ?? response.data.dosen_pembimbing.name;
                        dosenCol.html(`<span class="badge bg-success-subtle text-success">${namaDosen}</span>`);
                    } else {
                        dosenCol.html(`<span class="badge bg-warning-subtle text-warning">Belum Ditentukan</span>`);
                    }
        
                    // 2. Update Widget Ringkasan & Chart.js (7 STATUS LENGKAP)
                    if (response.stats) {
                        let s = response.stats;
        
                        // Update Widget Kartu
                        $('#stat-pengajuan').text(s.pengajuan ?? 0);
                        $('#stat-pelaksanaan').text(s.pelaksanaan ?? 0);
                        $('#stat-verifikasi').text(s.verifikasi ?? 0);
                        $('#stat-ujian').text(s.ujian ?? 0);
                        $('#stat-seminar').text(s.seminar ?? 0);
                        $('#stat-revisi').text(s.revisi ?? 0);
                        $('#stat-selesai').text(s.selesai ?? 0);
        
                        // Update Chart.js dengan 7 data berurutan
                        if (typeof pklStatusChart !== 'undefined' && pklStatusChart !== null) {
                            pklStatusChart.data.datasets[0].data = [
                                Number(s.pengajuan ?? 0),
                                Number(s.pelaksanaan ?? 0),
                                Number(s.verifikasi ?? 0),
                                Number(s.ujian ?? 0),
                                Number(s.seminar ?? 0),
                                Number(s.revisi ?? 0),
                                Number(s.selesai ?? 0)
                            ];
                            pklStatusChart.update(); // Re-render diagram
                        }
                    }
        
                    // 3. Panggil fetchTableData agar tampilan tabel merespons filter yang sedang aktif
                    if (typeof fetchTableData === 'function') {
                        fetchTableData();
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan saat memperbarui data.';
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        errorMsg = Object.values(errors).map(err => err[0]).join('<br>');
                    }
                    Swal.fire({ icon: 'error', title: 'Gagal', html: errorMsg });
                },
                complete: function() {
                    btnSubmit.prop('disabled', false);
                    btnSubmit.find('.btn-text').text('Simpan Perubahan');
                    btnSubmit.find('.spinner-border').addClass('d-none');
                }
            });
        });
    });
</script>
@endpush