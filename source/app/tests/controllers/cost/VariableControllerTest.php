<?php
class VariableControllerTest extends TestCase {
  public function testIndex()
  {
    $this->assertUserOnlyContent('GET', '/cost/variable/create');
  }

  public function testCreate()
  {
    $this->login();
    $params = array(
      'user_id' => array(1),
      'activity_date' => array(date('Y/m/d')),
      'activity_category_group_id' => array(1),
      'amount' => array(1000)
    );
    $this->call('POST', '/cost/variable', $params);
    $this->assertEquals(Activity::all()->count(), 1);
  }
}
