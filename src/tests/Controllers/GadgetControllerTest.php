<?php
namespace Tests\Services;

use Tests\TestCase;

class GadgetControllerTest extends TestCase {
  public function testActivityStatus()
  {
    $this->assertUserOnlyContent('GET', '/gadget/activity-status');
  }

  public function testActivityGraph()
  {
    $this->assertUserOnlyContent('GET', '/gadget/activity-graph');
  }

  public function testActivityHistory()
  {
    $this->assertUserOnlyContent('GET', '/gadget/activity-history');
  }
}
