<?php
namespace Monelytics\Tests\Services;

use Monelytics\Models;
use Monelytics\Seeds\Test\ActivityCategoryGroupTableSeeder;
use Monelytics\Tests\TestCase;

class ActivityServiceTest extends TestCase {
  private $activity;

  public function setup()
  {
    parent::setup();

    $this->activity = $this->app->make('Monelytics\Services\ActivityService');
  }

  public function testCreateVariableCosts()
  {
    $user = $this->getUser();
    $params = array(
      'activity_date' => array(date('Y-m-d')),
      'activity_category_group_id' => array(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE),
      'amount' => array(100),
      'location' => array(''),
      'content' => array(''),
      'credit_flag' => array(Models\Activity::CREDIT_FLAG_USE),
      'special_flag' => array(Models\Activity::SPECIAL_FLAG_USE)
    );

    $before_count = Models\Activity::count();
    $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
    $this->assertEquals($before_count, Models\Activity::count() - 1);

    $activity = Models\Activity::limit(1)->orderBy('id', 'desc')->get()->first();
    $this->assertEquals($activity->amount, -100);

    $params['activity_category_group_id'] = array(ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE);
    $params['amount'] = array(-100);
    $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
    $activity = Models\Activity::limit(1)->orderBy('id', 'desc')->get()->first();
    $this->assertEquals($activity->amount, 100);
  }
}
