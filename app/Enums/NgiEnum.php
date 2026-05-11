<?php

declare(strict_types=1);

namespace App\Enums;

class NgiEnum
{
    // API Steps for Device/SmartPhone policy issuance
    public const STEP_CREATE_POLICY_FROM_QUOTE = 'CreatePolicyFromQuote';
    public const STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'GetAndUploadPolicyDocs';
    public const STEP_BOOK_POLICY = 'BookPolicy';

    // Response types for API calls
    public const RESPONSE_CREATE_POLICY = 'CreatePolicyResponse';
    public const RESPONSE_GET_POLICY_DOCUMENTS = 'GetPolicyDocumentsResponse';
    public const RESPONSE_DOWNLOAD_DOCUMENT = 'DownloadDocumentResponse';

    // Error messages
    public const UNKNOWN_ERROR = 'Unknown error';
    public const ALL_STEPS_ARE_EDITABLE = 'All Steps are editable';

    // API retry configuration
    public const MAX_RETRY_ATTEMPTS = 3;
    public const RETRY_DELAY_MINUTES = 5;
    public const DOCUMENT_FETCH_DELAY_MINUTES = 3;
    public const FAILURE_EMAIL_DEFAULT_PREFIX_MESSAGE = 'Failed because you use dedicated failure email %s for %s. ';
}
