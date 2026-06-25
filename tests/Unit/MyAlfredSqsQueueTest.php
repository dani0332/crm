<?php

declare(strict_types=1);

use App\Jobs\ProcessMyAlfredWelcomeEmailSqsJob;
use App\Queue\MyAlfredSqsQueue;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;

function makeMockSqsQueue(array $messages): MyAlfredSqsQueue
{
    $sqsClient = Mockery::mock(SqsClient::class);
    $sqsClient->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(['Messages' => $messages]);

    $queue = new MyAlfredSqsQueue(
        $sqsClient,
        'test-queue',
        'https://sqs.us-east-1.amazonaws.com/123456',
        '',
        false,
    );

    $queue->setContainer(app(Container::class));
    $queue->setConnectionName('sqs_myalfred');

    return $queue;
}

test('pop returns null when queue is empty', function () {
    $sqsClient = Mockery::mock(SqsClient::class);
    $sqsClient->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(['Messages' => null]);

    $queue = new MyAlfredSqsQueue($sqsClient, 'test-queue', 'https://sqs.us-east-1.amazonaws.com/123456', '', false);
    $queue->setContainer(app(Container::class));
    $queue->setConnectionName('sqs_myalfred');

    expect($queue->pop())->toBeNull();
});

test('pop returns SqsJob unchanged when body already has a job key', function () {
    $laravelPayload = [
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'data' => ['commandName' => 'App\\Jobs\\SomeJob', 'command' => 'serialized'],
    ];

    $queue = makeMockSqsQueue([[
        'MessageId' => 'msg-123',
        'ReceiptHandle' => 'receipt-123',
        'Body' => json_encode($laravelPayload),
    ]]);

    $job = $queue->pop();

    expect($job)->toBeInstanceOf(SqsJob::class);

    $body = json_decode($job->getRawBody(), true);
    expect($body['job'])->toBe('Illuminate\\Queue\\CallQueuedHandler@call');
    expect($body['data']['commandName'])->toBe('App\\Jobs\\SomeJob');
});

test('pop wraps raw MyAlfred message into a Laravel ProcessMyAlfredWelcomeEmailSqsJob payload', function () {
    $rawPayload = [
        'email' => 'test@example.com',
        'code' => 'abc123',
        'source' => 'web',
        'tag' => 'promo',
    ];

    $queue = makeMockSqsQueue([[
        'MessageId' => 'msg-456',
        'ReceiptHandle' => 'receipt-456',
        'Body' => json_encode($rawPayload),
    ]]);

    $job = $queue->pop();

    expect($job)->toBeInstanceOf(SqsJob::class);

    $body = json_decode($job->getRawBody(), true);
    expect($body['job'])->toBe('Illuminate\\Queue\\CallQueuedHandler@call');
    expect($body['data']['commandName'])->toBe(ProcessMyAlfredWelcomeEmailSqsJob::class);

    $command = unserialize($body['data']['command']);
    expect($command)->toBeInstanceOf(ProcessMyAlfredWelcomeEmailSqsJob::class);
    expect($command->email)->toBe('test@example.com');
    expect($command->code)->toBe('abc123');
    expect($command->source)->toBe('web');
    expect($command->tag)->toBe('promo');
});

test('pop wraps raw MyAlfred message with only required fields', function () {
    $rawPayload = [
        'email' => 'minimal@example.com',
        'code' => 'xyz789',
    ];

    $queue = makeMockSqsQueue([[
        'MessageId' => 'msg-789',
        'ReceiptHandle' => 'receipt-789',
        'Body' => json_encode($rawPayload),
    ]]);

    $job = $queue->pop();
    $body = json_decode($job->getRawBody(), true);
    $command = unserialize($body['data']['command']);

    expect($command)->toBeInstanceOf(ProcessMyAlfredWelcomeEmailSqsJob::class);
    expect($command->email)->toBe('minimal@example.com');
    expect($command->code)->toBe('xyz789');
    expect($command->source)->toBeNull();
    expect($command->tag)->toBeNull();
});

test('pop treats invalid JSON body as non-raw and returns it unchanged', function () {
    $queue = makeMockSqsQueue([[
        'MessageId' => 'msg-bad',
        'ReceiptHandle' => 'receipt-bad',
        'Body' => 'not-valid-json',
    ]]);

    $job = $queue->pop();

    expect($job)->toBeInstanceOf(SqsJob::class);
    expect($job->getRawBody())->toBe('not-valid-json');
});
