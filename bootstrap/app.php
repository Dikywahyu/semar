<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            '2fa' => \App\Http\Middleware\Google2FAMiddleware::class,
            'check.api.host' => \App\Http\Middleware\CheckApiHost::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\UpdateUserLastSeen::class,
        ]);

        // JANGAN panggil config() di sini.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $exception, \Illuminate\Http\Request $request) {
            $message = 'Ukuran seluruh data melebihi batas server. Unggah setiap dokumen satu per satu atau naikkan post_max_size di konfigurasi PHP.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 413);
            }

            return response()->view('errors.413', ['message' => $message], 413);
        });
    })->create();
