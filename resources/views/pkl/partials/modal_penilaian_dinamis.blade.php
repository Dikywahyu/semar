<div class="modal fade" id="modalPenilaianDinamis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold text-slate-800">Penilaian Seminar Multi-Dosen (Pembimbing & Penguji)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPenilaianDinamis" method="POST">
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div id="containerKomponenNilai">
                        <!-- Form komponen per dosen akan dirender otomatis via AJAX di sini -->
                        <div class="text-center text-muted py-3">Memuat komponen dan daftar dosen penilai...</div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitNilaiDinamis" class="btn btn-primary fw-semibold px-4">
                        <span class="btn-text">Rekap, Hitung & Simpan Nilai</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Klik tombol penilaian dinamis di tabel
        $(document).on('click', '.btn-penilaian-dinamis', function() {
            let id = $(this).data('id');
            let urlForm = "{{ url('pkl/admin/penilaian-seminar') }}/" + id;

            $('#formPenilaianDinamis').attr('action', urlForm);
            $('#containerKomponenNilai').html('<div class="text-center text-muted py-3">Memuat komponen dan daftar dosen penilai...</div>');
            $('#modalPenilaianDinamis').modal('show');

            $.ajax({
                url: urlForm,
                type: 'GET',
                success: function(response) {
                    let data = response.data;
                    let pkl = data.pkl;
                    let komponens = data.komponens;
                    let existingNilai = data.existing_nilai; // Format: [dosen_id][komponen_id]

                    if (komponens.length === 0) {
                        $('#containerKomponenNilai').html(`<div class="alert alert-danger">Belum ada komponen penilaian untuk semester mahasiswa ini. Atur terlebih dahulu di menu Master Komponen.</div>`);
                        return;
                    }

                    // Kumpulkan semua dosen penilai (Pembimbing + Penguji)
                    let listPenilai = [];
                    
                    if (pkl.dosen_pembimbing) {
                        listPenilai.push({
                            id: pkl.dosen_pembimbing.id_dosen,
                            nama: pkl.dosen_pembimbing.nama_dosen + ' (Dosen Pembimbing)',
                            badge: 'bg-primary-subtle text-primary'
                        });
                    }

                    if (pkl.dosen_penguji && pkl.dosen_penguji.length > 0) {
                        pkl.dosen_penguji.forEach(function(penguji, index) {
                            listPenilai.push({
                                id: penguji.id_dosen,
                                nama: penguji.nama_dosen + ' (Dosen Penguji ' + (index + 1) + ')',
                                badge: 'bg-info-subtle text-info'
                            });
                        });
                    }

                    if (listPenilai.length === 0) {
                        $('#containerKomponenNilai').html(`<div class="alert alert-warning">Mahasiswa ini belum memiliki Dosen Pembimbing atau Dosen Penguji yang diploting.</div>`);
                        return;
                    }

                    let html = `<p class="text-muted small mb-3">Masukkan nilai angka (0 - 100) dari masing-masing dosen penilai. Sistem akan menghitung rata-rata per komponen dan mengkalikannya dengan bobot semester secara otomatis.</p>`;

                    // Loop untuk setiap Dosen Penilai
                    listPenilai.forEach(function(dosen) {
                        html += `
                            <div class="card border mb-3 shadow-sm rounded-3">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark"><i class="ti ti-user-circle me-1"></i> ${dosen.nama}</span>
                                    <span class="badge ${dosen.badge}">Penilai</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                        `;

                        // Loop komponen penilaian untuk dosen tersebut
                        komponens.forEach(function(komp) {
                            let val = '';
                            
                            // Cek apakah data nilai untuk dosen dan komponen ini benar-benar ada
                            if (existingNilai[dosen.id] && existingNilai[dosen.id][komp.id]) {
                                val = existingNilai[dosen.id][komp.id].nilai_angka;
                            } 
                            // Fallback alternatif jika key di existingNilai menggunakan format UUID id_dosen
                            else if (dosen.id_dosen && existingNilai[dosen.id_dosen] && existingNilai[dosen.id_dosen][komp.id]) {
                                val = existingNilai[dosen.id_dosen][komp.id].nilai_angka;
                            }
                        
                            html += `
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small fw-semibold text-secondary mb-1">
                                        ${komp.nama_komponen} <span class="text-muted">(${komp.bobot}%)</span>
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100" 
                                           name="nilai[${dosen.id}][${komp.id}]" 
                                           class="form-control form-control-sm" 
                                           value="${val}" 
                                           placeholder="Cth: 85" required>
                                </div>
                            `;
                        });

                        html += `
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    $('#containerKomponenNilai').html(html);
                },
                error: function(xhr) {
                    let errMessage = xhr.responseJSON?.message || 'Gagal memuat form penilaian.';
                    $('#containerKomponenNilai').html(`<div class="alert alert-danger">${errMessage}</div>`);
                }
            });
        });

        // Submit form nilai multi-dosen
        $('#formPenilaianDinamis').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#btnSubmitNilaiDinamis');

            btn.prop('disabled', true);
            btn.find('.btn-text').text('Menghitung...');
            btn.find('.spinner-border').removeClass('d-none');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    $('#modalPenilaianDinamis').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan nilai.';
                    Swal.fire('Gagal', msg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').text('Rekap, Hitung & Simpan Nilai');
                    btn.find('.spinner-border').addClass('d-none');
                }
            });
        });
    });
</script>
@endpush