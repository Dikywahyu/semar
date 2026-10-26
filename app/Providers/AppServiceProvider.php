<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\View;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        \Carbon\Carbon::setLocale('id');
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        if ($this->app->runningInConsole()) {
            // Daftarkan folder migrasi tambahan (Kanban)
            $this->loadMigrationsFrom(database_path('migrations/kanban'));
            $this->loadMigrationsFrom(database_path('migrations/apps'));
            $this->loadMigrationsFrom(database_path('migrations/activity'));
            $this->loadMigrationsFrom(database_path('migrations/Pkl'));
        }

        $passportViewsPath = base_path('vendor/laravel/passport/resources/views');
        if (is_dir($passportViewsPath)) {
            $this->loadViewsFrom($passportViewsPath, 'passport');
        }

        // 1. DAFTARKAN MODEL KUSTOM DI SINI (Cukup Satu Kali Saja)
        \Laravel\Passport\Passport::useClientModel(\App\Models\PassportClient::class);

        // 2. IKAT RESPONSE INTERFACE PASSPORT
        $this->app->bind(
            \Laravel\Passport\Contracts\AuthorizationViewResponse::class,
            \App\Http\Responses\SSOAuthorizeResponse::class
        );

        View::composer(['layouts.partials.*', 'layouts.parsial.*', 'layouts.partials.aside', 'layouts.partials.header'], function ($view) {
            $rawMenus = [
                [
                    'title' => 'Dashboard',
                    'icon' => 'solar:home-line-duotone',
                    'url' => url('/dashboard'),
                    'category' => 'Home',
                    'apps_menu' => true
                ],
                [
                    'title' => 'PKL Mahasiswa',
                    'icon' => 'solar:case-round-line-duotone',
                    'url' => route('pkl.mahasiswa.index'),
                    'category' => 'PKL',
                    'apps_menu' => true,
                    'active' => request()->is('pkl/mahasiswa*'),
                    'roles' => ['Super Admin', 'mahasiswa', 'Mahasiswa'],
                ],
                [
                    'title' => 'PKL Dosen',
                    'icon' => 'solar:case-minimalistic-line-duotone',
                    'url' => route('pkl.dosen.index'),
                    'category' => 'PKL',
                    'apps_menu' => true,
                    'active' => request()->is('pkl/dosen*'),
                    'roles' => ['Super Admin', 'dosen_pembimbing', 'dosen_penguji', 'dosen', 'Dosen', 'DosenLB', 'Guru Besar'],
                ],
                [
                    'title' => 'Monitoring PKL',
                    'icon' => 'solar:clipboard-list-line-duotone',
                    'url' => route('pkl.admin.index'),
                    'category' => 'PKL',
                    'apps_menu' => true,
                    'active' => request()->is('pkl/admin*'),
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ],
                ],

                // --- CATEGORY: PRESENSI & KEGIATAN ---
                [
                    'title' => 'Kegiatan & Sesi',
                    'icon' => 'solar:calendar-mark-line-duotone',
                    'url' => route('apps.activities.index'), // Mengarah ke route name apps.activities.index
                    'category' => 'Presensi & Kegiatan',
                    'apps_menu' => true,
                    'active' => request()->is('apps/activities*'),
                    'permission' => 'activity-list'
                ],

                // --- CATEGORY: MASTER DATA ---
                [
                    'title' => 'Tipe Kegiatan',
                    'icon' => 'solar:widget-3-line-duotone',
                    'url' => route('apps.activity-types.index'),
                    'category' => 'Master Data',
                    'apps_menu' => true,
                    'active' => request()->is('apps/activity-types*'),
                    'permission' => 'activity-type-list'
                ],
                [
                    'title' => 'Data Dosen',
                    'icon' => 'solar:user-id-line-duotone',
                    'url' => route('master.dosens.index'),
                    'category' => 'Master Data',
                    'apps_menu' => true,
                    'active' => request()->is('admin/master/dosens*'),
                    'permission' => 'academic-edit'
                ],
                [
                    'title' => 'Data Mahasiswa',
                    'icon' => 'solar:users-group-rounded-line-duotone',
                    'url' => route('master.mahasiswas.index'),
                    'category' => 'Master Data',
                    'apps_menu' => true,
                    'active' => request()->is('admin/master/mahasiswas*'),
                    'permission' => 'student-edit',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ],
                ],
                [
                    'title' => 'Program Studi',
                    'icon' => 'solar:square-academic-cap-2-line-duotone',
                    'url' => route('master.prodis.index'),
                    'category' => 'Master Data',
                    'apps_menu' => true,
                    'active' => request()->is('admin/master/prodis*'),
                    'permission' => 'academic-edit',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ],
                ],
                [
                    'title' => 'Semester Akademik',
                    'icon' => 'solar:calendar-line-duotone',
                    'url' => route('master.semesters.index'),
                    'category' => 'Master Data',
                    'apps_menu' => true,
                    'active' => request()->is('admin/master/semesters*'),
                    'permission' => 'academic-edit',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ],
                ],

                // --- CATEGORY: ADMINISTRATOR ---
                [
                    'title' => 'Manajemen User',
                    'icon' => 'solar:users-group-two-rounded-line-duotone',
                    'url' => route('admin.users.data'),
                    'category' => 'Administrator',
                    'apps_menu' => true,
                    'active' => request()->is('admin/users/data'),
                    'permission' => 'user-list',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ], // <-- Nama permission Spatie / Gate Anda
                ],
                [
                    'title' => 'User Roles',
                    'icon' => 'solar:user-speak-line-duotone',
                    'url' => url('/admin/roles'),
                    'category' => 'Administrator',
                    'apps_menu' => false,
                    'active' => request()->is('admin/roles*'),
                    'permission' => 'role-list',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ], // <-- Hanya user dengan permission ini yang bisa melihat
                ],
                [
                    'title' => 'Permissions',
                    'icon' => 'solar:shield-keyhole-line-duotone',
                    'url' => url('/admin/permissions'),
                    'category' => 'Administrator',
                    'apps_menu' => false,
                    'active' => request()->is('admin/permissions*'),
                    'permission' => 'role-list',
                    'roles_or_permission' => [
                        'roles' => ['Super Admin', 'BAAK', 'Akademik', 'Kemahasiswaan', 'Kaprodi', 'Kajur'],
                        'permission' => 'academic-edit',
                    ],
                ],
                [
                    'title' => 'API Client',
                    'icon' => 'solar:code-square-line-duotone',
                    'url' => url('/admin/api-clients'),
                    'category' => 'Administrator',
                    'apps_menu' => false,
                    'active' => request()->is('admin/api-clients*'),
                    'permission' => 'api-list'
                ],
                [
                    'title' => 'Clear Cache',
                    'icon' => 'solar:refresh-square-line-duotone',
                    'url' => 'javascript:void(0)', // Di-set kosong agar tidak pindah halaman
                    'category' => 'Administrator',
                    'apps_menu' => false,
                    'active' => false,
                    'permission' => 'api-list', // Hanya bisa diakses oleh level Admin/Super Admin
                    'class' => 'btn-trigger-clear-cache' // Kita beri penanda class khusus untuk jQuery
                ],
            ];

            /** @var \App\Models\User|null $user */
            $user = request()->user(); // Bisa juga menggunakan \Illuminate\Support\Facades\Auth::user()

            $menus = array_filter($rawMenus, function ($menu) use ($user) {
                if (isset($menu['roles_or_permission'])) {
                    $access = $menu['roles_or_permission'];
                    return $user && (
                        $user->hasAnyRole($access['roles'] ?? [])
                        || $user->can($access['permission'] ?? '')
                    );
                }

                if (isset($menu['roles']) && (!$user || !$user->hasAnyRole($menu['roles']))) {
                    return false;
                }

                // Jika menu tidak mendefinisikan key 'permission', tampilkan untuk semua user
                if (!isset($menu['permission'])) {
                    return true;
                }

                // Jika user memiliki permission yang sesuai, loloskan menu ini
                return $user && $user->can($menu['permission']);
            });

            // Kirim data menu yang sudah difilter ke view
            $view->with('menus', $menus);
        });


        config(['google2fa.view' => 'auth.2fa.index']);
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
