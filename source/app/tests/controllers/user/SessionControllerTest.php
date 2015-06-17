<?php
class SessionControllerTest extends TestCase {
  public function testGetLogin()
  {
    $this->assertGuestAccessibleContent('GET', '/user/login');
  }

  public function testPostLogin()
  {
    $params = array(
      'email' => 'test@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user/login', $params);
    $this->assertRedirectedTo('/dashboard');
    $this->assertTrue(Auth::check());
  }

  public function testLogot()
  {
    $this->assertGuestInaccessibleContent('GET', '/user/logout');

    $this->login();
    $this->call('GET', '/user/logout');
    $this->assertRedirectedTo('/');
    $this->assertTrue(Auth::guest());
  }
}
