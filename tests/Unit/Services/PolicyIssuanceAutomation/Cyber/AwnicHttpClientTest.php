<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AwnicHttpClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_performs_post_requests_using_configured_base_url(): void
    {
        config()->set('constants.AWNIC_API_BASE_URL', 'https://awni.test');
        config()->set('constants.AWNIC_API_PARTNER_ID', 'partner');
        config()->set('constants.AWNIC_API_SECRET_KEY', 'secret');

        $storage = Mockery::mock(ApplicationStorageService::class);
        $storage->shouldReceive('getValueByKey')->andReturn(5);
        app()->instance(ApplicationStorageService::class, $storage);

        Http::fake([
            'https://awni.test/foo' => Http::response(['ok' => true], 200),
        ]);

        $client = new AwnicHttpClient();
        $response = $client->post('/foo', ['bar' => 'baz']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->json('ok'));
        $this->assertSame('https://awni.test', $client->getBaseUrl());
    }
}

