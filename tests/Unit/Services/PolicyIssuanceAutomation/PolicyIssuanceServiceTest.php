<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use JsonSerializable;

beforeEach(function () {
    $this->service = new PolicyIssuanceService;
    $reflection = new ReflectionMethod(PolicyIssuanceService::class, 'resolvePolicyIssuanceLogResponse');
    $reflection->setAccessible(true);
    $this->resolve = fn (mixed $payload) => $reflection->invoke($this->service, $payload);
});

afterEach(function () {
    unset($this->service, $this->resolve);
});

dataset('policyIssuanceLogResponses', function () {
    $httpPayload = ['status' => true, 'message' => 'ok'];
    $response = new Response(new Psr7Response(200, [], json_encode($httpPayload)));

    return [
        'http client response' => [$response, $httpPayload],
        'scalar array' => [['status' => false, 'error' => 'failed'], ['status' => false, 'error' => 'failed']],
        'std class object' => [
            (object) [
                'status' => true,
                'details' => (object) ['code' => 'OK', 'value' => 100],
            ],
            ['status' => true, 'details' => ['code' => 'OK', 'value' => 100]],
        ],
        'object with toArray' => [
            new class {
                public function toArray(): array
                {
                    return ['foo' => 'bar'];
                }
            },
            ['foo' => 'bar'],
        ],
        'json serializable object' => [
            new class implements JsonSerializable {
                public function jsonSerialize(): array
                {
                    return ['baz' => 'qux'];
                }
            },
            ['baz' => 'qux'],
        ],
    ];
});

it('normalizes policy issuance log responses', function ($payload, $expected) {
    $resolve = $this->resolve;

    expect($resolve($payload))->toBe($expected);
})->with('policyIssuanceLogResponses');
