<?php

declare(strict_types=1);

namespace App\Queue;

use App\Jobs\ProcessMyAlfredWelcomeEmailSqsJob;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;

/**
 * Custom SQS queue driver for the sqs_myalfred connection.
 *
 * MyAlfred publishes raw JSON messages (e.g. {"email":"...","code":"..."}) to the SQS queue
 * without the Laravel job-envelope format (`job` key). This subclass detects those raw messages
 * in pop() and wraps them into a proper Laravel payload before returning the SqsJob, so the
 * queue worker can dispatch them to ProcessMyAlfredWelcomeEmailSqsJob.
 */
class MyAlfredSqsQueue extends SqsQueue
{
    public function pop($queue = null): ?SqsJob
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (is_null($response['Messages']) || count($response['Messages']) === 0) {
            return null;
        }

        $sqsMessage = $response['Messages'][0];
        $sqsMessage['Body'] = $this->wrapRawBodyIfNeeded($sqsMessage['Body']);

        return new SqsJob(
            $this->container, $this->sqs, $sqsMessage,
            $this->connectionName, $queue
        );
    }

    /**
     * Check if the SQS message body is a raw MyAlfred payload (no Laravel `job` key).
     * If so, wrap it in the Laravel queue job envelope targeting ProcessMyAlfredWelcomeEmailSqsJob.
     */
    private function wrapRawBodyIfNeeded(string $body): string
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded) || isset($decoded['job'])) {
            return $body;
        }

        $wrapped = [
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => ProcessMyAlfredWelcomeEmailSqsJob::class,
                'command' => serialize(new ProcessMyAlfredWelcomeEmailSqsJob(
                    email: (string) ($decoded['email'] ?? ''),
                    code: (string) ($decoded['code'] ?? ''),
                    source: isset($decoded['source']) ? (string) $decoded['source'] : null,
                    tag: isset($decoded['tag']) ? (string) $decoded['tag'] : null,
                )),
            ],
            'id' => $decoded['id'] ?? null,
            'attempts' => 0,
        ];

        return json_encode($wrapped);
    }
}
