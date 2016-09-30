<?php
namespace Tests\Controllers;

use Tests\TestCase;

class ContactControllerTest extends TestCase {
    public function testGetIndex()
    {
        $this->assertAnyAccessibleContent('GET', '/contact');
    }

    public function testSend()
    {
        $params = [
            'contact_name' => 'test',
            'email' => 'test@monelytics.me',
            'contact_type' => '1',
            'contact_message' => 'test'
        ];

        $this->call('POST', '/contact/send', $params);
        $this->assertRedirectedTo('/contact/done');
    }

    public function testDone()
    {
        $this->call('GET', '/contact/done');
        $this->assertRedirectedTo('/contact');
    }
}
