<?php
class DailySummaryTest extends TestCase {
  public function setUp()
  {
    parent::setUp();

    $this->login();
  }

  public static function setUpBeforeClass()
  {
    DB::statement(File::get('app/storage/meta/create.sql'));
  }

  public static function tearDownAfterClass()
  {
    DB::statement(File::get('app/storage/meta/truncate.sql'));
  }

  public function testGetIndex()
  {
    $response = $this->call('GET', '/dailySummary/index');
    $this->assertTrue($response->isOk());
  }

  public function testGetCondition()
  {
    $response = $this->call('GET', '/dailySummary/condition');
    $this->assertTrue($response->isOk());
  }
}
