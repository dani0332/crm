<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Schema;
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

    public static function createRenewalsSchema(): void
    {
        self::createMinimalSchema();

        (new RenewalsSchema)->register();
    }

}
