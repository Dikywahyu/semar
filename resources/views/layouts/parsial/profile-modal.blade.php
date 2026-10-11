<div class="modal fade" id="modalMyAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

            <div class="modal-header bg-primary text-white border-0 py-3" style="background: linear-gradient(135deg, #5d87ff 0%, #769bff 100%);">
                <h5 class="modal-title text-white d-flex align-items-center fs-5 fw-bold">
                    <iconify-icon icon="solar:settings-bold-duotone" class="me-2 fs-7"></iconify-icon> Pusat Pengaturan Akun
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <ul class="nav nav-tabs  p-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link d-flex active  " data-bs-toggle="tab" href="#tab-profile" role="tab">
                        <span><i class="ti ti-user fs-4"></i></span>
                        <span class="d-none d-md-block ms-2 ">Profil Personal</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex  " data-bs-toggle="tab" href="#tab-password" role="tab">
                        <span><i class="ti ti-lock fs-4"></i></span>
                        <span class="d-none d-md-block ms-2  ">Keamanan Sandi</span>
                    </a>
                </li>

            </ul>

            <div class="tab-content bg-white">

                <div class="tab-pane active" id="tab-profile" role="tabpanel">
                    <form id="formUpdateProfile" action="{{ url('account/update-profile') }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="modal-body p-4">
                            <div class="row align-items-center mb-4">
                                <div class="col-md-3 text-center">
                                    <div class="position-relative d-inline-block">
                                        <img src="{{ filter_var(Auth::user()->avatar, FILTER_VALIDATE_URL) ? Auth::user()->avatar : (Auth::user()->avatar ? asset('dist/assets/images/profile/' . Auth::user()->avatar) : asset('dist/assets/images/profile/user-1.jpg')) }}"
                                            id="preview-avatar" class="rounded-circle border border-4 border-light shadow-sm" width="110" height="110" style="object-fit: cover;">
                                        <label for="input-avatar" class="position-absolute bottom-0 end-0 bg-white p-2 rounded-circle shadow-sm cursor-pointer border" title="Ubah Foto">
                                            <i class="ti ti-camera text-primary fs-4"></i>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-9 text-start">
                                    <h5 class="fw-bold text-dark mb-1">{{ Auth::user()->name }}</h5>
                                    <p class="text-muted fs-3 mb-0">Kelola informasi dasar dan foto profil Anda.</p>
                                </div>
                            </div>

                            <div class="mb-3 text-start">
                                <label class="form-label fw-bold text-dark fs-2">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control form-control-lg fs-3 px-4" value="{{ Auth::user()->name }}" required>
                            </div>
                            <div class="mb-3 d-none">
                                <input type="file" name="avatar" id="input-avatar" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer bg-light-subtle border-top-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary px-4" id="btnSaveProfile">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane" id="tab-password" role="tabpanel">
                    <form id="formUpdatePassword" action="{{ url('account/update-password') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body p-4 text-start">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark fs-2">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" class="form-control form-control-lg fs-3 toggle-password-input" placeholder="Masukkan password saat ini" required>
                                    <button class="btn btn-outline-border btn-toggle-password" type="button" style="border: 1px solid #e5eaef; border-left: none;">
                                        <i class="ti ti-eye fs-5 text-muted"></i>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-4 text-muted opacity-25">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark fs-2">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control form-control-lg fs-3 toggle-password-input" placeholder="Minimal 8 karakter" required>
                                    <button class="btn btn-outline-border btn-toggle-password" type="button" style="border: 1px solid #e5eaef; border-left: none;">
                                        <i class="ti ti-eye fs-5 text-muted"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark fs-2">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" class="form-control form-control-lg fs-3 toggle-password-input" placeholder="Ulangi password baru" required>
                                    <button class="btn btn-outline-border btn-toggle-password" type="button" style="border: 1px solid #e5eaef; border-left: none;">
                                        <i class="ti ti-eye fs-5 text-muted"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer bg-light-subtle border-top-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4" id="btnSavePassword">Ganti Password</button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Instant Preview Avatar saat File Dipilih
        $('#input-avatar').on('change', function() {
            let reader = new FileReader();
            reader.onload = (e) => {
                $('#preview-avatar').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        });

        // AJAX 1: Kirim Update Profil (Nama & Foto)
        $('#formUpdateProfile').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSaveProfile');
            let formData = new FormData(this); // Mengambil seluruh input berkas file

            // PENTING: Paksa FormData untuk menyisipkan method PUT agar dibaca oleh Laravel
            formData.append('_method', 'PUT');

            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST', // Kita tetap pakai POST di sini sebagai container pengiriman berkas
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(res) {
                    $('#modalMyAccount').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Manipulasi DOM Instan tanpa Reload Halaman penuh
                    if (res.new_avatar) {
                        $('.user-profile-avatar').attr('src', res.new_avatar);
                        $('#preview-avatar').attr('src', res.new_avatar);
                    }
                    $('.user-profile-name').text(formData.get('name'));
                },
                error: function(xhr) {
                    console.log(xhr.responseText); // Cek detail error di F12 jika ada kendala
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal memperbarui profil.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Simpan Perubahan');
                }
            });
        });

        // AJAX 2: Kirim Update Password
        $('#formUpdatePassword').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btnSavePassword');
            btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalMyAccount').modal('hide');
                    $('#formUpdatePassword')[0].reset(); // Bersihkan form field password
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan validasi.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Ganti Password');
                }
            });
        });

        // Fungsi Global Toggle Hide/Show Password
        $('.btn-toggle-password').on('click', function() {
            // Ambil elemen input yang berada satu kontainer group dengan tombol ini
            let inputField = $(this).closest('.input-group').find('.toggle-password-input');
            let icon = $(this).find('i');

            if (inputField.attr('type') === 'password') {
                // Ubah jadi text untuk Unhide
                inputField.attr('type', 'text');
                // Ganti ikon menjadi mata tercoret (eye-off / eye-closed)
                icon.removeClass('ti-eye').addClass('ti-eye-off');
            } else {
                // Kembalikan jadi password untuk Hide
                inputField.attr('type', 'password');
                // Kembalikan ikon jadi mata normal
                icon.removeClass('ti-eye-off').addClass('ti-eye');
            }
        });
    });
</script>
@endpush