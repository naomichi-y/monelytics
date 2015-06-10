<?php
class UserControllerTest extends TestCase {
  public function testPostCreate()
  {
    $response = $this->call('POST',
      '/user/create',
      array(
        'nickname' => 'test',
        'email' => 'test@monelytics.com',
        'password' => 'testtest'
      )
    );

    $this->assertRedirectedToAction('UserController@getCreateDone');
  }
}
