<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class AwnicResponseHandlerTest extends TestCase
{
    public function test_builds_success_response_from_successful_http_response(): void
    {
        $handler = new AwnicResponseHandler();
        $body = ['isSuccess' => 'Y', 'foo' => 'bar'];
        $httpResponse = new Response(new Psr7Response(200, [], json_encode($body)));

        $result = $handler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_POLICY);

        $this->assertTrue($result['status']);
        $this->assertIsObject($result['data']);
    }

    public function test_flags_api_errors_and_returns_normalized_message(): void
    {
        $handler = new AwnicResponseHandler();
        $body = ['isSuccess' => 'N', 'errorList' => ['ERR']];
        $httpResponse = new Response(new Psr7Response(200, [], json_encode($body)));

        $result = $handler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_POLICY);

        $this->assertFalse($result['status']);
        $this->assertEquals(['ERR'], $result['error']);
    }
}

