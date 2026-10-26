@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mx-auto card border-0 shadow-sm rounded-4 p-4 text-center" style="max-width: 640px">
        <div class="display-5 text-warning mb-2"><i class="ti ti-file-alert"></i></div>
        <h1 class="h4 fw-bold">Ukuran unggahan terlalu besar</h1>
        <p class="text-secondary mb-4">{{ $message }}</p>
        <a href="{{ url()->previous() }}" class="btn btn-primary">Kembali dan coba lagi</a>
    </div>
</div>
@endsection
