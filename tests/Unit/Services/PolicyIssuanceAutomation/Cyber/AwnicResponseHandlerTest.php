<?php

use App\Enums\AwnicEnum;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

it('builds success response from successful http response', function () {
    $handler = new AwnicResponseHandler;
    $body = ['isSuccess' => 'Y', 'foo' => 'bar'];
    $httpResponse = new Response(new Psr7Response(200, [], json_encode($body)));

    $result = $handler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_POLICY);

    expect($result['status'])->toBeTrue()
        ->and($result['data'])->toBeObject();
});

it('flags API errors and returns normalized message', function () {
    $handler = new AwnicResponseHandler;
    $body = ['isSuccess' => 'N', 'errorList' => ['ERR']];
    $httpResponse = new Response(new Psr7Response(200, [], json_encode($body)));

    $result = $handler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_POLICY);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe(['ERR']);
});
