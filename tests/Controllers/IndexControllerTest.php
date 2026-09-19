<?php
namespace Tests\Controllers;

use Tests\TestCase;

class IndexControllerTest extends TestCase {
    public function testIndex()
    {
      $this->assertGuestOnlyContent('GET', '/');
    }
}
