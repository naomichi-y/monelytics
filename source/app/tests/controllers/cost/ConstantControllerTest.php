<?php
use Monelytics\Models;

class ConstantControllerTest extends TestCase {
  private $default_count;

  public function setUp()
  {
    parent::setUp();
    $this->default_count = Models\Activity::all()->count();
  }

  public function testCreate()
  {
    $this->assertUserOnlyContent('GET', '/cost/constant/create');
  }

  public function testStore()
  {
    $this->login();
    $datetime = new DateTime('-1 months');
    $target_month = $datetime->format('Y-m');
    $params = array(
      'activity_date' => array($target_month => array(1 => $datetime->format('Y-m-d'))),
      'amount' => array($target_month => array(1 => -1000))
    );

    $this->call('POST', '/cost/constant', $params);
    $this->assertEquals($this->default_count + 1, Models\Activity::all()->count());
    $this->assertRedirectedTo('/cost/constant/create');

    $this->call('POST', '/cost/constant', $params);
    $this->assertEquals($this->default_count + 1, Models\Activity::all()->count());
  }

  public function testDestroy()
  {
    $this->login();
    $this->call('DELETE', '/cost/constant/1');
    $this->assertRedirectedTo('/cost/constant/create');
    $this->assertEquals($this->default_count - 1, Models\Activity::all()->count());
  }
}
