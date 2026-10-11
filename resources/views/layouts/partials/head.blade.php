<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="../../assets/UNITRI.png" />

    <!-- Core Css -->
    <link rel="stylesheet" href="{{ asset('dist/assets/css/styles.css') }}" />
    <link rel="stylesheet" href="{{ asset('dist/assets/libs/sweetalert2/dist/sweetalert2.min.css') }}" />



    <link rel="stylesheet" href="{{ asset('dist/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - (SEMAR) Sistem Evaluasi Monitoring dan Registrasi </title>

    <script>
        // Konfigurasi default & Pengambilan data dari LocalStorage
        const themeConfig = {
            theme: localStorage.getItem("user-theme") || "Blue_Theme",
            mode: localStorage.getItem("user-layout-mode") || "light",
            dir: localStorage.getItem("user-direction") || "ltr",
            layout: localStorage.getItem("user-layout-type") || "vertical",
            container: localStorage.getItem("user-container") || "boxed",
            sidebar: localStorage.getItem("user-sidebar-type") || "full",
            cardBorder: localStorage.getItem("user-card-border") || "false"
        };


        // Terapkan langsung ke elemen root (HTML)
        document.documentElement.setAttribute("data-color-theme", themeConfig.theme);
        document.documentElement.setAttribute("data-bs-theme", themeConfig.mode);
        document.documentElement.setAttribute("dir", themeConfig.dir);
        document.documentElement.setAttribute("data-layout", themeConfig.layout);
        document.documentElement.setAttribute("data-boxed-layout", themeConfig.container);
        document.documentElement.setAttribute("data-sidebartype", themeConfig.sidebar);
        document.documentElement.setAttribute("data-card-with-border", themeConfig.cardBorder);
    </script>
    @stack('styles')
</head>