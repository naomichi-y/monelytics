<?php
class ActivityCategoryGroupControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/settings/activityCategoryGroup');
  }
}
