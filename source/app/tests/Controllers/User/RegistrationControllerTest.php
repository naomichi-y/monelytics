<?php
namespace Monelytics\Tests\Summary;

use Auth;

use Monelytics\Models;
use Monelytics\Tests\TestCase;

class RegistrationControllerTest extends TestCase {
  private $user;

  public function setup()
  {
    parent::setup();

    $this->user = $this->app->make('Monelytics\Models\User');
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
    $this->assertEquals($this->user->find(1)->email, 'test2@monelytics.me');
  }

  public function testWithdrawal()
  {
    $this->assertUserOnlyContent('POST', '/user/withdrawal');
    $this->seed('TestSeeder');

    $this->login();
    $this->call('POST', '/user/withdrawal');
    $this->assertTrue(Auth::guest());

    $this->assertEquals($this->user->all()->count(), 0);
  }
}
