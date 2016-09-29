<?php
namespace Tests\Summary;

use Auth;

use Tests\TestCase;
use App\Models\User;

class RegistrationControllerTest extends TestCase {
  private $user;

  public function setup()
  {
    parent::setup();

    $this->user = new User;
  }

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
    $params = [
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest',
      '_token' => csrf_token()
    ];

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
    $params = [
      'nickname' => 'test',
      'email' => 'test2@monelytics.me',
      'password' => 'testtest',
      'password_confirmation' => 'testtest',
      '_token' => csrf_token()
    ];

    $this->call('PUT', '/user/update', $params);
    $this->assertRedirectedTo('/user');
    $this->assertEquals($this->user->find(1)->email, 'test2@monelytics.me');
  }

  public function testWithdrawal()
  {
    $this->assertUserOnlyContent('POST', '/user/withdrawal', ['_token' => csrf_token()]);
    $this->seed('Seeds\TestSeeder');

    $this->login();
    $this->call('POST', '/user/withdrawal', ['_token' => csrf_token()]);
    $this->assertTrue(Auth::guest());

    $this->assertEquals($this->user->find(1), null);
  }
}
