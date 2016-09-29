<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        require base_path() . '/app/Http/macros.php';
    }

    /**
     * @return void
     */
    public function register()
    {}
}
