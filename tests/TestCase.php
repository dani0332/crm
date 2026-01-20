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
        TestSchemaCreator::createMinimalSchema();
    }
}
