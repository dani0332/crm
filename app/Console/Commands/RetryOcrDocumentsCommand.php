<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrDocumentRetryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\ProcessLeadOCRDataComparison;

class RetryOcrDocumentsCommand extends Command
{
    protected $signature = 'comparison:ocr-lead
                            {--start-date= : Inclusive start date (YYYY-MM-DD)}
                            {--end-date= : Inclusive end date (YYYY-MM-DD)}';

    protected $description = 'OCR lead comparison command';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $startDateInput = $this->option('start-date');
        $endDateInput = $this->option('end-date');

        $startDate = $this->parseDateOption($startDateInput, 'start-date');
        $endDate = $this->parseDateOption($endDateInput, 'end-date');

        if ($startDate->gt($endDate)) {
            $this->error('Start date must be on or before end date.');

            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Dispatching Lead OCR comparison jobs for car documents from %s to %s',
            $startDate->toDateString(),
            $endDate->toDateString()
        ));

        ProcessLeadOCRDataComparison::dispatch($startDate, $endDate)->onQueue('lead_ocr_data_comparison');
        //$stats = $this->ocrDocumentRetryService->retryCarDocuments($startDate, $endDate);

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

