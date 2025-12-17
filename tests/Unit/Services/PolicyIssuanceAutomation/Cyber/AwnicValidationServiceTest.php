<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use Mockery;
use Tests\TestCase;

class AwnicValidationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_fails_required_data_validation_when_essentials_missing(): void
    {
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

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('payments', $result['error']);
        $this->assertStringContainsString('customer', $result['error']);
    }

    public function test_upload_and_download_document_validations(): void
    {
        $service = new AwnicValidationService(Mockery::mock(AwnicDocumentHandler::class));

        $quote = (object) ['insurer_quote_number' => 'REF-1'];
        $documents = [
            ['document_type_code' => 'doc', 'doc_name' => 'id', 'doc_url' => 'path'],
        ];

        $uploadResult = $service->validateUploadDocuments($quote, $documents);
        $downloadResult = $service->validateDownloadDocuments($quote, ['docA' => '1', 'docB' => '2']);

        $this->assertTrue($uploadResult['status']);
        $this->assertTrue($downloadResult['status']);
    }
}
