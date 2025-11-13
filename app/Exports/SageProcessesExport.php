<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Export class for failed sage processes
 *
 * This class handles the export of failed sage processes data to Excel format.
 */
class SageProcessesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    /**
     * @var string Default value for unavailable data
     */
    private string $notAvailable = 'N/A';

    /**
     * @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    private $failedProcesses;

    /**
     * Constructor
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $failedProcesses
     */
    public function __construct($failedProcesses)
    {
        $this->failedProcesses = $failedProcesses;
    }

    /**
     * Get the collection of failed processes
     */
    public function collection(array $requestParams = []): Collection
    {
        return $this->failedProcesses;
    }

    /**
     * Define the headings for the Excel export - matching frontend table columns
     */
    public function headings(): array
    {
        return [
            'Sage Pro. ID',
            'Quote UUID',
            'REF ID',
            'SU Ref ID',
            'Lead Create Date',
            'Policy Number',
            'Price Vat Applicable',
            'Price Vat',
            'Discount',
            'Total Price',
            'Commission (VAT applicable)',
            'Commission Vat',
            'Total Commission',
            'Payment Date',
            'Payment Status',
            'Provider',
            'Invoice Description',
            'Insurer Tax Invoice No.',
            'Insurer Commission Tax Invoice No.',
            'Lead Status',
            'Sage Receipt ID',
            'Sage Proc. Status',
            'Failed Sage API',
            'Failed API Error',
            'Error displayed in IMCRM',
        ];
    }

    /**
     * Map the data for each row in the Excel export
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        // Extract payment information (first payment if exists)
        $payment = $row->model?->payments[0] ?? null;

        // Extract sage API log information (first failed log)
        $sageApiLog = $row->model?->sageApiLogs[0] ?? null;
        // Parse sage response to extract error message if available
        $sageResponse = $sageApiLog?->response ?? $this->notAvailable;

        // Format dates
        // Note: Payment model's getCapturedAtAttribute accessor already formats captured_at using DATETIME_DISPLAY_FORMAT
        $paymentDate = $payment && $payment->captured_at ? $payment->captured_at : $this->notAvailable;

        // Format lead create date - handle custom formats like "02-Jul-2025 01:09pm"
        $leadCreateDate = $this->formatLeadCreateDate($row->model?->created_at);

        // Get lead status
        $leadStatus = $this->notAvailable;
        if ($row->model) {
            if (isset($row->model->quoteStatus)) {
                $leadStatus = $row->model->quoteStatus->text ?? $this->notAvailable;
            } elseif (isset($row->model->status)) {
                $leadStatus = $row->model->status;
            }
        }

        // Determine if this is SendUpdateLog or Main Lead
        $isSendUpdate = $row->model_type === \App\Models\SendUpdateLog::class;
        $refId = $isSendUpdate ? $this->notAvailable : ($row->model?->code ?? $this->notAvailable);
        $suRefId = $isSendUpdate ? ($row->model?->code ?? $this->notAvailable) : $this->notAvailable;

        return [
            $row->id ?? $this->notAvailable,
            $row->model?->uuid ?? $this->notAvailable,
            $refId,
            $suRefId,
            $leadCreateDate,
            $row->model?->policy_number ?? $this->notAvailable,
            $payment?->price_vat_applicable ?? $this->notAvailable,
            $payment?->price_vat ?? $this->notAvailable,
            $payment?->discount_value ?? $this->notAvailable,
            $payment?->total_price ?? $this->notAvailable,
            $payment?->commission_vat_applicable ?? $payment?->commission_vat_not_applicable ?? $this->notAvailable,
            $payment?->commission_vat ?? $this->notAvailable,
            $payment?->commission ?? $this->notAvailable,
            $paymentDate,
            $payment?->paymentStatus?->text ?? $payment?->payment_status ?? $this->notAvailable,
            $row->insurance_provider?->text ?? $this->notAvailable,
            $payment?->invoice_description ?? $this->notAvailable,
            $payment?->insurer_tax_number ?? $this->notAvailable,
            $payment?->insurer_commmission_invoice_number ?? $this->notAvailable,
            $leadStatus,
            $row->collected_sage_receipt_ids ?? $this->notAvailable,
            $row->status ?? $this->notAvailable,
            $sageApiLog?->sage_end_point ?? $this->notAvailable,
            $sageResponse,
            $row->imcrm_error ?? $this->notAvailable,
        ];
    }

    /**
     * Define the title for the Excel sheet
     */
    public function title(): string
    {
        return 'Failed Sage Processes';
    }

    /**
     * Format lead create date - handles custom date formats
     *
     * @param  mixed  $createdAt
     */
    private function formatLeadCreateDate($createdAt): string
    {
        if (! $createdAt) {
            return $this->notAvailable;
        }

        try {
            // Check if date is in custom format like "02-Jul-2025 01:09pm"
            if (is_string($createdAt) && preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}\s+\d{1,2}:\d{2}(am|pm)$/i', $createdAt)) {
                // Parse custom format: "02-Jul-2025 01:09pm"
                $createdAt = Carbon::createFromFormat('d-M-Y h:ia', $createdAt);
            } elseif (! $createdAt instanceof Carbon) {
                // Parse other formats
                $createdAt = Carbon::parse($createdAt);
            }

            return $createdAt->format(config('constants.DATETIME_DISPLAY_FORMAT'));
        } catch (\Exception $e) {
            return $this->notAvailable;
        }
    }

    /**
     * Get export metadata with sage process specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'sage_processes',
            'exportType' => 'sage_failed_processes',
            'recordCount' => $this->failedProcesses->count(),
        ];
    }
}
