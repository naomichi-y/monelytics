<?php
class UserControllerTest extends TestCase {
  public function testCreate()
  {
    $params = array(
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user', $params);
    $this->assertRedirectedTo('/user/create-done');
    $this->assertTrue(Auth::check());
    Auth::logout();

    $this->call('POST', '/user', $params);
    $this->assertRedirectedTo('/user/create');
    $this->assertTrue(Auth::guest());
  }

  public function testLogin()
  {
    $params = array(
      'email' => 'test@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user/login', $params);
    $this->assertRedirectedTo('/dashboard');
    $this->assertTrue(Auth::check());
  }

  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/user');
  }

  public function testLogot()
  {
    $this->assertGuestInaccessibleContent('GET', '/user/logout');

    $this->login();
    $this->call('GET', '/user/logout');
    $this->assertRedirectedTo('/');
    $this->assertTrue(Auth::guest());
  }

  public function testWithdrawal()
  {
    $this->assertUserOnlyContent('POST', '/user/withdrawal');
    $this->seed();

    $this->login();
    $this->call('POST', '/user/withdrawal');
    $this->assertTrue(Auth::guest());
  }
}
