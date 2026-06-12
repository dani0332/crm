<?php

declare(strict_types=1);

namespace App\Enums;

class AdnicEnum
{
    public const DEFAULT_EMIRATE_OF_YOUR_VISA = 2;
    public const STEP_ISSUE_POLICY = 'IssuePolicy';
    public const STEP_UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const STEP_UPLOAD_POLICY_DOCS = 'UploadPolicyDocumentsToIMCRM';
    public const STEP_BOOK_POLICY = 'BookPolicy';
    public const RESPONSE_POLICY = 'PolicyResponse';
    public const RESPONSE_UPLOAD_DOCUMENTS = 'UploadDocumentsResponse';
    public const RESPONSE_DOWNLOAD_DOCUMENT = 'DownloadDocumentResponse';
    public const UNKNOWN_ERROR = 'Unknown error';
    public const ALL_STEPS_ARE_EDITABLE = 'All Steps are editable';
    public const LOADING_TYPE = 'PER';
    public const LOADING_VALUE = 0;
    public const LOADING_AMOUNT = 0;
    public const PAYMENT_TYPE = 5;
    public const CUSTOMER_CLASSIFICATION_NATURAL_PERSONS = 1;
    public const NO = 'NO';
    public const VISA_TYPE_EXISTING_VISA_HOLDER = 2;
    public const NATIONALITY_ID_EMIRATES_ID = 146;
    public const OCCUPATION_OTHER = 13;
    public const SPONSER_CATEGORY_UAE = 2;
    public const MEMBER_CATEGORY_DUBAI_RESIDENCY = 4;
    public const DUBAI_RESIDENCY = 110;

    /* Insurer Document Keys */
    public const INSURER_DOCUMENT_KEY_POLICY_DOCUMENT = 'PolicyDocumentId';
    public const INSURER_DOCUMENT_KEY_COMMISION_NOTE = 'CommisionNoteDocumentId';
    public const INSURER_DOCUMENT_KEY_TAX_INVOICE = 'TaxInvoiceDocumentId';
    public const EMIRATES_ID_TEXT = 'Emirates ID';
    public const EMIRATES_ID_CODE = 3;
    public const INSURED_EMIRATES_ID_APPLICATION_TEXT = 'EID Application Form';
    public const INSURED_EMIRATES_ID_APPLICATION_CODE = 2;
    public const POLICY_CONVERSION_ALREADY_IN_PROGRESS = 'The Policy Conversion is already in Progress';
}
