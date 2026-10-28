<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Fase 1: seluruh aplikasi hanya untuk jaringan lokal (AksesJalur), lalu pembatas banjir akses per IP (BatasiBanjir).
        $middleware->prepend([\App\Http\Middleware\AksesJalur::class, \App\Http\Middleware\BatasiBanjir::class]);
        $middleware->alias(['role' => \Spatie\Permission\Middleware\RoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Semua galat di bawah /api dibalas JSON (bukan halaman HTML / pengalihan ke login).
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
