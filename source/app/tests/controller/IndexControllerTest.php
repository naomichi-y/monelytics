<?php
class IndexControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->call('GET', '/');
    $this->assertResponseOk();
  }
}
