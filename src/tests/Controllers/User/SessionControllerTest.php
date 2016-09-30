<?php
namespace Tests\Summary;

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
        $this->assertGuestInaccessibleContent('GET', '/user/logout');

        $this->login();
        $this->call('GET', '/user/logout');
        $this->assertRedirectedTo('/');
        $this->assertTrue(Auth::guest());
    }
}
