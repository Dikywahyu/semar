@extends('layouts.auth')

@section('title', 'Lupa Password - Integrated Academic Management')

@section('content')
<div class="forny-form">
    <div class="mb-8 text-center forny-logo">
        <img src="{{ asset('templates/img/logo-03.svg') }}">
    </div>
    
    <div class="text-center">
        <h4>Reset Password</h4>
        <p class="mb-10">Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang password.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 text-center">
            <small>{{ session('status') }}</small>
        </div>
    @endif

    @if ($errors->has('email'))
        <div class="alert alert-danger py-2 text-center">
            <small>{{ $errors->first('email') }}</small>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </span>
                </div>
                <input required class="form-control" name="email" type="email" placeholder="Alamat Email" value="{{ old('email') }}">
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-primary btn-block">Kirim Tautan Reset</button>
        </div>
    </form>

    <div class="text-center mt-8">
        <a href="{{ route('login') }}" class="text-muted"><small>Kembali ke Halaman Login</small></a>
    </div>
</div>
@endsection