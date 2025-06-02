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

    case RETRY_SPLIT_PAYMENT = 'retry-split-payment';

    case VOID_PAYMENT = 'void-payment';

    case CAPTURE_PAYMENT_VALIDATION = 'capture-payment-validation';

    case CC_PAYMENT_PROCESS = 'cc-payment-process';

    case SELECT_PLAN = 'select-plan';

    case SELECT_INSURANCE_PROVIDER = 'select-insurance-provider';

    case SEND_AND_BOOK_POLICY_EMAIL_JOB = 'send-and-book-policy-email-job';
}
