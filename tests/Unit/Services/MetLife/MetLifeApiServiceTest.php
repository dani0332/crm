<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeApiService;
use App\Services\MetLife\MetLifeValidationService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MetLifeApiServiceTest extends TestCase
{
    private MetLifeApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('constants.METLIFE_API_BASE_URL', 'https://test.metlife.com');
        Config::set('constants.METLIFE_API_VERSION', '3');
        Config::set('constants.METLIFE_USERNAME', 'test_user');
        Config::set('constants.METLIFE_PASSWORD', 'test_pass');
        Config::set('constants.METLIFE_API_TIMEOUT', 30);
        Config::set('constants.METLIFE_SESSION_TIMEOUT', 7200);
        Config::set('constants.METLIFE_CSRF_TOKEN_REFRESH_INTERVAL', 3600);
        
        $this->service = new MetLifeApiService();
    }

    public function test_is_metlife_enabled_returns_boolean()
    {
        $result = $this->service->isMetLifeEnabled();
        
        $this->assertIsBool($result);
    }

    public function test_get_mime_type_from_extension_pdf()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'pdf');
        
        $this->assertEquals('application/pdf', $result);
    }

    public function test_get_mime_type_from_extension_docx()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'docx');
        
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $result);
    }

    public function test_get_mime_type_from_extension_unknown()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'unknown');
        
        $this->assertEquals('application/octet-stream', $result);
    }

    public function test_get_mime_type_from_extension_case_insensitive()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'PDF');
        
        $this->assertEquals('application/pdf', $result);
    }

    public function test_accepted_document_mime_types_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('acceptedDocumentMimeTypes');
        $property->setAccessible(true);

        $mimeTypes = $property->getValue($this->service);
        
        $this->assertIsArray($mimeTypes);
        $this->assertContains('application/pdf', $mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('image/png', $mimeTypes);
    }

    public function test_service_initialization_properties()
    {
        $reflection = new \ReflectionClass($this->service);
        
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $this->assertEquals('https://test.metlife.com', $baseUrlProperty->getValue($this->service));
        
        $apiVersionProperty = $reflection->getProperty('apiVersion');
        $apiVersionProperty->setAccessible(true);
        $this->assertEquals('3', $apiVersionProperty->getValue($this->service));
        
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        $this->assertEquals(30, $timeoutProperty->getValue($this->service));
    }

    public function test_upload_to_metlife_validation_missing_policy_number()
    {
        $data = [
            'file' => 'test_file_data',
            'file_name' => 'test.pdf'
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_to_metlife_validation_missing_file()
    {
        $data = [
            'policy_number' => 'POL123',
            'file_name' => 'test.pdf'
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File data is required', $result['message']);
    }

    public function test_upload_to_metlife_validation_empty_policy_number()
    {
        $data = [
            'policy_number' => '',
            'file' => 'test_file_data',
            'file_name' => 'test.pdf'
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_to_metlife_validation_empty_file()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => '',
            'file_name' => 'test.pdf'
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File data is required', $result['message']);
    }

    public function test_response_structure_consistency()
    {
        $data = ['policy_number' => 'POL123'];
        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
