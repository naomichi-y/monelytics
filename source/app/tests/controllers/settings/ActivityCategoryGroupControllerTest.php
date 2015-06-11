<?php
class ActivityCategoryGroupControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategoryGroup');
  }
}
