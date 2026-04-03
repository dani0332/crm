<?php

namespace App\Enums;

enum HealthRoutingLogTypeEnum: string
{
    use Enumable;

    case ROUTING = 'ROUTING';
    case CONFIGURATION = 'CONFIGURATION';
}
