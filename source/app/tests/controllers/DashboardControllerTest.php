<?php
class DashboardControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/dashboard');
  }
}
