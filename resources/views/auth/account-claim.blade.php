@extends('layouts.auth')

@section('title', 'Klaim Akun Akademik | SEMAR')
@section('body-class', 'account-claim-page')

@section('content')
    <main class="claim-card">
        <div class="claim-brand">
            <img src="{{ asset('assets/Gambar Tulisan UNITRI.jpeg') }}" alt="Lambang Universitas Tribhuwana Tunggadewi">
            <div>
                <span>SEMAR</span>
                <small>TEKNIK SIPIL UNITRI</small>
            </div>
        </div>

        <div class="claim-heading">
            <span class="claim-heading__eyebrow">SATU LANGKAH LAGI</span>
            <h1>Hubungkan akun akademik</h1>
            <p>Masukkan identitas akademik untuk menghubungkan akun Google Anda dengan data kampus.</p>
        </div>

        <div class="claim-google-account">
            @if (filled(auth()->user()->avatar))
                <img src="{{ auth()->user()->avatar }}" alt="" referrerpolicy="no-referrer">
            @else
                <span class="claim-google-account__initial">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            @endif
            <div>
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="claim-alert" role="alert">{{ $errors->first() }}</div>
        @endif
        @if (session('success'))
            <div class="claim-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->has('identifier'))
            <p id="claim-error" class="visually-hidden">{{ $errors->first('identifier') }}</p>
        @endif
        <form action="{{ route('account.claim.store') }}" method="POST" class="claim-form">
            @csrf
            <label for="identifier">NIM / NIDN / NUPTK</label>
            <input
                id="identifier"
                class="claim-form__input"
                name="identifier"
                type="text"
                value="{{ old('identifier') }}"
                autocomplete="off"
                placeholder="Masukkan nomor identitas akademik"
                maxlength="50"
                required
                @if ($errors->has('identifier')) aria-invalid="true" aria-describedby="claim-error" @endif
                autofocus>
            <p class="claim-form__hint">
                NIM digunakan untuk mahasiswa. NIDN atau NUPTK digunakan untuk dosen.
            </p>

            <label for="claim-password">BUAT PASSWORD</label>
            <div class="claim-form__password">
                <input
                    id="claim-password"
                    class="claim-form__input"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    minlength="8"
                    required>
                <button class="claim-password-toggle" type="button" data-password-target="claim-password" aria-label="Tampilkan password">Tampilkan</button>
            </div>

            <label for="claim-password-confirmation">ULANGI PASSWORD</label>
            <div class="claim-form__password">
                <input
                    id="claim-password-confirmation"
                    class="claim-form__input"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Masukkan ulang password"
                    minlength="8"
                    required>
                <button class="claim-password-toggle" type="button" data-password-target="claim-password-confirmation" aria-label="Tampilkan password">Tampilkan</button>
            </div>

            <button class="claim-form__submit" type="submit">Hubungkan Akun & Buat Password</button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="claim-logout">
            @csrf
            <button type="submit">Keluar dari akun ini</button>
        </form>
    </main>
    <script>
        document.querySelectorAll('.claim-password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const field = document.getElementById(button.dataset.passwordTarget);
                const isPassword = field.type === 'password';
                field.type = isPassword ? 'text' : 'password';
                button.textContent = isPassword ? 'Sembunyikan' : 'Tampilkan';
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });
    </script>
@endsection

