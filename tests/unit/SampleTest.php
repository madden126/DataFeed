<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use UnitTester;

class SampleTest extends Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
        // Setup code here
    }

    public function testSample()
    {
        $this->assertTrue(true);
    }
} 