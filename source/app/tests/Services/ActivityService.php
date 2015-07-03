<?php
namespace Monelytics\Tests\Services;

use Monelytics\Models;
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
      'activity_category_group_id' => array($user->activityCategoryGroups()->first()->id),
      'amount' => array(100),
      'location' => array(''),
      'content' => array(''),
      'credit_flag' => array(Models\Activity::CREDIT_FLAG_USE),
      'special_flag' => array(Models\Activity::SPECIAL_FLAG_USE)
    );

    $this->assertEquals($this->activity->createVariableCosts($user->id, $params), true);
    $this->assertEquals(Models\Activity::count(), 2);

    $activity = Models\Activity::limit(1)->offset(1)->get()->first();
    $this->assertEquals($activity->amount, 100);
  }
}
