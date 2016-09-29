<?php
namespace Tests\Controllers;

use App\Models\Activity;
use Tests\TestCase;

class VariableControllerTest extends TestCase {
  private $activity;

  public function setUp()
  {
    parent::setUp();

    $this->activity = new Activity;
    $this->default_count = $this->activity->all()->count();
  }

  public function testCreate()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/create');
  }

  public function testStore()
  {
    $this->login();
    $params = [
      'user_id' => [1],
      'activity_date' => [date('Y/m/d')],
      'activity_category_group_id' => [1],
      'amount' => [-1000],
      '_token' => csrf_token()
    ];

    $this->call(
      'POST',
      '/cost/variable',
      $params,
      [],
      [],
      ['HTTP_REFERER' => 'http://localhost/cost/variable/create']
    );
    $this->assertEquals($this->activity->all()->count() - $this->default_count, 1);
    $this->assertRedirectedTo('/cost/variable/create');
  }

  public function testEdit()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/1/edit');
  }

  public function testUpdate()
  {
    $this->login();

    $params = $this->activity->find(1)->toArray();
    $params['amount'] = -2000;
    $params['_token'] = csrf_token();

    $this->assertValidAjaxResponse('PUT', '/cost/variable/1', $params);
    $this->assertEquals(Activity::find(1)->amount, -2000);
  }

  public function testDestroy()
  {
    $this->login();
    $this->call(
      'DELETE',
      '/cost/variable/1',
      ['_token' => csrf_token()],
      [],
      [],
      ['HTTP_REFERER' => 'http://localhost/cost/variable/create']
    );
    $this->assertRedirectedTo('/cost/variable/create');
    $this->assertEquals(Activity::find(1), null);
  }
}
