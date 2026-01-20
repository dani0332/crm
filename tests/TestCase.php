<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Helpers\TestSchemaCreator;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal database schema for each test to ensure tables exist
        // Optimized with static flag to avoid redundant creation
        TestSchemaCreator::createMinimalSchema();
    }
}
