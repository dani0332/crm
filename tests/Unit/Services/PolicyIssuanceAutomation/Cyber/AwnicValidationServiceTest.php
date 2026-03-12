<?php

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;

afterEach(function () {
    Mockery::close();
});

it('fails required data validation when essentials are missing', function () {
    $service = new AwnicValidationService(Mockery::mock(AwnicDocumentHandler::class));

    $quote = (object) [
        'payments' => null,
        'cyberPlanDetail' => null,
        'customer' => null,
        'nationality' => null,
        'cyberQuote' => null,
        'latestInsured' => [
            'id_type' => null,
            'id_number' => null,
        ],
    ];

    $result = $service->validateRequiredData($quote);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toContain('payments', 'customer');
});

it('validates upload documents and download documents helpers', function () {
    $service = new AwnicValidationService(Mockery::mock(AwnicDocumentHandler::class));

    $quote = (object) ['insurer_quote_number' => 'REF-1'];
    $documents = [
        ['document_type_code' => 'doc', 'doc_name' => 'id', 'doc_url' => 'path'],
    ];

    $uploadResult = $service->validateUploadDocuments($quote, $documents);
    $downloadResult = $service->validateDownloadDocuments(['docA' => '1', 'docB' => '2']);

    expect($uploadResult['status'])->toBeTrue()
        ->and($downloadResult['status'])->toBeTrue();
});
