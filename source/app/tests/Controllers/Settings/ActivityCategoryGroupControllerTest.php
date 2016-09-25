<?php
namespace Monelytics\Tests\Controllers\Settings;

use Monelytics\Models;
use Monelytics\Seeds\Test\ActivityCategoryTableSeeder;
use Monelytics\Tests\TestCase;

class ActivityCategoryGroupControllerTest extends TestCase {
  private $activity_category_group;

  public function setup()
  {
    parent::setup();

    $this->activity_category_group = $this->app->make('Monelytics\Models\ActivityCategoryGroup');
  }

  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategoryGroup');
  }

  public function testSort()
  {
    $this->login();
    $request_id_orders = $this->activity_category_group
      ->where('activity_category_id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)
      ->lists('id');
    rsort($request_id_orders);

    $this->call('POST', '/settings/activityCategoryGroup/sort', array('ids' => $request_id_orders));
    $this->assertRedirectedTo('/settings/activityCategoryGroup');

    $result_id_orders = $this->activity_category_group
      ->where('activity_category_id', '=', ActivityCategoryTableSeeder::TYPE_VARIABLE_EXPENSE)
      ->orderBy('sort_order')
      ->lists('id');

    $this->assertEquals($result_id_orders, $request_id_orders);
  }

  public function testCreate()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategoryGroup/create');
  }

  public function testStore()
  {
    $this->login();
    $params = array(
      'activity_category_id' => 1,
      'group_name' => 'test',
      'credit_flag' => Models\ActivityCategoryGroup::CREDIT_FLAG_DISABLE
    );

    $default_count = $this->activity_category_group->all()->count();
    $this->assertValidAjaxResponse('POST', '/settings/activityCategoryGroup', $params);
    $this->assertEquals($this->activity_category_group->all()->count(), $default_count + 1);
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory/1/edit');
  }

  public function testUpdate()
  {
    $this->login();
    $params = $this->activity_category_group->find(1)->toArray();
    $params['group_name'] = 'update';

    $this->assertValidAjaxResponse('PUT', '/settings/activityCategoryGroup/1', $params);
    $this->assertEquals($this->activity_category_group->find(1)->group_name, 'update');
  }

  public function testDestroy()
  {

    $this->login();
    $this->call('DELETE', '/settings/activityCategoryGroup/1', array(), array(), array('HTTP_REFERER' => 'http://localhost/settings/activityCategoryGroup'));
    $this->assertRedirectedTo('/settings/activityCategoryGroup');
    $this->assertEquals($this->activity_category_group->find(1), null);
  }
}
