<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;

beforeEach(function () {
    $this->documentHandlerMock = Mockery::mock(AdnicDocumentHandler::class);
    $this->service = new AdnicValidationService($this->documentHandlerMock);
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Document validation logic
test('validate download documents checks for required document IDs', function () {
    $quoteMock = Mockery::mock();

    // Test with missing documents
    $docTypeCodeForIMCRM = [
        'PolicyDocumentId' => null,
        'CommisionNoteDocumentId' => null,
        'TaxInvoiceDocumentId' => null,
    ];

    $result = $this->service->validateDownloadDocuments($quoteMock, $docTypeCodeForIMCRM);
    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toContain('Missing documents');

    // Test with all documents present
    $docTypeCodeForIMCRM = [
        'PolicyDocumentId' => 1,
        'CommisionNoteDocumentId' => 2,
        'TaxInvoiceDocumentId' => 3,
    ];

    $result = $this->service->validateDownloadDocuments($quoteMock, $docTypeCodeForIMCRM);
    expect($result['status'])->toBeTrue();
});

// CRITICAL TEST: Response structure
test('validation service returns consistent structure', function () {
    $quoteMock = Mockery::mock();
    
    // Test successful validation
    $docTypeCodeForIMCRM = [
        'PolicyDocumentId' => 1,
        'CommisionNoteDocumentId' => 2,
        'TaxInvoiceDocumentId' => 3,
    ];
    
    $result = $this->service->validateDownloadDocuments($quoteMock, $docTypeCodeForIMCRM);
    
    expect($result)
        ->toHaveKey('status')
        ->and($result['status'])->toBeTrue();
});
