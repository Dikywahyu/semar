<aside class="left-sidebar with-vertical">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ url('/dashboard') }}" class="text-nowrap logo-img">
                <img src="{{ asset('dist/assets/images/logos/dark-logo.svg') }}" class="dark-logo" height="50px" alt="Logo-Dark" />
            </a>
            <a href="javascript:void(0)" class="sidebartoggler ms-auto text-decoration-none fs-5 d-block d-xl-none">
                <i class="ti ti-x"></i>
            </a>
        </div>
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul id="sidebarnav">
                <li class="nav-small-cap"><span class="hide-menu">Home</span></li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ url('/dashboard') }}">
                        <span><iconify-icon icon="solar:widget-add-line-duotone"></iconify-icon></span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
                {{-- Tambahkan menu lainnya di sini --}}
            </ul>
        </nav>
    </div>
</aside>