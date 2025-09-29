<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\EpBookingContext;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Models\EmbeddedTransaction;
use App\Services\EpExcessCashbackService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EpWatermarkDocumentJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;
    public $backoff = 30;

    private EmbeddedTransaction $embeddedTransaction;
    private array $watermarkableDocTypeCodes;

    private string $logPrefix = 'EpWatermarkDocument - Job:';
    private array $logExtra = [];

    public function __construct(
        public EpBookingContext $context
    ) {
        $this->logExtra = [
            'etId' => $this->context->etId,
            'quoteId' => $this->context->quoteId,
            'quoteTypeId' => $this->context->quoteTypeId,
            'quoteUUID' => $this->context->quoteUUID,
        ];

        $this->watermarkableDocTypeCodes = [
            QuoteDocumentsEnum::POLICY_SCHEDULE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE
        ];
    }

    public function handle(): void
    {
        LoggerService::info("{$this->logPrefix} Starting", extra: $this->logExtra);

        $this->embeddedTransaction = EmbeddedTransaction::find($this->context->etId);
        $documents = $this->embeddedTransaction->documents()
            ->whereIn('document_type_code', $this->watermarkableDocTypeCodes)->get();

        $epEcbService = app(EpExcessCashbackService::class, ['context' => $this->context]);
        $epEcbService->processWatermarkDocuments($documents, $this->watermarkableDocTypeCodes);

        LoggerService::info("{$this->logPrefix} Completed", extra: $this->logExtra);
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Failed", extra: [
            ...$this->logExtra,
            'error' => $exception->getMessage()
        ]);
    }

    public function shouldRetry(Throwable $exception): bool
    {
        // Retry for file processing issues only
        return str_contains($exception->getMessage(), 'file') ||
               str_contains($exception->getMessage(), 'permission') ||
               str_contains($exception->getMessage(), 'temporary');
    }
}
