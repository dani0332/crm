<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicRequestBuilder;

beforeEach(function () {
    $this->httpClientMock = Mockery::mock(AdnicHttpClient::class);
    $this->httpClientMock->shouldReceive('getPartnerId')->andReturn('TEST_PARTNER_ID');
    $this->httpClientMock->shouldReceive('getPartnerReferenceNo')->andReturn('TEST_REF_NO');

    $this->builder = new AdnicRequestBuilder($this->httpClientMock);
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('request builder initializes correctly', function () {
    expect($this->builder)->toBeInstanceOf(AdnicRequestBuilder::class);
});

// CRITICAL TEST: HTTP client dependency injection
test('request builder has http client dependency', function () {
    $reflection = new ReflectionClass($this->builder);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);

    expect($property->getValue($this->builder))->toBeInstanceOf(AdnicHttpClient::class);
});

// CRITICAL TEST: Upload documents payload structure
test('build upload documents payload creates correct structure', function () {
    $base64Content = base64_encode('test content');

    $healthInsurerResponse = new stdClass;
    $healthInsurerResponse->QuoteInfo = new stdClass;
    $healthInsurerResponse->QuoteInfo->QuotationNo = 'QUOTE123';

    $insuredMember = new stdClass;
    $insuredMember->MemberSeqNo = 1;

    $quoteDocument = new stdClass;
    $quoteDocument->original_name = 'passport.pdf';
    $quoteDocument->doc_name = 'passport_copy.pdf';

    $insurerDocCode = '1';

    $result = $this->builder->buildUploadDocumentsPayload(
        $base64Content,
        $healthInsurerResponse,
        $insuredMember,
        $insurerDocCode,
        $quoteDocument
    );

    expect($result)
        ->toHaveKeys(['PartnerInfo', 'DocumentInfo'])
        ->and($result['PartnerInfo']['PartnerId'])->toBe('TEST_PARTNER_ID')
        ->and($result['DocumentInfo']['QuotationNo'])->toBe('QUOTE123')
        ->and($result['DocumentInfo']['MemberSeqNo'])->toBe(1)
        ->and($result['DocumentInfo']['DocumentType'])->toBe('1')
        ->and($result['DocumentInfo']['DocumentName'])->toBe('passport.pdf')
        ->and($result['DocumentInfo']['IsDocumentValidated'])->toBe('Y')
        ->and($result['DocumentInfo']['DocumentContent'])->toBe($base64Content);
});

// CRITICAL TEST: Upload documents uses doc_name when original_name is null
test('build upload documents payload uses doc name fallback', function () {
    $base64Content = base64_encode('test content');

    $healthInsurerResponse = new stdClass;
    $healthInsurerResponse->QuoteInfo = new stdClass;
    $healthInsurerResponse->QuoteInfo->QuotationNo = 'QUOTE123';

    $insuredMember = new stdClass;
    $insuredMember->MemberSeqNo = 1;

    $quoteDocument = new stdClass;
    $quoteDocument->doc_name = 'passport_copy.pdf';
    // original_name is not set

    $insurerDocCode = '1';

    $result = $this->builder->buildUploadDocumentsPayload(
        $base64Content,
        $healthInsurerResponse,
        $insuredMember,
        $insurerDocCode,
        $quoteDocument
    );

    expect($result['DocumentInfo']['DocumentName'])->toBe('passport_copy.pdf');
});

// CRITICAL TEST: Download document payload structure
test('build download document payload creates correct structure', function () {
    $generatePolicyResponse = new stdClass;
    $generatePolicyResponse->QuoteInfo = new stdClass;
    $generatePolicyResponse->QuoteInfo->QuotationNo = 'QUOTE123';
    $generatePolicyResponse->PolicyInfo = new stdClass;
    $generatePolicyResponse->PolicyInfo->PolicyNo = 'POL123';

    $docId = 'DOC123';

    $result = $this->builder->buildDownloadDocumentPayload($generatePolicyResponse, $docId);

    expect($result)
        ->toHaveKeys(['PartnerInfo', 'PolicyDocumentInfo'])
        ->and($result['PartnerInfo']['PartnerId'])->toBe('TEST_PARTNER_ID')
        ->and($result['PolicyDocumentInfo']['PartnerReferenceNo'])->toBe('TEST_REF_NO')
        ->and($result['PolicyDocumentInfo']['QuotationNo'])->toBe('QUOTE123')
        ->and($result['PolicyDocumentInfo']['PolicyNo'])->toBe('POL123')
        ->and($result['PolicyDocumentInfo']['DocumentId'])->toBe('DOC123');
});

// CRITICAL TEST: Mapping methods accessibility
test('request builder has mapping methods for data transformation', function () {
    $reflection = new ReflectionClass($this->builder);

    // Check that private mapping methods exist
    expect($reflection->hasMethod('mappingSalaryBand'))->toBeTrue()
        ->and($reflection->hasMethod('mappingGender'))->toBeTrue()
        ->and($reflection->hasMethod('mappingMaritalStatus'))->toBeTrue();
});

// CRITICAL TEST: Gender mapping
test('mapping gender returns correct values', function () {
    $reflection = new ReflectionClass($this->builder);
    $method = $reflection->getMethod('mappingGender');
    $method->setAccessible(true);

    // Test female variations (based on GenericRequestEnum values)
    expect($method->invoke($this->builder, 'Female'))->toBe('F')
        ->and($method->invoke($this->builder, 'female'))->toBe('F')
        ->and($method->invoke($this->builder, 'F'))->toBe('F')
        ->and($method->invoke($this->builder, 'FS'))->toBe('F')
        ->and($method->invoke($this->builder, 'FM'))->toBe('F')
        ->and($method->invoke($this->builder, 'Female-Single'))->toBe('F')
        ->and($method->invoke($this->builder, 'Female-Married'))->toBe('F');

    // Test male (default)
    expect($method->invoke($this->builder, 'Male'))->toBe('M')
        ->and($method->invoke($this->builder, 'M'))->toBe('M')
        ->and($method->invoke($this->builder, 'Unknown'))->toBe('M')
        ->and($method->invoke($this->builder, null))->toBe('M');
});

// CRITICAL TEST: Salary band mapping
test('mapping salary band returns correct values', function () {
    $reflection = new ReflectionClass($this->builder);
    $method = $reflection->getMethod('mappingSalaryBand');
    $method->setAccessible(true);

    expect($method->invoke($this->builder, 1))->toBe(1) // 4000 and less
        ->and($method->invoke($this->builder, 2))->toBe(2) // More than 4000
        ->and($method->invoke($this->builder, 99))->toBeNull(); // Invalid
});

// CRITICAL TEST: Marital status mapping
test('mapping marital status returns correct values', function () {
    $reflection = new ReflectionClass($this->builder);
    $method = $reflection->getMethod('mappingMaritalStatus');
    $method->setAccessible(true);

    expect($method->invoke($this->builder, 1))->toBe(1) // Single
        ->and($method->invoke($this->builder, 2))->toBe(2) // Married
        ->and($method->invoke($this->builder, 3))->toBe(4) // Widowed
        ->and($method->invoke($this->builder, 4))->toBe(3) // Divorced
        ->and($method->invoke($this->builder, 99))->toBeNull(); // Invalid
});

// CRITICAL TEST: Document upload date format
test('build upload documents payload includes iso formatted upload date', function () {
    $base64Content = base64_encode('test');

    $healthInsurerResponse = new stdClass;
    $healthInsurerResponse->QuoteInfo = new stdClass;
    $healthInsurerResponse->QuoteInfo->QuotationNo = 'Q123';

    $insuredMember = new stdClass;
    $insuredMember->MemberSeqNo = 1;

    $quoteDocument = new stdClass;
    $quoteDocument->original_name = 'test.pdf';

    $result = $this->builder->buildUploadDocumentsPayload(
        $base64Content,
        $healthInsurerResponse,
        $insuredMember,
        '1',
        $quoteDocument
    );

    expect($result['DocumentInfo'])->toHaveKey('DocumentUploadDate')
        ->and($result['DocumentInfo']['DocumentUploadDate'])->toBeString();
});
