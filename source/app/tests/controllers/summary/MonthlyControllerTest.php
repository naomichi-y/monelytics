<?php
class MonthlyControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly');
  }
}
