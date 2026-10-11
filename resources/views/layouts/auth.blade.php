<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title')</title>
    <link href="{{ asset('templates/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/css/common.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
    <link href="{{ asset('templates/css/theme-03.css') }}" rel="stylesheet">
    
    <link rel="icon" type="image/png" href="{{ asset('assets/UNITRI.png') }}">
    <meta name="theme-color" content="#21123b">
    
    @stack('styles')
    
    <style>
        /* 1. Bebaskan Scroll di level HTML, Body, dan seluruh kontainer */
        html, body {
            height: auto !important;
            min-height: 100% !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            position: relative !important;
        }

        /* 2. Paksa semua pembungkus Forny tidak memotong konten yang melimpah */
        .forny-container, 
        .forny-inner, 
        .forny-form {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            position: relative !important;
        }

        .forny-container {
            display: block !important;
            padding: 30px 15px 120px 15px !important; /* Jarak bawah 120px */
        }

        .forny-inner {
            margin: 0 auto !important;
            max-width: 520px !important;
        }

        .forny-form {
            padding-bottom: 40px !important;
        }
    </style>
</head>

<body class="@yield('body-class')">
    <div class="forny-container">
        @yield('header')
        <div class="forny-inner">
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('templates/js/jquery.min.js') }}"></script>
    <script src="{{ asset('templates/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('templates/js/main.js') }}"></script>
</body>
</html>