<?php

namespace App\Jobs;

use App\Services\Life\EmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendOCAEmailJob implements ShouldQueue
{
    use Queueable;

    protected string $quoteUID;
    protected array $planIds;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUID, array $planIds = [])
    {
        $this->quoteUID = $quoteUID;
        $this->planIds = $planIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteUID);

        LoggerService::info('Life OCA email job started');

        app(EmailService::class)->sendOCAEmail($this->quoteUID, $this->planIds);

        LoggerService::info('Life OCA email sent');
    }

    public function failed(Throwable $exception)
    {
        LoggerService::error('Life OCA email  failed', ['exception' => $exception]);
    }
}
