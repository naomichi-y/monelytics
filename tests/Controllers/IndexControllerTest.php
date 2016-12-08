<?php
namespace Tests\Services;

use Tests\TestCase;

class IndexControllerTest extends TestCase {
    public function testIndex()
    {
      $this->assertGuestOnlyContent('GET', '/');
    }
}
