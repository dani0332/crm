<?php

namespace Tests\Helpers;

use Tests\Support\Schemas\CoreSchema;
use Tests\Support\Schemas\CyberSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables for LifeQuote tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema())->register();
    }

    public static function createCyberSchema(): void
    {
        self::createMinimalSchema();

        (new CyberSchema())->register();
    }
}
