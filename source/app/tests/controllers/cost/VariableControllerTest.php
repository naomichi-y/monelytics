<?php
class VariableControllerTest extends TestCase {
  public function testGetIndex()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/create');
  }
}
