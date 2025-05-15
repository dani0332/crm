<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';
    case PCP_CLIENT = 'private client';
}
