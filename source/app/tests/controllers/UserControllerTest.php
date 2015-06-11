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
}
