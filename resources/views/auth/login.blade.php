@extends('layouts.auth')

@section('title', 'Masuk | SEMAR Teknik Sipil UNITRI')
@section('body-class', 'login-page')

@section('header')
<header class="login-hero">
    <div class="login-hero__background" aria-hidden="true"></div>
    <img
        class="login-hero__portrait"
        src="{{ asset('assets/Mahasiswi UNITRI.png') }}"
        alt="">

    <div class="login-brand" aria-label="SEMAR Teknik Sipil Universitas Tribhuwana Tunggadewi">
        <span class="login-brand__name">SEMAR</span>
        <span class="login-brand__faculty">TEKNIK SIPIL</span>
        <span class="login-brand__university">Universitas Tribhuwana Tunggadewi</span>
    </div>

    <img
        class="login-hero__emblem"
        src="{{ asset('assets/UNITRI.png') }}"
        alt="Lambang Universitas Tribhuwana Tunggadewi">
</header>
@endsection

@section('content')
<main class="login-card">
    <div class="login-card__heading">
        <h1>Selamat Datang!</h1>
        <p>Silakan masuk ke ruang kerja Anda.</p>
    </div>

    @if ($errors->any())
    <div class="login-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('error'))
    <div class="login-alert" role="alert">{{ session('error') }}</div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="login-form">
        @csrf
        <label for="username">EMAIL / NIM / NIDN</label>
        <input
            id="username"
            class="login-form__input"
            name="username"
            type="text"
            value="{{ old('username', old('email')) }}"
            autocomplete="username"
            placeholder="Ketik ID Anda"
            required
            autofocus>

        <label for="password-field">PASSWORD</label>
        <div class="login-form__password">
            <input
                id="password-field"
                class="login-form__input"
                name="password"
                type="password"
                autocomplete="current-password"
                placeholder="Ketik Password"
                required>
            <button class="password-toggle" id="toggle-password" type="button" aria-label="Tampilkan password">
                Tampilkan
            </button>
        </div>

        <input type="hidden" name="remember" value="1">
        <button class="login-form__submit" type="submit">Masuk ke Sistem</button>
    </form>

    <div class="login-card__divider"><span>atau</span></div>
    <a class="login-google" href="{{ route('auth.google') }}">
        <svg aria-hidden="true" viewBox="0 0 48 48" width="20" height="20">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z" />
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.72 7.18l7.62 5.91c4.45-4.1 7.14-10.15 7.14-17.56Z" />
            <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.75-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.87.93 7.53 2.56 10.78l7.97-6.19Z" />
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.89l-7.62-5.91c-2.12 1.43-4.84 2.27-8.29 2.27-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z" />
        </svg>
        <span>Masuk dengan Google</span>
    </a>

    <p class="login-card__register">
        Belum memiliki akun?
        <a href="{{ route('auth.google') }}">Daftar Akun</a>
    </p>
</main>

<script>
    const passwordField = document.getElementById('password-field');
    const passwordToggle = document.getElementById('toggle-password');

    passwordToggle.addEventListener('click', () => {
        const isPassword = passwordField.type === 'password';
        passwordField.type = isPassword ? 'text' : 'password';
        passwordToggle.textContent = isPassword ? 'Sembunyikan' : 'Tampilkan';
        passwordToggle.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
    });
</script>
@endsection

