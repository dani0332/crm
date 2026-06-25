<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Schema;
use Tests\Support\Schema\CoreSchema;
use Tests\Support\Schema\CyberSchema;
use Tests\Support\Schema\OCRSchema;
use Tests\Support\Schema\PartnerSchema;
use Tests\Support\Schema\RenewalsSchema;
use Tests\Support\Schema\RulesSchema;

class TestSchemaCreator
{
    private const MINIMAL_SCHEMA_SENTINEL_TABLE = 'users';

    /**
     * Create minimal required tables for tests (see {@see CoreSchema}).
     *
     * Includes `document_types` columns used by send-policy and LOB-specific document tests.
     */
    public static function createMinimalSchema(): void
    {
        (new CoreSchema)->register();
    }

    public static function createPartnerSchema(): void
    {
        self::createMinimalSchema();

        (new PartnerSchema)->register();
    }

    public static function ensureMinimalSchema(): void
    {
        if (Schema::hasTable(self::MINIMAL_SCHEMA_SENTINEL_TABLE)) {
            return;
        }

        self::createMinimalSchema();
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

    public static function createOCRSchema(): void
    {
        self::createMinimalSchema();

        (new OCRSchema)->register();
    }

    public static function createRulesSchema(): void
    {
        self::createMinimalSchema();

        (new RulesSchema)->register();
    }

}
