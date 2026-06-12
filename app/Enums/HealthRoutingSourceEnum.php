<?php

namespace App\Enums;

enum HealthRoutingSourceEnum: string
{
    case ROUTING = 'ROUTING';
    case REASSIGNMENT = 'REASSIGNMENT';
}
