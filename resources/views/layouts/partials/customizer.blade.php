@push('scripts')
<script>
    /**
     * 1. FUNGSI LOGIKA UTAMA
     */

    // Fungsi universal untuk menerapkan atribut DOM dan menyimpan ke LocalStorage
    function handleAttribute(attr, val, storageKey) {
        if (attr === 'data-bs-theme') {
            handleLayoutMode(val); // Memanggil fungsi khusus mode untuk sinkronisasi ikon
        } else {
            document.documentElement.setAttribute(attr, val);
            localStorage.setItem(storageKey, val);
        }
    }

    // Fungsi khusus warna tema (Blue, Aqua, dll)
    function handleColorTheme(theme) {
        document.documentElement.setAttribute("data-color-theme", theme);
        localStorage.setItem("user-theme", theme);
    }

    // Fungsi khusus mode gelap/terang (Termasuk sinkronisasi ikon Sun/Moon)
    function handleLayoutMode(mode) {
        document.documentElement.setAttribute("data-bs-theme", mode);
        localStorage.setItem("user-layout-mode", mode);

        if (mode === 'dark') {
            $('.moon').hide();
            $('.sun').show();
        } else {
            $('.sun').hide();
            $('.moon').show();
        }
    }

    /**
     * 2. INISIALISASI SAAT HALAMAN DIMUAT (Initial Load)
     */
    $(document).ready(function() {
        // Ambil data tersimpan atau gunakan nilai default
        const config = {
            mode: localStorage.getItem("user-layout-mode") || "light",
            theme: localStorage.getItem("user-theme") || "Blue_Theme",
            dir: localStorage.getItem("user-direction") || "ltr",
            layout: localStorage.getItem("user-layout-type") || "vertical",
            container: localStorage.getItem("user-container") || "boxed",
            sidebar: localStorage.getItem("user-sidebar-type") || "full",
            card: localStorage.getItem("user-card-border") || "false"
        };

        // Terapkan semua konfigurasi ke sistem saat halaman siap
        handleLayoutMode(config.mode);
        handleColorTheme(config.theme);
        handleAttribute('dir', config.dir, "user-direction");
        handleAttribute("data-layout", config.layout, "user-layout-type");
        handleAttribute("data-boxed-layout", config.container, "user-container");
        handleAttribute("data-sidebartype", config.sidebar, "user-sidebar-type");
        handleAttribute("data-card-with-border", config.card, "user-card-border");

        /**
         * 3. LOGIKA AUTO-CHECK (Radio Button sinkron dengan data tersimpan)
         */
        const uiMapping = {
            "user-theme": config.theme,
            "user-layout-mode": config.mode,
            "user-direction": config.dir,
            "user-layout-type": config.layout,
            "user-container": config.container,
            "user-sidebar-type": config.sidebar
        };

        Object.keys(uiMapping).forEach(key => {
            const val = uiMapping[key];
            let targetId = val;

            // Pemetaan ID agar sesuai dengan penamaan di HTML Anda
            if (['ltr', 'rtl', 'vertical', 'horizontal', 'boxed', 'full'].includes(val)) {
                targetId = val + '-layout';
            } else if (val === 'mini-sidebar') {
                targetId = 'mini-sidebar';
            } else if (val === 'full' && key === 'user-sidebar-type') {
                targetId = 'full-sidebar';
            }

            $(`#${targetId}`).prop('checked', true);
        });

        // Khusus Card Border
        $(`#card-${config.card === 'true' ? 'with' : 'without'}-border`).prop('checked', true);

        /**
         * 4. EVENT LISTENERS (Menangani klik tombol di panel pengaturan)
         */

        // Mode Terang/Gelap
        $('.light-layout').on('click', function() {
            handleLayoutMode('light');
        });
        $('.dark-layout').on('click', function() {
            handleLayoutMode('dark');
        });

        // Arah Teks (LTR/RTL)
        $('#ltr-layout').on('click', function() {
            handleAttribute('dir', 'ltr', "user-direction");
        });
        $('#rtl-layout').on('click', function() {
            handleAttribute('dir', 'rtl', "user-direction");
        });

        // Tipe Layout
        $('#vertical-layout').on('click', function() {
            handleAttribute('data-layout', 'vertical', "user-layout-type");
        });
        $('#horizontal-layout').on('click', function() {
            handleAttribute('data-layout', 'horizontal', "user-layout-type");
        });

        // Warna Tema (Pola dinamis berdasarkan ID input)
        $('input[name="color-theme-layout"]').on('click', function() {
            handleColorTheme($(this).attr('id'));
        });

        // Tipe Sidebar
        $('#full-sidebar').on('click', function() {
            handleAttribute('data-sidebartype', 'full', "user-sidebar-type");
        });
        $('#mini-sidebar').on('click', function() {
            handleAttribute('data-sidebartype', 'mini-sidebar', "user-sidebar-type");
        });

        // Opsi Container
        $('#boxed-layout').on('click', function() {
            handleAttribute('data-boxed-layout', 'boxed', "user-container");
        });
        $('#full-layout').on('click', function() {
            handleAttribute('data-boxed-layout', 'full', "user-container");
        });

        // Gaya Kartu (Border/Shadow)
        $('#card-with-border').on('click', function() {
            handleAttribute('data-card-with-border', 'true', "user-card-border");
        });
        $('#card-without-border').on('click', function() {
            handleAttribute('data-card-with-border', 'false', "user-card-border");
        });
    });
