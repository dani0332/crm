<?php

declare(strict_types=1);

namespace App\Enums;

class AdnicEnum
{
    public const RESPONSIBLE_PERSON_DEFAULT_EMAIL = 'hitesh.motwani@insurancemarket.ae'; // TODO:: Shereen will let us know the when business confirmed the email
    public const RESPONSIBLE_PERSON_DEFAULT_MOBILE = '+971505636254'; // TODO:: Shereen will let us know the when business confirmed the mobile
    public const DEFAULT_EMIRATE_OF_YOUR_VISA = 2;
    public const STEP_GENERATE_QUOTE = 'generateQuote';
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

    /* Insurer Document Keys */
    public const INSURER_DOCUMENT_KEY_POLICY_DOCUMENT = 'PolicyDocumentId';
    public const INSURER_DOCUMENT_KEY_COMMISION_NOTE = 'CommisionNoteDocumentId';
    public const INSURER_DOCUMENT_KEY_TAX_INVOICE = 'TaxInvoiceDocumentId';
}
