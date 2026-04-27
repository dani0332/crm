<?php

use App\Enums\ProcessStatusCode;
use App\Services\EmailStatusService;
use App\Services\PostMarkService;
use GuzzleHttp\ClientInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use function Pest\Laravel\mock;

beforeEach(function () {
    config()->set('constants.POSTMARK_TOKEN', 'test-token');
    config()->set('constants.POSTMARK_URL', 'https://postmark.test/email');
});

afterEach(function () {
    Mockery::close();
});

test('sendEmail logs ep email status when request has quote metadata and response has MessageID', function () {
    /** @var EmailStatusService&MockInterface $emailStatusService */
    $emailStatusService = mock(EmailStatusService::class);
    $emailStatusService
        ->shouldReceive('logEpEmailStatuses')
        ->once()
        ->with(Mockery::on(function (array $payload) {
            expect($payload['MessageID'])->toBe('msg-123')
                ->and($payload['RecordType'])->toBe(ProcessStatusCode::IN_PROGRESS)
                ->and($payload['Recipient'])->toBe('to@example.com')
                ->and($payload['Subject'])->toBe('Hello');

            expect($payload['Metadata'])->toMatchArray([
                'quote_id' => '7',
                'quote_type_id' => '2',
                'subject' => 'Hello',
            ]);

            return true;
        }));

    $client = Mockery::mock(ClientInterface::class);
    $service = new PostMarkService($emailStatusService, $client);

    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('getContents')->andReturn(json_encode(['MessageID' => 'msg-123']));

    $httpResponse = Mockery::mock(ResponseInterface::class);
    $httpResponse->shouldReceive('getStatusCode')->andReturn(200);
    $httpResponse->shouldReceive('getBody')->andReturn($stream);

    $client->shouldReceive('request')
        ->once()
        ->with('POST', 'https://postmark.test/email', Mockery::on(function (array $opts) {
            expect($opts)->toHaveKeys(['headers', 'body', 'timeout']);
            expect($opts['timeout'])->toBe(100);
            expect($opts['headers'])->toMatchArray([
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => 'test-token',
                'Content-Type' => 'application/json',
            ]);

            return true;
        }))
        ->andReturn($httpResponse);

    $body = json_encode([
        'To' => 'to@example.com',
        'Subject' => 'Hello',
        'Metadata' => [
            'quote_id' => '7',
            'quote_type_id' => '2',
        ],
    ]);

    expect($service->sendEmail($body))->toBe(200);
});

test('sendEmail does not log when postmark response has no MessageID', function () {
    /** @var EmailStatusService&MockInterface $emailStatusService */
    $emailStatusService = mock(EmailStatusService::class);
    $emailStatusService->shouldNotReceive('logEpEmailStatuses');

    $client = Mockery::mock(ClientInterface::class);
    $service = new PostMarkService($emailStatusService, $client);

    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('getContents')->andReturn(json_encode(['ErrorCode' => 0]));

    $httpResponse = Mockery::mock(ResponseInterface::class);
    $httpResponse->shouldReceive('getStatusCode')->andReturn(200);
    $httpResponse->shouldReceive('getBody')->andReturn($stream);

    $client->shouldReceive('request')->andReturn($httpResponse);

    $body = json_encode([
        'To' => 'to@example.com',
        'Subject' => 'Hello',
        'Metadata' => [
            'quote_id' => '7',
            'quote_type_id' => '2',
        ],
    ]);

    expect($service->sendEmail($body))->toBe(200);
});

test('sendEmail does not log when request payload metadata is missing quote ids', function () {
    /** @var EmailStatusService&MockInterface $emailStatusService */
    $emailStatusService = mock(EmailStatusService::class);
    $emailStatusService->shouldNotReceive('logEpEmailStatuses');

    $client = Mockery::mock(ClientInterface::class);
    $service = new PostMarkService($emailStatusService, $client);

    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('getContents')->andReturn(json_encode(['MessageID' => 'msg-123']));

    $httpResponse = Mockery::mock(ResponseInterface::class);
    $httpResponse->shouldReceive('getStatusCode')->andReturn(200);
    $httpResponse->shouldReceive('getBody')->andReturn($stream);

    $client->shouldReceive('request')->andReturn($httpResponse);

    $body = json_encode([
        'To' => 'to@example.com',
        'Subject' => 'Hello',
        'Metadata' => [
            'quote_id' => '',
            'quote_type_id' => '2',
        ],
    ]);

    expect($service->sendEmail($body))->toBe(200);
});

test('sendEmail does not log when request payload quote ids are non-numeric strings', function () {
    /** @var EmailStatusService&MockInterface $emailStatusService */
    $emailStatusService = mock(EmailStatusService::class);
    $emailStatusService->shouldNotReceive('logEpEmailStatuses');

    $client = Mockery::mock(ClientInterface::class);
    $service = new PostMarkService($emailStatusService, $client);

    $stream = Mockery::mock(StreamInterface::class);
    $stream->shouldReceive('getContents')->andReturn(json_encode(['MessageID' => 'msg-123']));

    $httpResponse = Mockery::mock(ResponseInterface::class);
    $httpResponse->shouldReceive('getStatusCode')->andReturn(200);
    $httpResponse->shouldReceive('getBody')->andReturn($stream);

    $client->shouldReceive('request')->andReturn($httpResponse);

    $body = json_encode([
        'To' => 'to@example.com',
        'Subject' => 'Hello',
        'Metadata' => [
            'quote_id' => 'abc',
            'quote_type_id' => '2',
        ],
    ]);

    expect($service->sendEmail($body))->toBe(200);
});

test('sendEmail returns exception code when guzzle throws', function () {
    /** @var EmailStatusService&MockInterface $emailStatusService */
    $emailStatusService = mock(EmailStatusService::class);
    $emailStatusService->shouldNotReceive('logEpEmailStatuses');

    $client = Mockery::mock(ClientInterface::class);
    $service = new PostMarkService($emailStatusService, $client);

    $client->shouldReceive('request')->andThrow(new Exception('boom', 503));

    expect($service->sendEmail(json_encode(['To' => 'to@example.com'])))->toBe(503);
});