</script>
@endpush


<button class="btn btn-primary p-3 rounded-circle d-flex align-items-center justify-content-center customizer-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
    <i class="icon ti ti-settings fs-7"></i>
</button>

<div class="offcanvas customizer offcanvas-end" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h4 class="offcanvas-title fw-semibold" id="offcanvasExampleLabel">
            Settings
        </h4>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body h-n80" data-simplebar>
        <h6 class="fw-semibold fs-4 mb-2">Theme</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <input type="radio" class="btn-check light-layout" name="theme-layout" id="light-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="light-layout">
                <i class="icon ti ti-brightness-up fs-7 me-2"></i>Light
            </label>

            <input type="radio" class="btn-check dark-layout" name="theme-layout" id="dark-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary rounded-2" for="dark-layout">
                <i class="icon ti ti-moon fs-7 me-2"></i>Dark
            </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Direction</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <input type="radio" class="btn-check" name="direction-l" id="ltr-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="ltr-layout">
                <i class="icon ti ti-text-direction-ltr fs-7 me-2"></i>LTR
            </label>

            <input type="radio" class="btn-check" name="direction-l" id="rtl-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="rtl-layout">
                <i class="icon ti ti-text-direction-rtl fs-7 me-2"></i>RTL
            </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Theme Colors</h6>

        <div class="d-flex flex-row flex-wrap gap-3 customizer-box color-pallete" role="group">
            <input type="radio" class="btn-check" name="color-theme-layout" id="Blue_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Blue_Theme')" for="Blue_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="BLUE_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-1">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>

            <input type="radio" class="btn-check" name="color-theme-layout" id="Aqua_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Aqua_Theme')" for="Aqua_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="AQUA_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-2">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>

            <input type="radio" class="btn-check" name="color-theme-layout" id="Purple_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Purple_Theme')" for="Purple_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="PURPLE_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-3">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>

            <input type="radio" class="btn-check" name="color-theme-layout" id="Green_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Green_Theme')" for="Green_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="GREEN_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-4">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>

            <input type="radio" class="btn-check" name="color-theme-layout" id="Cyan_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Cyan_Theme')" for="Cyan_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="CYAN_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-5">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>

            <input type="radio" class="btn-check" name="color-theme-layout" id="Orange_Theme" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary d-flex align-items-center justify-content-center" onclick="handleColorTheme('Orange_Theme')" for="Orange_Theme" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="ORANGE_THEME">
                <div class="color-box rounded-circle d-flex align-items-center justify-content-center skin-6">
                    <i class="ti ti-check text-white d-flex icon fs-5"></i>
                </div>
            </label>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Layout Type</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <div>
                <input type="radio" class="btn-check" name="page-layout" id="vertical-layout" autocomplete="off" />
                <label class="btn p-9 btn-outline-primary" for="vertical-layout">
                    <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Vertical
                </label>
            </div>
            <div>
                <input type="radio" class="btn-check" name="page-layout" id="horizontal-layout" autocomplete="off" />
                <label class="btn p-9 btn-outline-primary" for="horizontal-layout">
                    <i class="icon ti ti-layout-navbar fs-7 me-2"></i>Horizontal
                </label>
            </div>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Container Option</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <input type="radio" class="btn-check" name="layout" id="boxed-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="boxed-layout">
                <i class="icon ti ti-layout-distribute-vertical fs-7 me-2"></i>Boxed
            </label>

            <input type="radio" class="btn-check" name="layout" id="full-layout" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="full-layout">
                <i class="icon ti ti-layout-distribute-horizontal fs-7 me-2"></i>Full
            </label>
        </div>

        <h6 class="fw-semibold fs-4 mb-2 mt-5">Sidebar Type</h6>
        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <a href="javascript:void(0)" class="fullsidebar">
                <input type="radio" class="btn-check" name="sidebar-type" id="full-sidebar" autocomplete="off" />
                <label class="btn p-9 btn-outline-primary" for="full-sidebar">
                    <i class="icon ti ti-layout-sidebar-right fs-7 me-2"></i>Full
                </label>
            </a>
            <div>
                <input type="radio" class="btn-check " name="sidebar-type" id="mini-sidebar" autocomplete="off" />
                <label class="btn p-9 btn-outline-primary" for="mini-sidebar">
                    <i class="icon ti ti-layout-sidebar fs-7 me-2"></i>Collapse
                </label>
            </div>
        </div>

        <h6 class="mt-5 fw-semibold fs-4 mb-2">Card With</h6>

        <div class="d-flex flex-row gap-3 customizer-box" role="group">
            <input type="radio" class="btn-check" name="card-layout" id="card-with-border" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="card-with-border">
                <i class="icon ti ti-border-outer fs-7 me-2"></i>Border
            </label>

            <input type="radio" class="btn-check" name="card-layout" id="card-without-border" autocomplete="off" />
            <label class="btn p-9 btn-outline-primary" for="card-without-border">
                <i class="icon ti ti-border-none fs-7 me-2"></i>Shadow
            </label>
        </div>
    </div>
</div>