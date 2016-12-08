<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
        \Validator::resolver(function($translator, $data, $rules, $messages) {
            return new \App\Validators\NotExistsValidator($translator, $data, $rules, $messages);
        });
    }

    /**
     * @return void
     */
    public function register()
    {}
}
