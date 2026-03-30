<?php

namespace Tests\Helpers;

use Tests\Support\Schema\CoreSchema;
use Tests\Support\Schema\CyberSchema;
use Tests\Support\Schema\RenewalsSchema;
use Tests\Support\Schema\RulesSchema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables for tests (see {@see CoreSchema}).
     *
     * Includes `document_types` columns used by send-policy and LOB-specific document tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema)->register();
    }

    public static function createCyberSchema(): void
    {
        self::createMinimalSchema();

        (new CyberSchema)->register();
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
