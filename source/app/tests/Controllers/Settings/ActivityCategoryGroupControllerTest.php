<?php
namespace Monelytics\Tests\Settings;

use Monelytics\Models;
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
    $activity_category_group = array(
      'id' => 2,
      'activity_category_id' => 1,
      'user_id' => 1,
      'group_name' => 'test',
      'credit_flag' => Models\Activity::CREDIT_FLAG_USE,
      'sort_order' => 2
    );

    $this->activity_category_group->create($activity_category_group);
    $params = array(2, 1);

    $this->call('POST', '/settings/activityCategoryGroup/sort', $params);
    $this->assertRedirectedTo('/settings/activityCategoryGroup');

    $sort_orders = $this->activity_category_group->orderBy('id', 'DESC')->lists('sort_order');
    $this->assertEquals($sort_orders, array(2, 1));
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
