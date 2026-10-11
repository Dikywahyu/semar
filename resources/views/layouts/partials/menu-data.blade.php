<ul id="sidebarnav" class="sidebar-nav">
    @php
        $currentCategory = null;
    @endphp

    @foreach($menus as $menu)
        {{-- Rendring Header Kategori jika berubah --}}
        @if(isset($menu['category']) && $menu['category'] !== $currentCategory)
            @php $currentCategory = $menu['category']; @endphp
            <li class="nav-small-cap">
                <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                <span class="hide-menu text-uppercase fw-bold fs-2">{{ $currentCategory }}</span>
            </li>
        @endif

        {{-- Render Item Menu --}}
        <li class="sidebar-item {{ ($menu['active'] ?? false) ? 'active' : '' }}">
            <a class="sidebar-link {{ $menu['class'] ?? '' }}" 
               href="{{ $menu['url'] }}" 
               aria-expanded="false">
                
                @if(str_contains($menu['icon'] ?? '', ':'))
                    <iconify-icon icon="{{ $menu['icon'] }}" class="fs-6"></iconify-icon>
                @else
                    <i class="{{ $menu['icon'] ?? 'ti ti-circle' }}"></i>
                @endif
                
                <span class="hide-menu">{{ $menu['title'] }}</span>
            </a>
        </li>
    @endforeach
</ul>