@push('styles')
    <style>
        body.account-claim-page {
            min-height: 100vh;
            color: #302044;
            background: #170b25;
            font-family: "Poppins", "Segoe UI", sans-serif;
        }

        .account-claim-page .forny-container {
            display: flex !important;
            min-height: 100vh !important;
            align-items: center;
            justify-content: center;
            padding: 30px 16px !important;
            background:
                radial-gradient(ellipse at 16% 5%, rgba(103, 56, 155, .5), transparent 36%),
                linear-gradient(135deg, #170b25 0%, #28103e 54%, #160a24 100%);
        }

        .account-claim-page .forny-inner {
            width: min(100%, 510px);
            max-width: 510px !important;
            margin: 0 auto !important;
        }

        .claim-card {
            padding: clamp(26px, 6vw, 42px);
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 23px;
            background: #eee5f2;
            box-shadow: 0 24px 75px rgba(0, 0, 0, .3);
        }

        .claim-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .claim-brand img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }

        .claim-brand div {
            display: flex;
            flex-direction: column;
            line-height: 1.12;
        }

        .claim-brand span {
            color: #351452;
            font-size: 17px;
            font-weight: 700;
        }

        .claim-brand small {
            margin-top: 4px;
            color: #71657e;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .1em;
        }

        .claim-heading {
            margin-bottom: 21px;
        }

        .claim-heading__eyebrow {
            color: #7c6295;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .14em;
        }

        .claim-heading h1 {
            margin: 6px 0 8px;
            color: #32154d;
            font-size: clamp(24px, 5vw, 30px);
            font-weight: 600;
            letter-spacing: -.04em;
        }

        .claim-heading p {
            margin: 0;
            color: #665c70;
            font-size: 13px;
            line-height: 1.65;
        }

        .claim-google-account {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 0 22px;
            padding: 12px;
            border: 1px solid rgba(86, 63, 111, .14);
            border-radius: 12px;
            background: rgba(255, 255, 255, .46);
        }

        .claim-google-account img,
        .claim-google-account__initial {
            display: grid;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            place-items: center;
            border-radius: 50%;
            object-fit: cover;
            color: #fff;
            background: #67418d;
            font-size: 15px;
            font-weight: 700;
        }

        .claim-google-account div {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 3px;
        }

        .claim-google-account strong,
        .claim-google-account span:not(.claim-google-account__initial) {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .claim-google-account strong {
            color: #342148;
            font-size: 12px;
        }

        .claim-google-account span:not(.claim-google-account__initial) {
            color: #756a80;
            font-size: 11px;
        }

        .claim-alert,
        .claim-success {
            margin-bottom: 16px;
            padding: 11px 13px;
            border-radius: 9px;
            font-size: 12px;
        }

        .claim-alert {
            border: 1px solid #e3aab2;
            color: #842029;
            background: #fff0f1;
        }

        .claim-success {
            border: 1px solid #a6d4b5;
            color: #21633b;
            background: #edf9f0;
        }

        .claim-form label {
            display: block;
            margin-bottom: 8px;
            color: #34164f;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .11em;
        }

        .claim-form__input {
            width: 100%;
            height: 50px;
            padding: 0 15px;
            border: 1px solid #b999e4;
            border-radius: 10px;
            outline: none;
            color: #332444;
            background: #f9f6fb;
            font: inherit;
            font-size: 13px;
        }

        .claim-form__input:focus {
            border-color: #7441ad;
            box-shadow: 0 0 0 3px rgba(116, 65, 173, .13);
        }

        .claim-form__hint {
            margin: 8px 0 19px;
            color: #766c80;
            font-size: 11px;
            line-height: 1.55;
        }

        .claim-form__password {
            position: relative;
            margin-bottom: 17px;
        }

        .claim-form__password .claim-form__input {
            padding-right: 95px;
        }

        .claim-password-toggle {
            position: absolute;
            top: 0;
            right: 0;
            height: 50px;
            padding: 0 13px;
            border: 0;
            color: #66418e;
            background: transparent;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .claim-form__submit {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #f5a000;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color .18s ease;
        }

        .claim-form__submit:hover {
            background: #e89300;
        }

        .claim-logout {
            margin-top: 18px;
            text-align: center;
        }

        .claim-logout button {
            padding: 5px;
            border: 0;
            color: #705d82;
            background: transparent;
            font: inherit;
            font-size: 11px;
            cursor: pointer;
        }

        .claim-logout button:hover {
            color: #351452;
            text-decoration: underline;
        }
    </style>
@endpush
