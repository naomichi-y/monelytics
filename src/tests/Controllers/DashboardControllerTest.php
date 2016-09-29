<?php
namespace Tests\Services;

use Tests\TestCase;

class DashboardControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/dashboard');
  }
}
