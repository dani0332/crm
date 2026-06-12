<?php

namespace App\Traits;

use App\Services\Logger\LoggerService;
use ErrorException;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use Throwable;

trait ChecksAzureFileExistence
{
    protected function checkAzureFileExistsWithRetry(string $path, string $storageDisk = 'azureIMPrivate'): bool
    {
        $delayInMilliseconds = $this->fileExistenceRetryDelayInMilliseconds();
        $maxAttempts = $this->fileExistenceRetryAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->performAzureFileExistenceCheck($path, $storageDisk);
            } catch (Throwable $e) {
                if (! $this->isTransientFileExistenceFailure($e) || $attempt === $maxAttempts) {
                    throw $e;
                }

                $previous = $e->getPrevious();

                LoggerService::warning('Transient file existence check failed; retrying', extra: [
                    'path' => $path,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'next_retry_delay_ms' => $delayInMilliseconds,
                    'exception_class' => $e::class,
                    'previous_exception_class' => $previous ? $previous::class : null,
                    'previous_exception_message' => $previous?->getMessage(),
                ]);

                usleep($delayInMilliseconds * 1000);
                $delayInMilliseconds *= 2;
            }
        }

        return false;
    }

    protected function isTransientFileExistenceFailure(Throwable $e): bool
    {
        if ($e instanceof UnableToCheckExistence) {
            return true;
        }

        $messages = [];
        $current = $e;

        do {
            $messages[] = strtolower($current->getMessage());
            $current = $current->getPrevious();
        } while ($current instanceof Throwable);

        foreach ($messages as $message) {
            if (
                str_contains($message, 'could not resolve host')
                || str_contains($message, 'curl error 6')
                || str_contains($message, 'temporary failure in name resolution')
                || str_contains($message, 'getaddrinfo')
                || str_contains($message, 'name or service not known')
                || str_contains($message, 'connection timed out')
                || str_contains($message, 'operation timed out')
            ) {
                return true;
            }
        }

        return false;
    }

    protected function fileExistenceRetryAttempts(): int
    {
        return 3;
    }

    protected function fileExistenceRetryDelayInMilliseconds(): int
    {
        return app()->environment('testing') ? 1 : 500;
    }

    private function performAzureFileExistenceCheck(string $path, string $storageDisk): bool
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $headers = $this->getHttpHeaders($path);

            if (! empty($headers) && is_array($headers) && preg_match('#HTTP/\d+\.\d+\s+(\d{3})#', $headers[0], $matches)) {
                $status = (int) $matches[1];

                return $status >= 200 && $status < 400;
            }

            return false;
        }

        return Storage::disk($storageDisk)->exists($path);
    }

    private function getHttpHeaders(string $path): array|false
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return get_headers($path);
        } finally {
            restore_error_handler();
        }
    }
}
