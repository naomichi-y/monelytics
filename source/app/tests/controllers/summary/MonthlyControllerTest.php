<?php
class MonthlyControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/monthly');
  }
}
