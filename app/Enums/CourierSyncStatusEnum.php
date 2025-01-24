<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierSyncStatusEnum: string
{
    use Enumable;

    case ALL = 'all';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case SYNCED = 'synced';
}
