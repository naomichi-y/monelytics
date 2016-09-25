<?php
namespace Monelytics\Tests\Summary;

use Monelytics\Tests\TestCase;

class MonthlyControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly');
  }

  public function testCondition()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/condition');
  }

  public function testReport()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/report');
  }

  public function testCalendar()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/calendar');
  }

  public function testPieChart()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/pie-chart');
  }

  public function testPieChartData()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/pie-chart-data');
  }

  public function testRanking()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly/ranking');
  }
}
