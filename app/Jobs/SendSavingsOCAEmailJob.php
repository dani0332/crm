<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use App\Services\Savings\SavingsEmailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSavingsOCAEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 640;
    public int $timeout = 60;
    public int $tries = 4;
    public array $backoff = [30, 60, 120];
    protected ?string $uniqueIdOverride = null;

    public function __construct(
        protected string $quoteUID,
        protected array $data = []
    ) {
        $this->onQueue('shared');

        if ($this->data['force_send'] ?? false) {
            $this->uniqueIdOverride = "savings-oca-email-{$this->quoteUID}-force-".uniqid('', true);
        }
    }

    public function uniqueId(): string
    {
        return $this->uniqueIdOverride ?? "savings-oca-email-{$this->quoteUID}";
    }

    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteUID);
        LoggerService::info('Savings OCA email job started');

        app(SavingsEmailService::class)->sendOCAEmail($this->quoteUID, $this->data);

        LoggerService::info('Savings OCA email sent');
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::startQuoteLogging($this->quoteUID);
        LoggerService::error('Savings OCA email failed', [
            'quote_uuid' => $this->quoteUID,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_code' => $exception->getCode(),
            'exception_trace' => $exception->getTraceAsString(),
        ], $exception);
    }
}
