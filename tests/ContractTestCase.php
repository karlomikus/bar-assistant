<?php

namespace Tests;

use Spectator\Spectator;

class ContractTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');
    }
}
