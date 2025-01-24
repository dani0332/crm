<?php

declare(strict_types=1);

namespace App\Enums;

enum CourierSyncStatusEnum: string
{
    use Enumable;

    case ALL = 'all';
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case FAILED = 'failed';
    case SYNCED = 'synced';
}
