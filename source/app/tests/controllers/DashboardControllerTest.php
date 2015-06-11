<?php
class DashboardControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/dashboard');
  }
}
