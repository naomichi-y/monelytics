<?php
namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    private ?TestResponse $lastResponse = null;

    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase();
        $this->seed(\Seeds\TestSeeder::class);
    }

    /**
     * スキーマの作り直しはスイート全体で 1 回だけ行う。
     */
    protected function setupDatabase(): void
    {
        static $initialized = false;

        if (!$initialized) {
            Artisan::call('migrate:refresh');

            $initialized = true;
        }
    }

    protected function getUser(): User
    {
        return User::find(1);
    }

    protected function login(): void
    {
        $this->be($this->getUser());
    }

    protected function logout(): void
    {
        Auth::logout();
    }

    /**
     * 直近のレスポンスを保持し、assertRedirectedTo() から参照できるようにする。
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        return $this->lastResponse = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * BrowserKit 時代の API。Laravel 5.4 で削除されたが、呼び出し側を
     * 変えずに済むよう直近のレスポンスに対する assertRedirect として残す。
     */
    protected function assertRedirectedTo(string $uri): void
    {
        $this->assertNotNull($this->lastResponse, 'call() が先に実行されていない');
        $this->lastResponse->assertRedirect($uri);
    }

    protected function assertGuestAccessibleContent(...$args): void
    {
        $this->call(...$args)->assertOk();
    }

    protected function assertGuestInaccessibleContent(...$args): void
    {
        $this->call(...$args)->assertRedirect('/user/login');
    }

    protected function assertUserAccessibleContent(...$args): void
    {
        $this->login();
        $this->call(...$args)->assertOk();
        $this->logout();
    }

    protected function assertUserInaccessibleContent(...$args): void
    {
        $this->login();
        $this->call(...$args)->assertRedirect('/dashboard');
        $this->logout();
    }

    protected function assertAnyAccessibleContent(...$args): void
    {
        $this->assertGuestAccessibleContent(...$args);
        $this->assertUserAccessibleContent(...$args);
    }

    protected function assertGuestOnlyContent(...$args): void
    {
        $this->assertGuestAccessibleContent(...$args);
        $this->assertUserInaccessibleContent(...$args);
    }

    protected function assertUserOnlyContent(...$args): void
    {
        $this->assertGuestInaccessibleContent(...$args);
        $this->assertUserAccessibleContent(...$args);
    }

    protected function assertValidAjaxResponse(...$args): void
    {
        $response = $this->call(...$args);
        $response->assertOk();

        $result = json_decode($response->getContent());

        $this->assertTrue(
            empty($result->error),
            'AJAX レスポンスにエラーが含まれている: ' . $response->getContent()
        );
    }
}