@push('styles')
<style>
    html:has(body.login-page),
    body.login-page {
        height: 100% !important;
        min-width: 320px;
        min-height: 100vh;
        overflow: hidden !important;
        color: #321651;
        background: #140b1e;
        font-family: "Poppins", "Segoe UI", sans-serif;
    }

    /* Container utama diubah agar bertumpuk (layering) bukan grid split */
    .login-page .forny-container {
        position: relative !important;
        display: flex !important;
        width: 100vw;
        height: 100vh !important;
        min-height: 600px !important;
        overflow: hidden !important;
        padding: 0 !important;
        background: #140b1e;
    }

    /* Header & Background ditarik penuh ke seluruh layar */
    .login-hero {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        overflow: hidden;
        background: #190d28;
        z-index: 1;
    }

    .login-hero__background,
    .login-hero::after {
        position: absolute;
        inset: 0;
        content: "";
    }

    /* Gambar Gedung Full Layar */
    .login-hero__background {
        width: 100%;
        height: 100%;
        background-image: url("{{ asset('assets/Gambar Gedung UNITRI.jpeg') }}");
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        filter: saturate(.72);
        transform: scale(1.03);
    }

    /* Gradasi overlay melintasi seluruh layar */
    .login-hero::after {
        z-index: 1;
        background:
            linear-gradient(90deg, rgba(23, 7, 39, .92) 0%, rgba(28, 8, 48, .82) 45%, rgba(20, 11, 30, .88) 100%),
            linear-gradient(0deg, rgba(11, 5, 20, .85), transparent 60%);
    }

    /* Foto Mahasiswa (digeser sedikit agar tidak terlalu menutupi form/teks) */
    .login-hero__portrait {
        position: absolute;
        z-index: 2;
        bottom: -15vh;
        left: 21%;
        width: auto;
        height: min(158vh, 820px);
        max-width: none;
        object-fit: contain;
        /* transform: translateX(-50%); */
    }

    /* Teks Brand (SEMAR TEKNIK SIPIL) */
    .login-brand {
        position: absolute;
        z-index: 3;
        top: 28.5%;
        left: clamp(32px, 5vw, 70px);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        color: #fff;
        line-height: .98;
    }

    .login-brand__name,
    .login-brand__faculty {
        font-size: clamp(40px, 4.8vw, 72px);
        font-weight: 700;
        letter-spacing: -.04em;
        white-space: nowrap;
    }

    .login-brand__faculty {
        margin-top: 4px;
        color: #ffa000;
    }

    .login-brand__university {
        margin-top: 18px;
        font-size: clamp(14px, 1.4vw, 20px);
        font-weight: 500;
        letter-spacing: -.02em;
        white-space: nowrap;
    }

    .login-hero__emblem {
        position: absolute;
        z-index: 3;
        bottom: 8%;
        left: clamp(32px, 5vw, 70px);
        width: 68px;
        height: 68px;
        border: 2px solid rgba(255, 255, 255, .7);
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 3px 20px rgba(0, 0, 0, .35);
    }

    /* Posisikan Wrapper Form Login di sisi kanan melayang di atas background */
    .login-page .forny-inner {
        position: absolute !important;
        right: 0;
        top: 0;
        bottom: 0;
        z-index: 4;
        display: flex;
        width: min(100%, 480px) !important;
        height: 100% !important;
        align-items: center;
        justify-content: center;
        margin: 0 !important;
        padding: 20px 40px;
    }

    /* Form Card dengan efek frosted-glass */
    .login-card {
        width: 100%;
        padding: 40px 32px 35px;
        border: 1px solid rgba(255, 255, 255, .6);
        border-radius: 24px;
        background: rgba(229, 216, 237, 0.94);
        backdrop-filter: blur(8px);
        box-shadow: 0 22px 70px rgba(0, 0, 0, .35);
        transform: none;
    }

    .login-card__heading {
        margin-bottom: 28px;
    }

    .login-card__heading h1 {
        margin: 0;
        color: #351452;
        font-size: clamp(24px, 2.2vw, 30px);
        font-weight: 600;
        letter-spacing: -.03em;
        line-height: 1.2;
    }

    .login-card__heading p {
        margin: 5px 0 0;
        color: #5c5266;
        font-size: 13px;
    }

    .login-alert {
        margin: -13px 0 20px;
        padding: 11px 13px;
        border: 1px solid #e3aab2;
        border-radius: 9px;
        color: #842029;
        background: #fff0f1;
        font-size: 12px;
    }

    .login-form>label {
        display: block;
        margin: 0 0 8px;
        color: #34164f;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .1em;
    }

    .login-form__input {
        display: block;
        width: 100%;
        height: 48px;
        padding: 0 15px;
        border: 1px solid #b999e4;
        border-radius: 10px;
        outline: none;
        color: #332444;
        background: #f7f4f9;
        font: inherit;
        font-size: 13px;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .login-form__input::placeholder {
        color: #647084;
        opacity: 1;
    }

    .login-form__input:focus {
        border-color: #7441ad;
        box-shadow: 0 0 0 3px rgba(116, 65, 173, .13);
    }

    .login-form>.login-form__input {
        margin-bottom: 20px;
    }

    .login-form__password {
        position: relative;
        margin-bottom: 20px;
    }

    .login-form__password .login-form__input {
        padding-right: 85px;
    }

    .password-toggle {
        position: absolute;
        top: 0;
        right: 0;
        height: 48px;
        padding: 0 12px;
        border: 0;
        color: #66418e;
        background: transparent;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .login-form__submit {
        width: 100%;
        min-height: 50px;
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: #f5a000;
        box-shadow: 0 6px 15px rgba(218, 133, 0, .2);
        font: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: background-color .18s ease, transform .18s ease;
    }

    .login-form__submit:hover {
        background: #e89300;
        transform: translateY(-1px);
    }

    .login-card__register {
        margin: 20px 0 0;
        color: #5b5265;
        font-size: 13px;
        text-align: center;
    }

    .login-card__divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 18px 0 14px;
        color: #776a82;
        font-size: 11px;
    }

    .login-card__divider::before,
    .login-card__divider::after {
        height: 1px;
        flex: 1;
        background: rgba(67, 44, 101, .17);
        content: "";
    }

    .login-google {
        display: flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: 1px solid rgba(83, 61, 107, .32);
        border-radius: 10px;
        color: #3d2a53;
        background: rgba(255, 255, 255, .38);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: background-color .18s ease, border-color .18s ease;
    }

    .login-google:hover,
    .login-google:focus-visible {
        border-color: #7955a0;
        color: #351452;
        background: rgba(255, 255, 255, .75);
        text-decoration: none;
    }

    .login-card__register a {
        color: #351452;
        font-weight: 700;
        text-decoration: none;
    }

    .login-card__register a:hover {
        text-decoration: underline;
    }

    /* Responsif Mobile */
    @media (max-width: 900px) {

        html:has(body.login-page),
        body.login-page {
            height: auto !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            background: #160b24;
        }

        .login-page .forny-container {
            display: flex !important;
            height: auto !important;
            min-height: 100vh !important;
            flex-direction: column;
        }

        .login-hero {
            position: relative !important;
            min-height: 340px;
            flex: 0 0 340px;
        }

        .login-hero__portrait {
            left: 40%;
            height: 320px;
        }

        .login-brand {
            top: 50px;
            left: 20px;
        }

        .login-brand__name,
        .login-brand__faculty {
            font-size: clamp(36px, 8vw, 48px);
        }

        .login-brand__university {
            margin-top: 10px;
            font-size: 13px;
        }

        .login-hero__emblem {
            bottom: 90px;
            left: 20px;
            width: 50px;
            height: 50px;
        }

        .login-page .forny-inner {
            position: relative !important;
            width: 100% !important;
            padding: 0 20px 30px;
            margin-top: -30px !important;
        }

        .login-card {
            padding: 28px 20px;
            border-radius: 20px;
        }
    }
</style>
@endpush