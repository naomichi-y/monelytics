<?php
namespace Monelytics\Tests\Services;

use Monelytics\Tests\TestCase;

class IndexControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertGuestOnlyContent('GET', '/');
  }
}
