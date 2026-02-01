<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use App\Services\Savings\SavingsEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSavingsOCAEmailJob implements ShouldQueue
{
    use Queueable;

    protected string $quoteUID;
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUID, array $data = [])
    {
        $this->quoteUID = $quoteUID;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteUID);

        LoggerService::info('Savings OCA email job started');

        app(SavingsEmailService::class)->sendOCAEmail($this->quoteUID, $this->data);

        LoggerService::info('Savings OCA email sent');
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        LoggerService::error('Savings OCA email failed', ['exception' => $exception]);
    }
}
