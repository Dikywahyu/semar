@forelse(auth()->user()->unreadNotifications as $notification)
<a href="{{ $notification->data['url'] }}" class="py-6 px-7 d-flex align-items-center dropdown-item">
    <span class="me-3">
        @php
        $imgUrl = $notification->data['image'] ?? 'user-1.jpg';
        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
        $imgUrl = asset($imgUrl);
        }
        @endphp
        <img src="{{ $imgUrl }}" alt="user" class="rounded-circle" width="48" height="48" />
    </span>
    <div class="w-100">
        <h6 class="mb-1 fw-semibold lh-base">{{ $notification->data['title'] }}</h6>
        <span class="fs-2 d-block text-body-secondary">{{ $notification->data['message'] }}</span>
        <small class="text-muted fs-1">{{ $notification->created_at->diffForHumans() }}</small>
    </div>
</a>
@empty
<div class="py-6 px-7 text-center">
    <p class="mb-0 fs-2">Tidak ada notifikasi baru</p>
</div>
@endforelse