<?php
use Monelytics\Services;

class ContactServiceTest extends TestCase {
  private $contact;

  public function __construct()
  {
    $this->contact = new Services\ContactService();
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
    $this->contact->send($params, $user_id);

    $user_id = 1;
    $this->contact->send($params, $user_id);
  }
}
