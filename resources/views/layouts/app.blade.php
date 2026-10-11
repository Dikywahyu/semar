<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">


@include('layouts.partials.head')

<body>
    <!-- Preloader -->

    @include('layouts.partials.preloader')

    <div id="main-wrapper">
        @include('layouts.partials.aside')


        <div class="page-wrapper">

            @include('layouts.partials.header')



            <div class="body-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        @yield('content')

                    </div>
                </div>
            </div>

            @include('layouts.partials.customizer')
        </div>

    </div>

    <div class="dark-transparent sidebartoggler"></div>
    @include('layouts.partials.scripts')
</body>

</html>