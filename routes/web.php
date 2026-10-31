<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\PassportClientController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProdiController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\Apps\ActivityController;
use App\Http\Controllers\Apps\ActivityReportController;
use App\Http\Controllers\Apps\ActivitySessionController;
use App\Http\Controllers\Apps\ActivityTypeController;
use App\Http\Controllers\Apps\AttendanceController;
use App\Http\Controllers\Apps\ShortLinkController;
use App\Http\Controllers\Auth\AccountClaimController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\TwoFactorAuthController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Kanban\KanbanController;
use App\Http\Controllers\PklController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Capstone\CapstoneStudentController;
use App\Http\Controllers\Capstone\CapstoneCoordinatorController;
use App\Http\Controllers\Capstone\CapstoneScoreController;
use App\Http\Controllers\Capstone\CapstoneAdminController;

// PENCEGAT DARURAT: Mengintip data yang dilempar dari SIAKAD Lokal
// Route::get('/oauth/authorize', function(\Illuminate\Http\Request $request) {
//     dd([
//         'KETERANGAN' => 'Melihat data kiriman dari SIAKAD Lokal',
//         'CLIENT_ID_YANG_DIKIRIM' => $request->query('client_id'),
//         'REDIRECT_URI_TUTUAN'    => $request->query('redirect_uri'),
//         'RESPONSE_TYPE'          => $request->query('response_type'),
//         'STATE_OAUTH'            => $request->query('state'),
//         'SEMUA_PARAMETER_URL'    => $request->all()
//     ]);
// });

/*
|--------------------------------------------------------------------------
| Public Routes (Dapat Diakses Tanpa Login / Pihak Luar)
|--------------------------------------------------------------------------
*/
// Form Presensi via Scan QR Code (Peserta Internal & Tamu Eksternal)
Route::get('/presensi/{token}', [AttendanceController::class, 'publicForm'])->name('attendances.public_form');
Route::post('/presensi/{token}', [AttendanceController::class, 'publicSubmit'])->name('attendances.public_submit');

// Hal Verifikasi QR Tanda Tangan Digital Berita Acara
Route::get('/verify-report/{token}', [ActivityReportController::class, 'verifyDigitalSignature'])->name('activity-reports.verify');

Route::get('/approve-report/{token}', [ActivityReportController::class, 'publicApprovalView'])->name('activity_reports.public_approval');
Route::post('/approve-report/{token}', [ActivityReportController::class, 'publicApprovalSubmit'])->name('activity_reports.public_approval_submit');


