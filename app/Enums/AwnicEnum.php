<?php

declare(strict_types=1);

namespace App\Enums;

class AwnicEnum
{
    public const STEP_ISSUE_POLICY = 'IssuePolicy';
    public const STEP_UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const STEP_UPLOAD_POLICY_DOCS = 'UploadPolicyDocumentsToIMCRM';
    public const STEP_BOOK_POLICY = 'BookPolicy';
    public const RESPONSE_POLICY = 'PolicyResponse';
    public const RESPONSE_UPLOAD_DOCUMENTS = 'UploadDocumentsResponse';
    public const RESPONSE_DOWNLOAD_DOCUMENT = 'DownloadDocumentResponse';
    public const UNKNOWN_ERROR = 'Unknown error';
    public const ALL_STEPS_ARE_EDITABLE = 'All Steps are editable';
}
