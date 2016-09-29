<?php
namespace Tests\Services;

use App\Models\Activity;
use App\Services\ActivityService;
use Seeds\Test\ActivityCategoryGroupTableSeeder;
use Tests\TestCase;

class ActivityServiceTest extends TestCase {
  private $activity;

  public function setup()
  {
    parent::setup();

    $this->activity = app('App\Services\ActivityService');
  }

  public function testCreateVariableCosts()
  {
    $user = $this->getUser();
    $params = [
      'activity_date' => [date('Y-m-d')],
      'activity_category_group_id' => [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_EXPENSE_CREDIT_ENABLE],
      'amount' => [100],
      'location' => [''],
      'content' => [''],
      'credit_flag' => [Activity::CREDIT_FLAG_USE],
      'special_flag' => [Activity::SPECIAL_FLAG_USE]
    ];

    $before_count = Activity::count();
    $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
    $this->assertEquals($before_count, Activity::count() - 1);

    $activity = Activity::limit(1)->orderBy('id', 'desc')->get()->first();
    $this->assertEquals($activity->amount, -100);

    $params['activity_category_group_id'] = [ActivityCategoryGroupTableSeeder::TYPE_VARIABLE_INCOME_CREDIT_ENABLE];
    $params['amount'] = [-100];
    $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
    $activity = Activity::limit(1)->orderBy('id', 'desc')->get()->first();
    $this->assertEquals($activity->amount, 100);
  }
}
