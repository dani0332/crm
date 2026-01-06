<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object for automation failed email data request.
 * Groups related parameters to reduce method parameter count.
 */
class AutomationFailedEmailDataRequest
{
    public function __construct(
        public readonly array $cc,
        public readonly bool $isDeviceNgi,
        public readonly string $actionRequired,
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $statusAPIFailed,
        public readonly string $processInvolved,
        public readonly string $workflowType
    ) {
    }
}
