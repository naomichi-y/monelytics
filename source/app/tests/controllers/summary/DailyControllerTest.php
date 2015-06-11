<?php
class DailyControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/daily');
  }
}
