<?php
class ActivityCategoryControllerTest extends TestCase {
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
      'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
      'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE,
      'sort_order' => 2
    );

    ActivityCategory::create($activity_category);
    $params = array(2, 1);

    $this->call('POST', '/settings/activityCategory/sort', $params);
    $this->assertRedirectedTo('/settings/activityCategory');

    $sort_orders = ActivityCategory::orderBy('id', 'DESC')->lists('sort_order');
    $this->assertEquals($sort_orders, array(2, 1));
  }

  public function testStore()
  {
    $this->login();
    $params = array(
      'category_name' => 'test',
      'cost_type' => ActivityCategory::COST_TYPE_VARIABLE,
      'balance_type' => ActivityCategory::BALANCE_TYPE_EXPENSE
    );
    $this->assertValidAjaxResponse('POST', '/settings/activityCategory', $params);
    $this->assertEquals(ActivityCategory::all()->count(), 2);
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory/1/edit');
  }

  public function testUpdate()
  {
    $this->login();
    $params = ActivityCategory::find(1)->toArray();
    $params['category_name'] = 'update';

    $this->assertValidAjaxResponse('PUT', '/settings/activityCategory/1', $params);
    $this->assertEquals(ActivityCategory::find(1)->category_name, 'update');
  }

  public function testDestroy()
  {
    $this->login();
    $this->call('DELETE', '/settings/activityCategory/1');
    $this->assertRedirectedTo('/settings/activityCategory');
    $this->assertEquals(ActivityCategory::all()->count(), 0);
  }
}
