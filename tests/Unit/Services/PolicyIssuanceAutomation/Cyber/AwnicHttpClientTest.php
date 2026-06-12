<?php

use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

afterEach(function () {
    Mockery::close();
});

it('performs POST requests using configured base url', function () {
    config()->set('constants.AWNIC_API_BASE_URL', 'https://awni.test');
    config()->set('constants.AWNIC_API_PARTNER_ID', 'partner');
    config()->set('constants.AWNIC_API_SECRET_KEY', 'secret');

    $storage = Mockery::mock(ApplicationStorageService::class);
    $storage->shouldReceive('getValueByKey')->andReturn(5);
    app()->instance(ApplicationStorageService::class, $storage);

    Http::fake([
        'https://awni.test/foo' => Http::response(['ok' => true], 200),
    ]);

    $client = new AwnicHttpClient;
    $response = $client->post('/foo', ['bar' => 'baz']);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->json('ok'))->toBeTrue()
        ->and($client->getBaseUrl())->toBe('https://awni.test');
});
