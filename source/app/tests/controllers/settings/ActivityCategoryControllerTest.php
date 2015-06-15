<?php
class ActivityCategoryControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategory');
  }
}
