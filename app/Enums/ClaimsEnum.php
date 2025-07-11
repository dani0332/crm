<?php

namespace App\Enums;

/**
 * Claims Enum
 *
 * Consolidates all claim-related constants, statuses, types, and configuration values
 * that were previously scattered across controllers, services, and models.
 */
enum ClaimsEnum: string
{
    // Reference ID Configuration
    case REF_ID_PREFIX = 'CLM-';

}
