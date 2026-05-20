<?php

declare(strict_types=1);

namespace App\Queue;

use Aws\Credentials\CredentialProvider;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Registers the sqs_myalfred driver and returns a MyAlfredSqsQueue instance
 * instead of the standard SqsQueue.
 */
class MyAlfredSqsConnector extends SqsConnector
{
    public function connect(array $config): MyAlfredSqsQueue
    {
        $config = $this->getDefaultConfiguration($config);

        if ($credentials = $this->resolveCredentialProvider($config)) {
            $config['credentials'] = $credentials;
        } elseif (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return new MyAlfredSqsQueue(
            new SqsClient(Arr::except($config, ['token'])),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }

    protected function resolveCredentialProvider(array $config): mixed
    {
        $credentials = $config['credentials'] ?? null;
        $provider = is_string($credentials) ? $credentials : ($credentials['provider'] ?? null);

        if (is_null($provider)) {
            return null;
        }

        $options = is_array($credentials) ? Arr::except($credentials, ['provider']) : [];

        $resolved = match ($provider) {
            'ecs' => CredentialProvider::ecsCredentials($options),
            'instance' => CredentialProvider::instanceProfile($options),
            default => throw new InvalidArgumentException(
                "Invalid credential provider [{$provider}]."
            ),
        };

        return CredentialProvider::memoize($resolved);
    }
}
