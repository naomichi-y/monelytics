<?php
class ActivityCategoryControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory');
  }
}
