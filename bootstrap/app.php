<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        // Dibaca langsung oleh JS (document.cookie) sebagai sinyal "download ZIP
        // selesai di-generate server" — harus mentah, tidak dienkripsi.
        $middleware->encryptCookies(except: ['fileDownloadToken']);
        // Staf yang masih login lalu membuka /login (middleware guest Fortify) langsung ke
        // dashboard sesuai role. Tanpa ini Laravel mengalihkan ke "/" = login ujian siswa.
        $middleware->redirectUsersTo(
            fn () => \App\Http\Responses\LoginResponse::dashboardUrl(auth()->guard('web')->user()) ?? '/'
        );
        $middleware->alias([
            'student'     => \App\Http\Middleware\AuthStudent::class,
            'participant' => \App\Http\Middleware\AuthParticipant::class,
            'asesor'      => \App\Http\Middleware\EnsureAsesor::class,
            'admin'       => \App\Http\Middleware\EnsureAdmin::class,
            'manager'     => \App\Http\Middleware\EnsureManagerSertifikasi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();