<?php
use Monelytics\Models;

class RegistrationControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/user');
  }

  public function testCreate()
  {
    $this->assertGuestOnlyContent('GET', '/user/create');
  }

  public function testStore()
  {
    $params = array(
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest'
    );

    $this->call('POST', '/user', $params);
    $this->assertRedirectedTo('/user/done');
    $this->assertTrue(Auth::check());
    $this->logout();

    $this->call('POST', '/user', $params);
    $this->assertRedirectedTo('/user/create');
    $this->assertTrue(Auth::guest());
  }

  public function testDone()
  {
    $this->assertUserOnlyContent('GET', '/user/done');
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/user');
  }

  public function testUpdate()
  {
    $this->login();
    $params = array(
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest',
      'password_confirmation' => 'testtest'
    );

    $this->call('PUT', '/user/update', $params);
    $this->assertRedirectedTo('/user');
    $this->assertEquals(Models\User::find(1)->email, 'test2@monelytics.me');
  }

  public function testWithdrawal()
  {
    $this->assertUserOnlyContent('POST', '/user/withdrawal');
    $this->seed();

    $this->login();
    $this->call('POST', '/user/withdrawal');
    $this->assertTrue(Auth::guest());

    $this->assertEquals(Models\User::all()->count(), 0);
  }
}
