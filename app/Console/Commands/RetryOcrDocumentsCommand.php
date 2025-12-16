<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrDocumentRetryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetryOcrDocumentsCommand extends Command
{
    protected $signature = 'ocr:retry-documents
                            {--start-date= : Inclusive start date (YYYY-MM-DD)}
                            {--end-date= : Inclusive end date (YYYY-MM-DD)}';

    protected $description = 'Retry OCR processing for car documents within a date range.';

    public function __construct(private OcrDocumentRetryService $ocrDocumentRetryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $startDateInput = $this->option('start-date');
        $endDateInput = $this->option('end-date');

        $startDate = $this->parseDateOption($startDateInput, 'start-date');
        $endDate = $this->parseDateOption($endDateInput, 'end-date');

        if ($startDateInput && ! $startDate) {
            return Command::FAILURE;
        }

        if ($endDateInput && ! $endDate) {
            return Command::FAILURE;
        }

        $startDate ??= Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $endDate ??= Carbon::now()->subMonthNoOverflow()->endOfMonth();

        if ($startDate->gt($endDate)) {
            $this->error('Start date must be on or before end date.');

            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Dispatching OCR retry jobs for car documents from %s to %s',
            $startDate->toDateString(),
            $endDate->toDateString()
        ));

        $stats = $this->ocrDocumentRetryService->retryCarDocuments($startDate, $endDate);

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(
                fn ($value, $key) => [str_replace('_', ' ', $key), $value]
            )->values()
        );

        LoggerService::info(self::class.' - Command finished', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'stats' => $stats,
        ]);

        $this->info('OCR retry command completed.');

        return Command::SUCCESS;
    }

    private function parseDateOption(?string $dateInput, string $label): ?Carbon
    {
        if (! $dateInput) {
            return null;
        }

        try {
            return Carbon::parse($dateInput)->startOfDay();
        } catch (\Throwable $exception) {
            LoggerService::info(self::class.' - Invalid date option provided', [
                'label' => $label,
                'value' => $dateInput,
                'error' => $exception->getMessage(),
            ]);

            $this->error("Invalid {$label} value: {$dateInput}. Use YYYY-MM-DD.");

            return null;
        }
    }
}