Route::middleware(['auth'])->prefix('capstone')->name('capstone.')->group(function () {
    Route::get('file', function (\Illuminate\Http\Request $request) {
        abort_unless(auth()->check(), 403); // Pastikan user login

        $path = $request->query('path');
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->file(storage_path('app/public/' . $path));
    })->name('file.view');

    // --- ROLE: MAHASISWA ---
    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('reset-profile', [CapstoneStudentController::class, 'resetProfile'])->name('reset_profile');
        Route::get('/', [CapstoneStudentController::class, 'index'])->name('index');
        Route::post('group', [CapstoneStudentController::class, 'storeGroup'])->name('group.store');
        Route::post('member-response/{id}', [CapstoneStudentController::class, 'responseInvitation'])->name('member.response');
        Route::post('execution/{id}', [CapstoneStudentController::class, 'uploadExecution'])->name('execution.upload');
        Route::post('sidang/{id}', [CapstoneStudentController::class, 'uploadSidang'])->name('sidang.upload');
        Route::post('upload-sidang/{groupId}', [CapstoneStudentController::class, 'uploadSidang'])->name('upload.sidang');
        Route::post('invitation/{memberId}', [CapstoneStudentController::class, 'responseInvitation'])->name('invitation.response');
        Route::delete('delete-sidang/{groupId}/{fileType}', [CapstoneStudentController::class, 'deleteSidangFile'])->name('delete.sidang.file');
    });

    // --- ROLE: KOORDINATOR KEAHLIAN ---
    Route::prefix('koordinator')->name('koordinator.')->group(function () {
        Route::get('/', [CapstoneCoordinatorController::class, 'index'])->name('index');

        Route::post('/plot-lecturers/{groupId}', [CapstoneCoordinatorController::class, 'plotLecturers']);
        Route::post('/kpi-approval/{groupId}', [CapstoneCoordinatorController::class, 'updateKpiApproval']);
        Route::post('/toggle-acc/{groupId}', [CapstoneCoordinatorController::class, 'toggleAccCoordinator']);
    });

    // --- ROLE: DOSEN (PEMBIMBING & PENGUJI) ---
    Route::prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/', [CapstoneScoreController::class, 'dosenIndex'])->name('index');
        Route::post('student-scores/{groupId}', [CapstoneScoreController::class, 'storeStudentScores'])->name('scores.store');
    });

    // --- ROLE: ADMIN / KPS (MASTER & RUANGAN) ---
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [CapstoneAdminController::class, 'index'])->name('index');
        Route::post('room/{id}', [CapstoneAdminController::class, 'setExamRoom'])->name('room');

        // Master Komponen Nilai Routes
        Route::get('komponen', [CapstoneAdminController::class, 'komponenIndex'])->name('komponen.index');
        Route::post('komponen', [CapstoneAdminController::class, 'storeKomponen'])->name('komponen.store');
        Route::put('komponen/{id}', [CapstoneAdminController::class, 'updateKomponen'])->name('komponen.update');
        Route::delete('komponen/{id}', [CapstoneAdminController::class, 'destroyKomponen'])->name('komponen.destroy');

        Route::get('expertises', [CapstoneAdminController::class, 'expertiseIndex'])->name('expertises.index');
        Route::post('expertises', [CapstoneAdminController::class, 'storeExpertise'])->name('expertises.store');
        Route::put('expertises/{id}', [CapstoneAdminController::class, 'updateExpertise'])->name('expertises.update');
        Route::delete('expertises/{id}', [CapstoneAdminController::class, 'destroyExpertise'])->name('expertises.destroy');

        // Endpoint Pivot Dosen Keahlian
        Route::get('expertises/{id}/lecturers', [CapstoneAdminController::class, 'getExpertiseLecturers'])->name('expertises.lecturers.get');
        Route::post('expertises/{id}/lecturers', [CapstoneAdminController::class, 'storeExpertiseLecturer'])->name('expertises.lecturers.store');
        Route::delete('expertises/{expertiseId}/lecturers/{lecturerId}', [CapstoneAdminController::class, 'destroyExpertiseLecturer'])->name('expertises.lecturers.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated App Routes (Prefix: /apps)
|--------------------------------------------------------------------------
*/

Route::prefix('apps')->name('apps.')->group(function () {
    Route::prefix('sessions')->name('sessions.')->group(function () {
        // Menampilkan Layar QR Code Sesi untuk di-scan Peserta
        Route::get('/{session}/qr-display', [ActivitySessionController::class, 'qrDisplay'])->name('qr_display');
    });
});
Route::middleware(['auth'])->prefix('apps')->name('apps.')->group(function () {

    // 1. MASTER DATA: Tipe Kegiatan
    Route::resource('activity-types', ActivityTypeController::class);

    // 2. KEGIATAN & SESI
    Route::resource('activities', ActivityController::class);

    // Nested/Additional Route untuk Sesi Pertemuan di dalam Kegiatan
    Route::prefix('activities/{activity}')->name('activities.')->group(function () {
        Route::post('/sessions', [ActivitySessionController::class, 'store'])->name('sessions.store');
    });

    Route::prefix('sessions')->name('sessions.')->group(function () {
        Route::get('/{session}', [ActivitySessionController::class, 'show'])->name('show');
        Route::put('/{session}', [ActivitySessionController::class, 'update'])->name('update');
        Route::delete('/{session}', [ActivitySessionController::class, 'destroy'])->name('destroy');
    });

    // 3. BERITA ACARA & NOTULENSI
    Route::resource('activity-reports', ActivityReportController::class);
    Route::prefix('activity-reports')->name('activity-reports.')->group(function () {
        // Helper route untuk fetch data laporan berdasarkan activity_id
        Route::get('/by-activity/{activityId}', [ActivityReportController::class, 'getByActivity'])->name('by_activity');

        // Cetak Dokumen Berita Acara PDF
        Route::get('/{activityReport}/print-pdf', [ActivityReportController::class, 'printPdf'])->name('print_pdf');
        // Fitur Pengesahan Digital (Aprove/Reject)
        Route::post('/{activityReport}/approve', [ActivityReportController::class, 'approve'])->name('approve');
    });

    // 4. DATA PRESENSI (REKAP & MONITORING)
    Route::resource('attendances', AttendanceController::class)->only(['index', 'destroy']);
    Route::prefix('attendances')->name('attendances.')->group(function () {
        // Export Rekap Kehadiran (Excel/PDF)
        Route::get('/export/excel', [AttendanceController::class, 'exportExcel'])->name('export_excel');
        Route::get('/export/pdf', [AttendanceController::class, 'exportPdf'])->name('export_pdf');
        // Manual Input Presensi oleh Operator
        Route::post('/manual-store', [AttendanceController::class, 'manualStore'])->name('manual_store');
    });
});

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::prefix('auth')->group(function () {

    // Rute khusus GUEST (Hanya bisa diakses jika BELUM login)
    Route::middleware(['guest'])->group(function () {

        Route::get('/', [LoginController::class, 'showLoginForm']);
        Route::get('login', [LoginController::class, 'showLoginForm']);
        Route::post('login', [LoginController::class, 'login']);

        // Password Reset Request
        Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    });

    // OAuth redirect dan callback harus dapat diakses ketika sesi lokal masih aktif.
    // Callback ini yang menukar sesi login setelah Google memverifikasi pengguna.
    Route::get('google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Rute khusus AUTH (Hanya bisa diakses jika SUDAH login)
    Route::middleware(['auth'])->group(function () {
        Route::get('account/claim', [AccountClaimController::class, 'create'])
            ->middleware('2fa')
            ->name('account.claim');
        Route::post('account/claim', [AccountClaimController::class, 'store'])
            ->middleware('2fa')
            ->name('account.claim.store');
        Route::post('logout', [LogoutController::class, 'logout'])->name('logout');

        // Email Verification
        Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
        Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
            ->name('verification.verify')->middleware(['signed']);
        Route::post('email/resend', [VerificationController::class, 'resend'])
            ->name('verification.resend')->middleware(['throttle:6,1']);

        Route::post('/2fa/toggle', [UserController::class, 'toggleSecured'])->name('2fa.toggle');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('auth/2fa/setup', [TwoFactorAuthController::class, 'setup'])->name('2fa.setup');
        Route::post('auth/2fa/activate', [TwoFactorAuthController::class, 'activate'])->name('2fa.activate');
        Route::get('auth/2fa/verify', [TwoFactorAuthController::class, 'index'])->name('2fa.index');
        Route::post('auth/2fa/verify', [TwoFactorAuthController::class, 'verify'])->name('2fa.verify');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('pkl')->name('pkl.')->group(function () {
        Route::get('/route-map', [PklController::class, 'routeMap'])->name('route-map');
        Route::post('/route-map/{pengajuan}/upload/{tahap}', [PklController::class, 'routeMapUpload'])->whereIn('tahap', ['1', '2', '3'])->name('route-map.upload');
        Route::post('/route-map/{pengajuan}/verify', [PklController::class, 'routeMapVerify'])->name('route-map.verify');
        Route::post('/route-map/{pengajuan}/acc', [PklController::class, 'routeMapAcc'])->name('route-map.acc');
        Route::post('/route-map/{pengajuan}/schedule', [PklController::class, 'routeMapSchedule'])->name('route-map.schedule.set');
        Route::post('/route-map/{pengajuan}/propose-schedule', [PklController::class, 'routeMapProposeSchedule'])->name('route-map.schedule.propose');
        Route::post('/route-map/{pengajuan}/grade', [PklController::class, 'routeMapGrade'])->name('route-map.grade');
        Route::post('/route-map/{pengajuan}/revision/validate', [PklController::class, 'routeMapValidateRevision'])->name('route-map.revision.validate');
        Route::post('/route-map/{pengajuan}/complete', [PklController::class, 'routeMapComplete'])->name('route-map.complete');
        Route::get('/route-map/documents/{dokumen}', [PklController::class, 'routeMapDocument'])->name('route-map.document');
        Route::get('/mahasiswa', [PklController::class, 'mahasiswaIndex'])->name('mahasiswa.index');
        Route::post('/mahasiswa', [PklController::class, 'mahasiswaStore'])->name('mahasiswa.store');
        Route::put('/mahasiswa/{pengajuan}', [PklController::class, 'mahasiswaUpdate'])->name('mahasiswa.update');
        Route::post('/mahasiswa/{pengajuan}/upload-revisi', [PklController::class, 'uploadRevisi'])->name('mahasiswa.upload-revisi');
        Route::post('/mahasiswa/{pengajuan}/plot-pembimbing', [PklController::class, 'mahasiswaPlotPembimbing'])->name('mahasiswa.plot-pembimbing');
        Route::post('/mahasiswa/ajukan-ulang-revisi/{pengajuan}', [PklController::class, 'ajukanUlangRevisi'])->name('mahasiswa.ajukan-ulang-revisi');
        Route::post('/mahasiswa/selesai-pelaksanaan/{pengajuan}', [PklController::class, 'selesaiPelaksanaan'])->name('mahasiswa.selesai-pelaksanaan');
        Route::post('/mahasiswa/upload-berkas-satuan/{pengajuan}', [PklController::class, 'uploadBerkasSatuan'])->name('mahasiswa.upload-berkas-satuan');
        Route::post('/mahasiswa/{pengajuan}/usulkan-jadwal', [PklController::class, 'mahasiswaProposeSchedule'])->name('mahasiswa.usulkan-jadwal');
        Route::post('/mahasiswa/{pengajuan}/upload-dokumen', [PklController::class, 'uploadTahapDocument'])->name('mahasiswa.upload-dokumen');

        Route::get('/dosen', [PklController::class, 'dosenIndex'])->name('dosen.index');
        Route::get('/dosen/bimbingan/{pengajuan}', [PklController::class, 'dosenDetail'])->name('dosen.bimbingan.detail');
        Route::get('/dosen/komponen-penilaian/{pengajuan}', [PklController::class, 'dosenComponents'])->name('dosen.komponen-penilaian');
        Route::post('/dosen/penilaian/{pengajuan}', [PklController::class, 'dosenSaveGrades'])->name('dosen.penilaian');

        Route::get('/admin', [PklController::class, 'adminIndex'])->name('admin.index');
        Route::post('/admin/pengajuan', [PklController::class, 'adminStore'])->name('admin.pengajuan.store');
        Route::get('/admin/pengajuan/{pengajuan}/edit', [PklController::class, 'adminEdit'])->name('admin.pengajuan.edit');
        Route::post('/admin/pengajuan/{pengajuan}/update', [PklController::class, 'adminUpdate'])->name('admin.pengajuan.update');
        Route::post('/admin/verifikasi-berkas-seminar/{pengajuan}', [PklController::class, 'verifyDocuments'])->name('admin.verifikasi-berkas');
        Route::post('/admin/terapkan-usulan-mahasiswa/{pengajuan}', [PklController::class, 'adminApplyStudentProposals'])->name('admin.terapkan-usulan-mahasiswa');
        Route::post('/admin/set-jadwal-penguji/{pengajuan}', [PklController::class, 'setSchedule'])->name('admin.jadwal');
        Route::get('/admin/penilaian-seminar/{pengajuan}', [PklController::class, 'adminGrades'])->name('admin.penilaian.show');
        Route::post('/admin/penilaian-seminar/{pengajuan}', [PklController::class, 'saveAdminGrades'])->name('admin.penilaian.store');

        Route::get('/admin/komponen-penilaian', [PklController::class, 'componentsIndex'])->name('admin.komponen.index');
        Route::post('/admin/komponen-penilaian', [PklController::class, 'componentStore'])->name('admin.komponen.store');
        Route::put('/admin/komponen-penilaian/{komponen}', [PklController::class, 'componentUpdate'])->name('admin.komponen.update');
        Route::delete('/admin/komponen-penilaian/{komponen}', [PklController::class, 'componentDestroy'])->name('admin.komponen.destroy');

        Route::get('/view-file/{path}', [PklController::class, 'viewFile'])->where('path', '.*')->name('file');
    });

    Route::get('admin/master/dosens/export', [DosenController::class, 'export'])->name('master.dosens.export');
    Route::post('admin/master/dosens/generate-users', [DosenController::class, 'generateUsers'])->name('master.dosens.generate-users');
    Route::post('admin/master/dosens/{dosen}/reset-account-binding', [DosenController::class, 'resetAccountBinding'])->name('master.dosens.reset-account-binding');
    Route::post('admin/master/dosens/import', [DosenController::class, 'import'])->name('master.dosens.import');
    Route::resource('admin/master/dosens', DosenController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('master.dosens');
    Route::get('admin/master/mahasiswas/export', [MahasiswaController::class, 'export'])->name('master.mahasiswas.export');
    Route::post('admin/master/mahasiswas/generate-users', [MahasiswaController::class, 'generateUsers'])->name('master.mahasiswas.generate-users');
    Route::post('admin/master/mahasiswas/{mahasiswa}/reset-account-binding', [MahasiswaController::class, 'resetAccountBinding'])->name('master.mahasiswas.reset-account-binding');
    Route::post('admin/master/mahasiswas/import', [MahasiswaController::class, 'import'])->name('master.mahasiswas.import');
    Route::resource('admin/master/mahasiswas', MahasiswaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('master.mahasiswas');
    Route::get('admin/master/prodis/options', [ProdiController::class, 'options'])->name('master.prodis.options');
    Route::resource('admin/master/prodis', ProdiController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('master.prodis');
    Route::get('admin/master/semesters/options', [SemesterController::class, 'options'])->name('master.semesters.options');
    Route::resource('admin/master/semesters', SemesterController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('master.semesters');

    Route::prefix('kanban')->name('kanban.')->group(function () {
        // 1. Rute Utama & Manajemen Board (Query String)
        Route::get('/', [KanbanController::class, 'index'])->name('index');
        Route::post('/board/store', [KanbanController::class, 'storeBoard'])->name('board.store');
        Route::delete('/board/delete/{id}', [KanbanController::class, 'deleteBoard'])->name('board.delete');

        // 2. TAMBAHAN BARIS BARU: Rute Fitur Kolaborasi (Share Board)
        // Mengambil daftar user/dosen untuk dipilih di modal share
        Route::get('/board/{id}/users', [KanbanController::class, 'getAvailableUsers'])->name('board.users');
        // Menyimpan kolaborator baru ke tabel pivot
        Route::post('/board/share', [KanbanController::class, 'shareBoard'])->name('board.share');

        // Fitur Kolaborasi Lanjutan
        Route::get('/board/{id}/members', [KanbanController::class, 'getBoardMembers'])->name('board.members');

        // PERBAIKAN DI SINI: Tambahkan ->name('member.update')
        Route::post('/board/member/update', [KanbanController::class, 'updateMemberRole'])->name('member.update');

        // PERBAIKAN DI SINI: Tambahkan ->name('member.remove')
        Route::delete('/board/member/remove/{board_id}/{user_id}', [KanbanController::class, 'removeMember'])->name('member.remove');

        // 3. Rute AJAX Manajemen Data & Kartu Tugas (Tetap Sama)
        Route::get('/board-data/{id}', [KanbanController::class, 'getBoardData'])->name('data');
        Route::post('/task/store', [KanbanController::class, 'storeTask'])->name('task.store');
        Route::post('/task/update-position', [KanbanController::class, 'updateTaskPosition'])->name('task.updatePosition');
        Route::put('/task/update/{id}', [KanbanController::class, 'updateTask'])->name('task.update');
        Route::delete('/task/delete/{id}', [KanbanController::class, 'deleteTask'])->name('task.delete');
    });
});

// Rute yang membutuhkan Login (Untuk Manajemen Pembuatan Link)
Route::middleware(['auth'])->group(function () {
    Route::resource('short-links', ShortLinkController::class);
    Route::get('short-links/{id}/qr-data', [ShortLinkController::class, 'getQrData'])->name('short-links.qr');
    Route::post('/qr/generate-instant', [ShortLinkController::class, 'generateInstantQr'])->name('qr.generate-instant');
});

Route::prefix('apps')->name('apps.')->group(function () {
    // Resource route otomatis mencakup 7 method CRUD (index, create, store, show, edit, update, destroy)
    Route::resource('activity-types', ActivityTypeController::class);
});

// Rute Umum/Publik diletakkan di luar middleware auth (Taruh di baris paling bawah file web.php)
// Skema URL: iam.cwcu.ac.id/s/{kode}
Route::get('/s/{code}', [ShortLinkController::class, 'handleRedirect'])->name('shortlink.redirect');

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', '2fa'])->name('dashboard');

// Halaman Utama setelah lolos semua gerbang keamanan
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', '2fa'])->name('dashboard');

// Route untuk Tampilan (Halaman Kosong)
Route::view('/admin/users/data', 'admin.users.index')->name('admin.users.data');

Route::get('/check-new-notifications', function () {
    $user = Auth::user();
    $unreadCount = $user->unreadNotifications->count();

    $html = view('pkl.partials.notification-list')->render();

    return response()->json([
        'unread_count' => $unreadCount,
        'notification_html' => $html,
    ]);
})->middleware('auth');

// Masukkan ke dalam middleware admin agar aman
Route::prefix('admin')->middleware(['auth', 'verified', '2fa'])->group(function () {

    // Resource Utama User
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');

    // Modul Kredensial (Brankas Password)
    Route::get('/users/{hashid}/credential', [UserController::class, 'showCredential'])->name('admin.user.credential');
    Route::post('/users/{hashid}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset');

    // Modul Role (Spatie)
    Route::get('/users/{hashid}/roles', [UserController::class, 'getRoles'])->name('admin.users.get_roles');
    Route::post('/users/{hashid}/roles', [UserController::class, 'updateRoles'])->name('admin.users.update_roles');

    // (Opsional) Modul Permission Spesifik
    Route::get('/users/{hashid}/permissions', [UserController::class, 'getPermissions'])->name('admin.users.get_permissions');
    Route::post('/users/{hashid}/permissions', [UserController::class, 'updatePermissions'])->name('admin.users.update_permissions');

    // logs Role (Spatie)
    Route::get('/users/{hashid}/logs', [UserController::class, 'getUserLogs'])->name('admin.users.logs');

    // Route untuk CRUD API Client
    // Route::resource('api-clients', ApiClientController::class);
    // // Route tambahan untuk toggle status aktif/nonaktif secara cepat
    // Route::post('api-clients/{id}/toggle', [ApiClientController::class, 'toggleStatus'])->name('api-clients.toggle');
    Route::post('system/clear-cache', [PassportClientController::class, 'clearSystemCache'])->name('admin.clear-cache');
    Route::post('api-clients/{id}/regenerate-secret', [PassportClientController::class, 'regenerateSecret']);
    Route::resource('api-clients', PassportClientController::class)->except(['create', 'edit']);
});

// Rute Pengaturan Akun Bersifat Universal
Route::middleware(['auth'])->prefix('account')->group(function () {
    Route::put('/update-profile', [AccountController::class, 'updateProfile']);
    Route::put('/update-password', [AccountController::class, 'updatePassword']);
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::resource('roles', RoleController::class);
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::resource('permissions', PermissionController::class);
});

Route::get('/mark-notifications-read', function () {
    Auth::user()->unreadNotifications->markAsRead();

    return response()->json(['status' => 'success']);
});
