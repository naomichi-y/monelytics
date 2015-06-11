<?php
class YearlyControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/summary/yearly');
  }
}
