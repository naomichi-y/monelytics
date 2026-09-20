<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Laravel 13 の既定は Tailwind 版のため差し替える。3 系の雛形は
        // page-item / page-link を出さず、5 では素の箇条書きとして並ぶ。
        Paginator::useBootstrapFive();

        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
