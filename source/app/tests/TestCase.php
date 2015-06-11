<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase {

  /**
   * Creates the application.
   *
   * @return \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  public function createApplication()
  {
    $unitTesting = true;

    $testEnvironment = 'staging';

    return require __DIR__.'/../../bootstrap/start.php';
  }

  public function setUp()
  {
    parent::setUp();

    Artisan::call('db:seed');
  }

  public function login()
  {
    $this->be(User::find(1));
  }

  public function assertGuestAccessibleContent()
  {
    call_user_func_array(array($this, 'call'), func_get_args());
    $this->assertResponseOk();
  }

  public function assertGuestInaccessibleContent()
  {
    call_user_func_array(array($this, 'call'), func_get_args());
    $this->assertRedirectedTo('/');
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
}
