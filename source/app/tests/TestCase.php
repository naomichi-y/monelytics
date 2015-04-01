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

		$testEnvironment = 'testing';

		return require __DIR__.'/../../bootstrap/start.php';
	}

  public function login()
  {
    $credentials = array(
     'email'=>'demo@monelytics.me',
     'password'=>'demo'
    );

    $response = $this->call('POST', '/auth/login', $credentials);
    $this->assertRedirectedTo('home');
    $response = $this->call('GET', '/home/index');
  }
}
