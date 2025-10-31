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
     *
     * @param  array  $requestParams
     * @return Collection
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
            'Quote Code',
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
        $sageApiLog = $row->model?->sage_api_logs[0] ?? null;
        // Parse sage response to extract error message if available
        $sageResponse = $sageApiLog?->response ?? $this->notAvailable;

        // Format dates
        $paymentDate = $payment && $payment->captured_at ? Carbon::parse($payment->captured_at)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : $this->notAvailable;

        // Get lead status
        $leadStatus = $this->notAvailable;
        if ($row->model) {
            if (isset($row->model->quoteStatus)) {
                $leadStatus = $row->model->quoteStatus->text ?? $this->notAvailable;
            } elseif (isset($row->model->status)) {
                $leadStatus = $row->model->status;
            }
        }

        return [
            $row->id ?? $this->notAvailable,
            $row->model?->uuid ?? $this->notAvailable,
            $row->model?->code ?? $this->notAvailable,
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
     * Get export metadata with sage process specific information
     *
     * @param  array  $requestParams
     * @return array
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
