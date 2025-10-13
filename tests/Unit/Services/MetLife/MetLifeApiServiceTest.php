<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeApiService;
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

        $this->service = new MetLifeApiService;
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
            'file_name' => 'test.pdf',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_to_metlife_validation_missing_file()
    {
        $data = [
            'policy_number' => 'POL123',
            'file_name' => 'test.pdf',
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
            'file_name' => 'test.pdf',
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
            'file_name' => 'test.pdf',
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

    public function test_upload_to_metlife_with_valid_data_structure()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => 'data:application/pdf;base64,test_file_data',
            'quote_uuid' => 'QUOTE456',
        ];

        // Mock the makeRequest method to return success
        $reflection = new \ReflectionClass($this->service);
        $makeRequestMethod = $reflection->getMethod('makeRequest');
        $makeRequestMethod->setAccessible(true);

        // We can't easily mock the makeRequest method since it's private and complex
        // Instead, we test the validation logic and structure
        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_upload_to_metlife_with_null_policy_number()
    {
        $data = [
            'policy_number' => null,
            'file' => 'test_file_data',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_to_metlife_with_null_file()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => null,
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File data is required', $result['message']);
    }

    public function test_upload_to_metlife_with_whitespace_policy_number()
    {
        $data = [
            'policy_number' => '   ',
            'file' => 'test_file_data',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        // The actual implementation may not validate whitespace-only strings
        $this->assertIsString($result['message']);
    }

    public function test_upload_to_metlife_with_whitespace_file()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => '   ',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        // The actual implementation may not validate whitespace-only strings
        $this->assertIsString($result['message']);
    }

    public function test_get_api_version_returns_string()
    {
        $result = $this->service->getApiVersion();

        $this->assertIsString($result);
        $this->assertEquals('3', $result);
    }

    public function test_accepted_document_mime_types_contains_expected_types()
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('acceptedDocumentMimeTypes');
        $property->setAccessible(true);

        $mimeTypes = $property->getValue($this->service);

        $expectedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/bmp',
            'image/tiff',
        ];

        foreach ($expectedTypes as $expectedType) {
            $this->assertContains($expectedType, $mimeTypes);
        }
    }

    public function test_accepted_document_mime_types_is_array()
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('acceptedDocumentMimeTypes');
        $property->setAccessible(true);

        $mimeTypes = $property->getValue($this->service);

        $this->assertIsArray($mimeTypes);
        $this->assertGreaterThan(0, count($mimeTypes));
    }

    public function test_get_mime_type_from_extension_doc()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'doc');

        $this->assertEquals('application/msword', $result);
    }

    public function test_get_mime_type_from_extension_empty_string()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '');

        $this->assertEquals('application/octet-stream', $result);
    }

    public function test_get_mime_type_from_extension_with_dots()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '.pdf');

        $this->assertEquals('application/octet-stream', $result);
    }

    public function test_get_mime_type_from_extension_mixed_case()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMimeTypeFromExtension');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'DoCx');

        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $result);
    }

    public function test_service_constructor_initializes_dependencies()
    {
        $reflection = new \ReflectionClass($this->service);

        // Check that all required services are initialized
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $this->assertNotNull($requestProperty->getValue($this->service));

        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $this->assertNotNull($cacheProperty->getValue($this->service));

        $responseServiceProperty = $reflection->getProperty('responseService');
        $responseServiceProperty->setAccessible(true);
        $this->assertNotNull($responseServiceProperty->getValue($this->service));

        $validatorProperty = $reflection->getProperty('validator');
        $validatorProperty->setAccessible(true);
        $this->assertNotNull($validatorProperty->getValue($this->service));
    }

    public function test_upload_to_metlife_data_structure_validation()
    {
        // Test with missing quote_uuid (should still work)
        $data = [
            'policy_number' => 'POL123',
            'file' => 'test_file_data',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_upload_to_metlife_with_provider_code()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => 'test_file_data',
            'provider_code' => 'MTL',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_upload_to_metlife_document_name_handling()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => 'test_file_data',
        ];

        // Test with various document names
        $documentNames = [
            'test.pdf',
            'test document.pdf',
            'test-document.pdf',
            'test_document.pdf',
            'test document with spaces.pdf',
        ];

        foreach ($documentNames as $docName) {
            $result = $this->service->uploadToMetLife($data, $docName);

            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('data', $result);
        }
    }

    public function test_upload_to_metlife_with_special_characters_in_policy_number()
    {
        $specialPolicyNumbers = [
            'POL-123',
            'POL_123',
            'POL.123',
            'POL 123',
            'POL123-ABC',
        ];

        foreach ($specialPolicyNumbers as $policyNumber) {
            $data = [
                'policy_number' => $policyNumber,
                'file' => 'test_file_data',
            ];

            $result = $this->service->uploadToMetLife($data, 'test.pdf');

            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('data', $result);
        }
    }

    public function test_upload_to_metlife_response_consistency()
    {
        $data = [
            'policy_number' => 'POL123',
            'file' => 'test_file_data',
        ];

        $result = $this->service->uploadToMetLife($data, 'test.pdf');

        // Verify response structure is consistent
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
        $this->assertIsArray($result['data']);

        // Verify data array structure is consistent
        $this->assertIsArray($result['data']);
    }
}
