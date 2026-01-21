<?php

namespace Tests\Helpers;

use Tests\Support\Schemas\CyberSchema;
use Tests\Support\Schema\CoreSchema;
use Tests\Support\Schema\RenewalsSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema)->register();
    }

    public static function createCyberSchema(): void
    {
        self::createMinimalSchema();

        (new CyberSchema())->register();
    }

    public static function createRenewalsSchema(): void
    {
        self::createMinimalSchema();

        (new RenewalsSchema)->register();
    }

}
