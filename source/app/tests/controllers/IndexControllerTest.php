<?php
class IndexControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertGuestOnlyContent('GET', '/');
  }
}
