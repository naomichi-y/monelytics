<?php
namespace Tests\Controllers\User;

use Auth;

use Tests\TestCase;

class SessionControllerTest extends TestCase {
    public function testGetLogin()
    {
        $this->assertGuestAccessibleContent('GET', '/user/login');
    }

    public function testPostLogin()
    {
        $params = [
            'email' => 'test@monelytics.me',
            'password' => 'testtest'
        ];

        $this->call('POST', '/user/login', $params);
        $this->assertRedirectedTo('/dashboard');
        $this->assertTrue(Auth::check());
    }

    public function testLogout()
    {
        $this->assertGuestInaccessibleContent('POST', '/user/logout');

        $this->login();
        $this->call('POST', '/user/logout');
        $this->assertRedirectedTo('/');
        $this->assertTrue(Auth::guest());
    }

    /**
     * GET では受け付けない。受け付けると <img src="/user/logout"> を踏ませる
     * だけで他人をログアウトさせられる。
     */
    public function testLogoutRejectsGet()
    {
        $this->login();
        $this->call('GET', '/user/logout')->assertMethodNotAllowed();
        $this->assertTrue(Auth::check(), 'GET でログアウトできてしまった');
        $this->logout();
    }
}
