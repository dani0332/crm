<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Traits\ModernCsvExportable;

/**
 * Export class for failed sage processes
 *
 * This class handles the export of failed sage processes data to Excel format.
 */
class SageProcessesExport extends CsvExportableInterface
{
    use ModernCsvExportable;

    /**
     * @var string Default value for unavailable data
     */
    private string $notAvailable = 'N/A';

    /**
     * @var \Illuminate\Support\Collection
     */
    private $failedProcesses;

    /**
     * Constructor
     */
    public function __construct($failedProcesses)
    {
        $this->failedProcesses = $failedProcesses;
    }

    /**
     * Get the collection of failed processes
     */
    public function collection()
    {
        return $this->failedProcesses;
    }

    /**
     * Define the headings for the Excel export
     */
    public function headings(): array
    {
        return [ 
            'Sage Process ID',
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
            'Sage Process Status',
            'Failed Sage API',
            'Failed API Error',
            'Updated At',
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
        $payment = $row->model->payments[0] ?? null;
        
        // Extract sage API log information (first failed log)
        $sageApiLog = $row->model->sage_api_logs[0] ?? null;
        
        // Parse sage response to extract error message if available
        $sageResponse = $this->notAvailable;
        if ($sageApiLog && !empty($sageApiLog->response)) {
            $responseData = json_decode($sageApiLog->response, true);
            if (is_array($responseData)) {
                // Try to extract error message from common response formats
                $sageResponse = $responseData['error']['message']['value'] ??
                               $responseData['message'] ??
                               $responseData['error'] ??
                               substr($sageApiLog->response, 0, 200); // Limit to 200 chars
            } else {
                $sageResponse = substr($sageApiLog->response, 0, 200);
            }
        }

        // Format dates
        $paymentDate = $payment && $payment->payment_due_date ? date('d-m-Y H:i:s', strtotime($payment->payment_due_date)) : $this->notAvailable;
        $updatedAt = $row->updated_at ? date('d-m-Y H:i:s', strtotime($row->updated_at)) : $this->notAvailable;

        // Get lead status
        $leadStatus = $this->notAvailable;
        if ($row->model && isset($row->model->quote_status)) {
            $leadStatus = $row->model->quote_status->text ?? $this->notAvailable;
        } elseif ($row->model && isset($row->model->status)) {
            $leadStatus = $row->model->status;
        }

        return [
            $row->id ?? $this->notAvailable,
            $row->model->code ?? $this->notAvailable,
            $row->model->policy_number ?? $this->notAvailable,
            $payment->price_vat_applicable ?? $this->notAvailable,
            $payment->price_vat ?? $this->notAvailable,
            $payment->discount_value ?? $this->notAvailable,
            $payment->total_price ?? $this->notAvailable,
            $payment->commission_vat_applicable ?? $payment->commission_vat_not_applicable ?? $this->notAvailable,
            $payment->commission_vat ?? $this->notAvailable,
            $payment->commission ?? $this->notAvailable,
            $paymentDate,
            $payment->paymentStatus->text ?? $payment->payment_status ?? $this->notAvailable,
            $row->insurance_provider->text ?? $this->notAvailable,
            $payment->invoice_description ?? $this->notAvailable,
            $payment->insurer_tax_number ?? $this->notAvailable,
            $payment->insurer_commmission_invoice_number ?? $this->notAvailable,
            $leadStatus,
            $row->collected_sage_receipt_ids ?? $this->notAvailable,
            $row->status ?? $this->notAvailable,
            $sageApiLog->sage_end_point ?? $this->notAvailable,
            $sageResponse,
            $updatedAt,
        ];
    }

    /**
     * Define the title for the Excel sheet
     */
    public function title(): string
    {
        return 'Failed Sage Processes';
    }
}
