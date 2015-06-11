<?php
class UserControllerTest extends TestCase {
  public function testPostCreate()
  {
    $params = array(
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user/create', $params);
    $this->assertRedirectedTo('/user/create-done');
  }

  public function testPostLogin()
  {
    $params = array(
      'email' => 'test@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user/login', $params);
    $this->assertRedirectedTo('/dashboard');
  }

  public function testGetIndex()
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
