<?php
class AuthTest extends TestCase {
	public function testPostLogin()
  {
    $this->login();
    $this->assertEquals(Auth::user()->id, 0);
	}
}
