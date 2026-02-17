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

    protected string $quoteUID;
    protected array $data;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUID, array $data = [])
    {
        $this->quoteUID = $quoteUID;
        $this->data = $data;
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return "savings-oca-email-{$this->quoteUID}";
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
        LoggerService::startQuoteLogging($this->quoteUID);

        $exceptionDetails = [
            'quote_uuid' => $this->quoteUID,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_code' => $exception->getCode(),
            'exception_trace' => $exception->getTraceAsString(),
        ];

        LoggerService::error('Savings OCA email failed', $exceptionDetails, $exception);
    }
}
