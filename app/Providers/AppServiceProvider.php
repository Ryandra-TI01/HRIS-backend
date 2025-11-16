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
        // Set format global untuk serialisasi Carbon tanggal
        \Illuminate\Support\Carbon::serializeUsing(function (\Carbon\Carbon $carbon) {
            return $carbon->format('Y-m-d H:i:s');
        });
    }
}
