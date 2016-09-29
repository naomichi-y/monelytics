<?php
namespace Tests;

use Artisan;
use Auth;
use Route;

use App\Models\User;

class TestCase extends \Illuminate\Foundation\Testing\TestCase {

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app;
    }

    public function setup()
    {
        parent::setup();

        \Session::start();

        $this->setupDatabase();
        $this->seed('Seeds\TestSeeder');
    }

    public function setupDatabase()
    {
        static $initialized = false;

        if (!$initialized) {
            Artisan::call('migrate:refresh');

            $initialized = true;
        }
    }

    public function getUser()
    {
        return User::find(1);
    }

    public function login()
    {
        $this->be($this->getUser());
    }

    public function logout()
    {
        Auth::logout();
    }

    public function assertGuestAccessibleContent()
    {
        call_user_func_array(array($this, 'call'), func_get_args());
        $this->assertResponseOk();
    }

    public function assertGuestInaccessibleContent()
    {
        call_user_func_array(array($this, 'call'), func_get_args());
        $this->assertRedirectedTo('/user/login');
    }

    public function assertUserAccessibleContent()
    {
        $this->login();
        call_user_func_array(array($this, 'call'), func_get_args());
        $this->assertResponseOk();
        Auth::logout();
    }

    public function assertUserInaccessibleContent()
    {
        $this->login();
        call_user_func_array(array($this, 'call'), func_get_args());
        $this->assertRedirectedTo('/dashboard');
    }

    public function assertAnyAccessibleContent()
    {
        call_user_func_array(array($this, 'assertGuestAccessibleContent'), func_get_args());
        call_user_func_array(array($this, 'assertUserAccessibleContent'), func_get_args());
    }

    public function assertGuestOnlyContent()
    {
        call_user_func_array(array($this, 'assertGuestAccessibleContent'), func_get_args());
        call_user_func_array(array($this, 'assertUserInaccessibleContent'), func_get_args());
    }

    public function assertUserOnlyContent()
    {
        call_user_func_array(array($this, 'assertGuestInaccessibleContent'), func_get_args());
        call_user_func_array(array($this, 'assertUserAccessibleContent'), func_get_args());
    }

    public function assertValidAjaxResponse()
    {
        $response = call_user_func_array(array($this, 'call'), func_get_args());
        $result = json_decode($response->getContent());

        return empty($result->error);
    }
}
