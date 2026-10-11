<aside class="left-sidebar with-vertical">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ url('/dashboard') }}" class="text-nowrap logo-img">
                <img src="../../assets/UNITRI.png" class="dark-logo" height="50px" alt="Logo-Dark" />
                <img src="../../assets/UNITRI.png" class="light-logo" height="50px" alt="Logo-light" />
            </a>
            <a href="javascript:void(0)" class="sidebartoggler ms-auto text-decoration-none fs-5 d-block d-xl-none">
                <i class="ti ti-x"></i>
            </a>
        </div>

        {{-- Menggunakan collect() agar aman untuk Array maupun Collection --}}
        @php
        $menuCollection = collect($menus ?? []);
        @endphp

        @if($menuCollection->isNotEmpty())
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul id="sidebarnav">
                @foreach($menuCollection->groupBy('category') as $category => $items)
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu text-uppercase fw-bold fs-2">{{ $category ?: 'General' }}</span>
                </li>

                @foreach($items as $menu)
                @php
                // Memastikan akses attribute aman baik format Array ['key'] atau Object ->key
                $menu = (array) $menu;
                @endphp
                <li class="sidebar-item {{ ($menu['active'] ?? false) ? 'selected' : '' }}">
                    <a class="sidebar-link {{ $menu['class'] ?? '' }} {{ ($menu['active'] ?? false) ? 'active' : '' }}"
                        href="{{ $menu['url'] ?? '#' }}"
                        aria-expanded="false">
                        <span>
                            @if(str_contains($menu['icon'] ?? '', ':'))
                            <iconify-icon icon="{{ $menu['icon'] }}" class="fs-6"></iconify-icon>
                            @else
                            <i class="{{ $menu['icon'] ?? 'ti ti-circle' }}"></i>
                            @endif
                        </span>
                        <span class="hide-menu">{{ $menu['title'] ?? 'Untitled' }}</span>
                    </a>
                </li>
                @endforeach
                @endforeach
            </ul>
        </nav>
        @else
        <div class="p-3 text-center text-muted fs-2">
            Tidak ada menu yang tersedia.
        </div>
        @endif


        <div class="fixed-profile p-3 mx-4 mb-2 bg-secondary-subtle rounded mt-3">
            <div class="hstack gap-3">
                <div class="john-img">
                    @if(Auth::user()?->avatar)
                    <img src="{{ Auth::user()->avatar }}" class="rounded-circle" width="40" height="40" alt="user-img" />
                    @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}&background=0085db&color=fff" class="rounded-circle" width="40" height="40" alt="user-img" />
                    @endif
                </div>
                <div class="john-title">
                    <h6 class="mb-0 fs-3 fw-semibold">{{ Auth::user()->name ?? 'User' }}</h6>
                    <span class="fs-2 text-muted">{{ Auth::user()?->getRoleNames()?->first() ?? 'User' }}</span>
                </div>
                <button class="border-0 bg-transparent text-primary ms-auto"
                    type="button"
                    aria-label="logout"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-title="Logout"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="ti ti-power fs-6"></i>
                </button>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</aside>