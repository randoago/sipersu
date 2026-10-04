<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Batasi akses: internet hanya /v/*, selain itu hanya jaringan lokal (lihat HanyaJaringanLokal).
        $middleware->prepend(\App\Http\Middleware\HanyaJaringanLokal::class);
        $middleware->alias(['role' => \Spatie\Permission\Middleware\RoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
