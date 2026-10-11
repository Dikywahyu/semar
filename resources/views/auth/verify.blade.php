@extends('layouts.auth')

@section('title', 'Verifikasi Email - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="../../assets/UNITRI.png">
    </div>

    <div class="text-center">
        <h4>Verifikasi Email Anda</h4>
        <p class="mb-10">Tautan verifikasi baru telah dikirim ke alamat email Anda.</p>
    </div>

    @if (session('resent'))
    <div class="alert alert-success py-2 text-center">
        <small>Link verifikasi baru berhasil dikirim!</small>
    </div>
    @endif

    <div class="text-center">
        <p>Sebelum melanjutkan, silakan periksa email Anda untuk tautan verifikasi. Jika Anda tidak menerima email tersebut, silakan klik tombol di bawah untuk meminta tautan baru.</p>
    </div>

    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <div class="mt-6">
            <button type="submit" class="btn btn-primary btn-block">Kirim Ulang Verifikasi</button>
        </div>
    </form>

    <div class="text-center mt-8">
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="text-muted">
            <small>Gunakan akun lain? Keluar</small>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>
@endsection