<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';

    case ALLOCATION_AUDIT = 'allocation-audit';

    case FTC_EMAIL = 'ftc-email';

    case TRAVEL_RENEWALS = 'travel-renewals';

    case CREATE_PAYMENT = 'create-payment';

    case UPDATE_PAYMENT = 'update-payment';

    case DELETE_PARENT_PAYMENT = 'delete-parent-payment';

    case DELETE_SPLIT_PAYMENT = 'delete-split-payment';

    case APPROVE_DECLINE_CHILD_PAYMENT = 'approve-decline-child-payment';

    case APPROVE_PARENT_PAYMENT = 'approve-parent-payment';

    case DECLINE_PARENT_PAYMENT = 'decline-parent-payment';

    case MIGRATE_PAYMENT = 'migrate-payment';

}
