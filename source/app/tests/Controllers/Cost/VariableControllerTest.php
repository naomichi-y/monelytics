<?php
namespace Monelytics\Tests\Controllers;

use Monelytics\Models;
use Monelytics\Tests\TestCase;

class VariableControllerTest extends TestCase {
  private $default_count;

  public function setUp()
  {
    parent::setUp();
    $this->default_count = Models\Activity::all()->count();
  }

  public function testCreate()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/create');
  }

  public function testStore()
  {
    $this->login();
    $params = array(
      'user_id' => array(1),
      'activity_date' => array(date('Y/m/d')),
      'activity_category_group_id' => array(1),
      'amount' => array(-1000)
    );

    $this->call('POST', '/cost/variable', $params, array(), array('HTTP_REFERER' => 'http://localhost/cost/variable/create'));
    $this->assertEquals(Models\Activity::all()->count() - $this->default_count, 1);
    $this->assertRedirectedTo('/cost/variable/create');
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/1/edit');
  }

  public function testUpdate()
  {
    $this->login();

    $params = Models\Activity::find(1)->toArray();
    $params['amount'] = -2000;

    $this->assertValidAjaxResponse('PUT', '/cost/variable/1', $params);
    $this->assertEquals(Models\Activity::find(1)->amount, -2000);
  }

  public function testDestroy()
  {
    $this->login();
    $this->call('DELETE', '/cost/variable/1', array(), array(), array('HTTP_REFERER' => 'http://localhost/cost/variable/create'));
    $this->assertRedirectedTo('/cost/variable/create');
    $this->assertEquals(Models\Activity::find(1), null);
  }
}
