<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Navigasi halaman: ikon panah + keterangan Indonesia (bukan tulisan "Previous/Next").
        \Illuminate\Pagination\Paginator::defaultView('pagination.sipersu');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination.ringkas');

        if (config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
