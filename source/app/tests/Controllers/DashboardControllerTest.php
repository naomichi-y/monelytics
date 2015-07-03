<?php
namespace Monelytics\Tests\Services;

use Monelytics\Tests\TestCase;

class DashboardControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/dashboard');
  }
}
