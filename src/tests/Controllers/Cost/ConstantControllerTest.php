<?php
namespace Tests\Services;

use DateTime;

use App\Models\Activity;
use Tests\TestCase;

class ConstantControllerTest extends TestCase {
  private $default_count;

  public function setUp()
  {
    parent::setUp();

    $activity = new Activity;
    $this->default_count = $activity->all()->count();
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
    $params = [
      'activity_date' => [$target_month => [1 => $datetime->format('Y-m-d')]],
      'amount' => [$target_month => [1 => -1000]],
      '_token' => csrf_token()
    ];

    $this->call(
      'POST',
      '/cost/constant',
      $params,
      [],
      [],
      ['HTTP_REFERER' => 'http://localhost/cost/constant/create']
    );
    $this->assertEquals($this->default_count + 1, Activity::all()->count());
    $this->assertRedirectedTo('/cost/constant/create');

    $this->call('POST', '/cost/constant', $params);
    $this->assertEquals($this->default_count + 1, Activity::all()->count());
  }

  public function testDestroy()
  {
    $this->login();
    $this->call(
      'DELETE',
      '/cost/constant/1',
      ['_token' => csrf_token()],
      [],
      [],
      ['HTTP_REFERER' => 'http://localhost/cost/constant/create']
    );
    $this->assertRedirectedTo('/cost/constant/create');
    $this->assertEquals(Activity::find(1), null);
  }
}
