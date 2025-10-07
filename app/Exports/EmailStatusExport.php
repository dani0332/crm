<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Models\EmailStatus;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmailStatusExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private int $quoteId,
        private int $quoteTypeId
    ) {}

    /**
     * Get the data collection - this is used by the original implementation
     * and falls back when getQuery is not available
     */
    public function collection(array $requestParams = []): Collection
    {
        return EmailStatus::where('quote_id', $this->quoteId)
            ->where('quote_type_id', $this->quoteTypeId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return EmailStatus::where('quote_id', $this->quoteId)
            ->where('quote_type_id', $this->quoteTypeId)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Define the CSV headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'Quote Type ID',
            'Quote ID',
            'Email Address',
            'Message ID',
            'Email Status',
            'Email Subject',
            'Template ID',
            'Customer ID',
            'Reason',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map a database record to CSV row
     */
    public function map($emailStatus): array
    {
        return [
            $emailStatus->id,
            $emailStatus->quote_type_id,
            $emailStatus->quote_id,
            $emailStatus->email_address,
            $emailStatus->msg_id,
            $emailStatus->email_status,
            $emailStatus->email_subject,
            $emailStatus->template_id,
            $emailStatus->customer_id,
            $emailStatus->reason,
            $emailStatus->created_at ?? '',
            $emailStatus->updated_at ?? '',
        ];
    }

    /**
     * Get export metadata with email status specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'email_status',
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'exportType' => 'email_status_logs',
        ];
    }
}
