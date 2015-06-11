<?php
class ConstantControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/cost/constant/create');
  }
}
