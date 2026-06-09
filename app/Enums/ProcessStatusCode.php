<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ProcessStatusCode extends Enum
{
    public const PENDING = 'Pending';
    public const COMPLETED = 'Completed';
    public const PROCEED = 'Proceed';
    public const IN_PROGRESS = 'In Progress';
    public const UPLOADED = 'Uploaded';
    public const FETCHING_PLANS = 'Fetching Plans';
    public const PLANS_FETCHED = 'Plans Fetched';
    public const PLANS_FAILED = 'Plans Failed';
    public const FAILED = 'Failed';
    public const SENT = 'Sent';
    public const UNSUBSCRIBED = 'unsubscribe-request';
    public const CLICKED = 'Clicked';
}
