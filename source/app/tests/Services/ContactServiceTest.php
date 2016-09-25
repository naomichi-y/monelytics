<?php
namespace Monelytics\Tests\Services;

use Monelytics\Tests\TestCase;

class ContactServiceTest extends TestCase {
  private $contact;

  public function setup()
  {
    parent::setup();

    $this->contact = $this->app->make('Monelytics\Services\ContactService');
  }

  public function testGetContactTypeList()
  {
    $this->assertEquals(gettype($this->contact->getContactTypeList()), 'array');
  }

  public function testSend()
  {
    $params = array(
      'contact_name' => 'test',
      'email' => 'test@monelytics.me',
      'contact_type' => 1,
      'contact_message' => 'test'
    );
    $user_id = null;
    $this->assertEquals($this->contact->send($params, $user_id), true);

    $user_id = 1;
    $this->assertEquals($this->contact->send($params, $user_id), true);
  }
}
