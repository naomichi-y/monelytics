<?php
namespace Monelytics\Tests\Summary;

use Monelytics\Tests\TestCase;

class YearlyControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/yearly');
  }

  public function testCondition()
  {
    $this->assertUserOnlyContent('GET', '/summary/yearly/condition');
  }

  public function testReport()
  {
    $this->assertUserOnlyContent('GET', '/summary/yearly/report');
  }
}
