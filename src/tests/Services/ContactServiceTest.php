<?php
namespace Tests\Services;

use App\Services\ContactService;
use Tests\TestCase;

class ContactServiceTest extends TestCase {
    private $contact;

    public function setup()
    {
        parent::setup();

        $this->contact = new ContactService;
    }

    public function testGetContactTypeList()
    {
        $this->assertEquals(gettype($this->contact->getContactTypeList()), 'array');
    }

    public function testSend()
    {
        $params = [
            'contact_name' => 'test',
            'email' => 'test@monelytics.me',
            'contact_type' => 1,
            'contact_message' => 'test'
        ];
        $user_id = null;
        $this->assertEquals($this->contact->send($params, $user_id), true);

        $user_id = 1;
        $this->assertEquals($this->contact->send($params, $user_id), true);
    }
}
