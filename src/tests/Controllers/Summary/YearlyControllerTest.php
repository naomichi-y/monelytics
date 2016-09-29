<?php
namespace Tests\Summary;

use Tests\TestCase;

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
