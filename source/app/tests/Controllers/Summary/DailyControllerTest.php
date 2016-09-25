<?php
namespace Monelytics\Tests\Summary;

use Monelytics\Tests\TestCase;

class DailyControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/daily');
  }

  public function testCondition()
  {
    $this->assertUserOnlyContent('GET', '/summary/daily/condition');
  }
}
