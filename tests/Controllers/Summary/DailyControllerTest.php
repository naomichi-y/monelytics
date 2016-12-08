<?php
namespace Tests\Summary;

use Tests\TestCase;

class DailyControllerTest extends TestCase {
    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/summary/daily');
    }

    public function testCondition()
    {
        $this->assertUserOnlyContent('GET', '/summary/daily/condition');
    }
}
