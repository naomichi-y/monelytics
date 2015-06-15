<?php
class IndexControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertGuestOnlyContent('GET', '/');
  }
}
