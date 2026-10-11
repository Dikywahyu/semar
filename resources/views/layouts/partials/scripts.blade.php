<script src="{{ asset('dist/assets/js/vendor.min.js') }}"></script>
<!-- Import Js Files -->
<script src="{{ asset('dist/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('dist/assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ asset('dist/assets/js/theme/app.init.js') }}"></script>
<script src="{{ asset('dist/assets/js/theme/theme.js') }}"></script>
<script src="{{ asset('dist/assets/js/theme/app.min.js') }}"></script>

<script src="{{ asset('dist/assets/libs/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script src="{{ asset('dist/assets/js/forms/sweet-alert.init.js') }}"></script>
<!-- <script src="{{ asset('dist/assets/js/theme/sidebarmenu.js') }}"></script> -->


<script src="{{ asset('dist/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script src="{{ asset('dist/assets/js/datatable/datatable-advanced.init.js') }}"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>


<!-- solar icons -->

<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

<!-- highlight.js') }} (code view) -->
<script src="{{ asset('dist/assets/js/highlights/highlight.min.js') }}"></script>
<script>
    hljs.initHighlightingOnLoad();


    document.querySelectorAll("pre.code-view > code").forEach((codeBlock) => {
        codeBlock.textContent = codeBlock.innerHTML;
    });
</script>

<script>
    $(document).ready(function() {
        const currentTheme = localStorage.getItem("user-theme") || "Blue_Theme";
        // Pilih radio button yang sesuai dengan ID tema
        $(`#${currentTheme}`).prop('checked', true);
    });
</script>
<script>
    $(document).ready(function() {
        
        // Mengikat aksi klik ke class menu sidebar kustom
        $(document).on('click', '.sidebar-link:contains("Clear Cache")', function(e) {
            e.preventDefault(); // Mencegah aksi lompat halaman default
        
            Swal.fire({
                title: 'Bersihkan Cache Sistem?',
                text: "Sistem akan menyegarkan seluruh konfigurasi, views, dan cache data di server IAM saat ini.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-warning me-2',
                    cancelButton: 'btn btn-light'
                },
                buttonsStyling: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.post("{{ url('admin/system/clear-cache') }}", {
                        _token: "{{ csrf_token() }}"
                    })
                    .done((res) => {
                        return res;
                    })
                    .fail((err) => {
                        let errMsg = err.responseJSON?.message || 'Gagal mengeksekusi perintah server.';
                        Swal.showValidationMessage(`Error: ${errMsg}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.status === 'success') {
                    Swal.fire({
                        title: 'Selesai!',
                        text: result.value.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Cek notifikasi baru setiap 30 detik
        setInterval(function() {
            $.get("{{ url('check-new-notifications') }}", function(data) {
                // 1. Update Badge
                if (data.unread_count > 0) {
                    $('.notification-badge').text(data.unread_count).show();
                } else {
                    $('.notification-badge').hide();
                }

                // 2. Update List Notifikasi
                $('#notification-content-target').html(data.notification_html);

                // 3. RE-INITIALIZE SimpleBar (Penting!)
                // Jika scrollbar tidak muncul otomatis, jalankan baris ini:
                const container = document.querySelector('[data-simplebar]');
                if (container) {
                    SimpleBar.instances.get(container).recalculate();
                }
            });
        }, 30000);

        // Daftar semua key dan default valuenya
        const controls = {
            "user-theme": "Blue_Theme",
            "user-layout-mode": "light",
            "user-direction": "ltr",
            "user-layout-type": "vertical",
            "user-container": "boxed",
            "user-sidebar-type": "full"
        };

        // Loop untuk mencentang radio button yang sesuai
        Object.keys(controls).forEach(key => {
            const val = localStorage.getItem(key) || controls[key];
            // Cari input radio berdasarkan ID atau Class yang mengandung value tersebut
            $(`input[id="${val}"], input[id="${val}-layout"], input[id="${val}-sidebar"]`).prop('checked', true);
        });
    });
</script>
<script src="{{ asset('dist/assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@stack('scripts')