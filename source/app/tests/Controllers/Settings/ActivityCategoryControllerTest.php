<?php
namespace Monelytics\Tests\Settings;

use Monelytics\Models;
use Monelytics\Tests\TestCase;

class ActivityCategoryControllerTest extends TestCase {
  private $activity_category;

  public function setup()
  {
    parent::setup();

    $this->activity_category = $this->app->make('Monelytics\Models\ActivityCategory');
  }

  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory');
  }

  public function testSort()
  {
    $this->login();
    $activity_category = array(
      'id' => 2,
      'user_id' => 1,
      'category_name' => 'test',
      'cost_type' => Models\ActivityCategory::COST_TYPE_VARIABLE,
      'balance_type' => Models\ActivityCategory::BALANCE_TYPE_EXPENSE,
      'sort_order' => 2
    );

    $this->activity_category->create($activity_category);
    $params = array(2, 1);

    $this->call('POST', '/settings/activityCategory/sort', $params);
    $this->assertRedirectedTo('/settings/activityCategory');

    $sort_orders = $this->activity_category->orderBy('id', 'DESC')->lists('sort_order');
    $this->assertEquals($sort_orders, array(2, 1));
  }

  public function testCreate()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory/create');
  }

  public function testStore()
  {
    $this->login();
    $params = array(
      'category_name' => 'test',
      'cost_type' => Models\ActivityCategory::COST_TYPE_VARIABLE,
      'balance_type' => Models\ActivityCategory::BALANCE_TYPE_EXPENSE
    );

    $default_count = $this->activity_category->all()->count();
    $this->assertValidAjaxResponse('POST', '/settings/activityCategory', $params);
    $this->assertEquals($this->activity_category->all()->count(), $default_count + 1);
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory/1/edit');
  }

  public function testUpdate()
  {
    $this->login();
    $params = $this->activity_category->find(1)->toArray();
    $params['category_name'] = 'update';

    $this->assertValidAjaxResponse('PUT', '/settings/activityCategory/1', $params);
    $this->assertEquals($this->activity_category->find(1)->category_name, 'update');
  }

  public function testDestroy()
  {

    $this->login();
    $this->call('DELETE', '/settings/activityCategory/1', array(), array(), array('HTTP_REFERER' => 'http://localhost/settings/activityCategory'));
    $this->assertRedirectedTo('/settings/activityCategory');
    $this->assertEquals($this->activity_category->find(1), null);
  }
}
