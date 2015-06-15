<?php
class ConstantControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/cost/constant/create');
  }
}
