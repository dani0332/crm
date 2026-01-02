<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';
    case ALLOCATION_AUDIT = 'allocation-audit';
    case FTC_EMAIL = 'ftc-email';
    case FTC_EMAIL_LOG = 'ftc-email-log';
    case TRAVEL_RENEWALS = 'travel-renewals';
    case OCR = 'ocr';
    case PCP_CLIENT = 'private-client';
    case AML_SCREENING = 'aml-screening';
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
    case POLICY_AUTOMATION = 'policy-automation';
    case SAGE_POLICY_BOOKING = 'sage-policy-booking';
    case POLICY_ISSUE_WHATSAPP_MESSAGE = 'policy-issue-whatsapp-message';
    case SAGE_ENDORSEMENT_BOOKING = 'sage-endorsement-booking';
    case SAGE_POST_PREPAYMENT = 'sage-post-prepayment';
    case CAR_OCB_INTRO_EMAIL = 'car-ocb-intro-email';
    case RETRY_PREPAYMENT_POSTING = 'retry-prepayment-posting';
    case SAGE_EP_BOOKING = 'sage-ep-booking';
    case SAGE_EP_BOOKING_REVERSAL = 'sage-ep-booking-reversal';
    case SEND_FAILED_PAYMENT_EMAIL = 'send-failed-payment-email';
    case CAR_CQF_RENEWALS = 'car-cqf-renewals';
    case SUPPORT_USER_ASSIGNMENT = 'support-user-assignment';
    case EP_PROCESS_PURCHASE_FLOW = 'ep-process-purchase-flow';
    case EP_PROCESS_SYNC_DOCUMENT = 'ep-process-sync-document';
    case EP_PROCESS_WATERMARK_DOCUMENT = 'ep-process-watermark-document';
    case EP_PROCESS_SEND_DOCUMENT = 'ep-process-send-document';
    case SLA_TRACKING = 'sla-tracking';
    case MA_WELCOME_JOB = 'ma-welcome-job';
    case POLICY_ISSUANCE_JOB = 'policy-issuance-job';
    case CC_PAYMENT_PROCESS_COMMAND = 'cc-payment-process-command';
    case SEND_AND_BOOK_POLICY_FAILED_BULK_EMAIL_JOB = 'send-and-book-policy-failed-bulk-email-job';
    case INSURER_AML_SCREENING_WITH_KYC_DOCUMENT = 'insurer-aml-screening-with-kyc-document';
    case LEAD_OCR_DATA_COMPARISON = 'lead-ocr-data-comparison';

    /* Claims Module */
    case CLAIM_CREATION = 'claim-creation';
    case CLAIM_UPDATE = 'claim-update';
    case CLAIM_DETAILS_UPDATE = 'claim-details-update';
    case CLAIM_STATUS_UPDATE = 'claim-status-update';
    case CLAIM_NEXT_FOLLOW_UP_UPDATE = 'claim-next-follow-up-update';
    case CLAIM_MAKE_ADDITIONAL_CONTACT_PRIMARY = 'claim-make-additional-contact-primary';
    case CLAIM_LIST = 'claim-list';
    case CLAIM_SEARCH_POLICIES = 'claim-search-policies';
    case CLAIM_EXPORT = 'claim-export';
    case CLAIM_OPTIMIZE_MESSAGE = 'claim-optimize-message';
    case CLAIM_COMPLAINT_STATUS_UPDATE = 'claim-complaint-status-update';
    case CLAIM_SEND_NOTIFICATION = 'claim-send-notification';
    case CLAIM_DOCUMENT_UPLOAD = 'claim-document-upload';
    case CLAIM_DOCUMENT_DELETE = 'claim-document-delete';
    case CLAIM_DOCUMENT_S3_URL = 'claim-document-s3-url';
    case CLAIM_DOCUMENT_DOWNLOAD_ALL = 'claim-document-download-all';
    case CLAIM_GOOGLE_REVIEW_EMAIL = 'claim-google-review-email';
}
