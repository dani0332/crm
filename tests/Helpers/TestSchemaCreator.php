<?php

namespace Tests\Helpers;

use Tests\Support\Schema\CoreSchema;
use Tests\Support\Schema\RenewalsSchema;
use Tests\Support\Schema\RulesSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema)->register();
    }

    public static function createRenewalsSchema(): void
    {
        self::createMinimalSchema();

        (new RenewalsSchema)->register();
    }

    public static function createRulesSchema(): void
    {
        self::createMinimalSchema();

        (new RulesSchema)->register();
    }

}
