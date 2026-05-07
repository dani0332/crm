<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Models\EmbeddedTransaction;
use App\Models\SendUpdateLog;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SageProcessesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    private string $notAvailable = 'N/A';
    private $failedProcesses;

    public function __construct($failedProcesses)
    {
        $this->failedProcesses = $failedProcesses;
    }

    public function collection(array $requestParams = []): Collection
    {
        return $this->failedProcesses;
    }

    public function headings(): array
    {
        return [
            'Sage Log ID',
            'Quote UUID',
            'REF ID',
            'SU Ref ID',
            'EP Ref ID',
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
            'Sage Log Status',
            'Failed Sage API',
            'Failed API Error',
            'Error displayed in IMCRM',
        ];
    }

    public function map($row): array
    {
        $payment = $row->model?->payments[0] ?? null;
        $paymentDate = $payment && $payment->captured_at ? $payment->captured_at : $this->notAvailable;
        $leadCreateDate = $this->formatLeadCreateDate($row->model?->created_at);

        $leadStatus = $this->notAvailable;
        if ($row->model) {
            if (isset($row->model->quoteStatus)) {
                $leadStatus = $row->model->quoteStatus->text ?? $this->notAvailable;
            } elseif (isset($row->model->status)) {
                $leadStatus = $row->model->status;
            }
        }

        $isSendUpdate = $row->model_type === SendUpdateLog::class;
        $isEmbeddedTransaction = $row->model_type === EmbeddedTransaction::class;
        $mainLead = match (true) {
            $isSendUpdate => $row->model?->personalQuote,
            $isEmbeddedTransaction => $row->model?->quoteRequest,
            default => $row->model,
        };
        $sectionType = $row->section_type ?? null;
        $refId = $mainLead?->code ?? $this->notAvailable;
        $suRefId = $isSendUpdate ? ($row->model?->code ?? $this->notAvailable) : $this->notAvailable;
        $epRefId = match (true) {
            $sectionType === EmbeddedTransaction::class => $row->section?->code ?? $this->notAvailable,
            $isEmbeddedTransaction => $row->model?->code ?? $this->notAvailable,
            default => $this->notAvailable,
        };

        return [
            $row->id ?? $this->notAvailable,
            $mainLead?->uuid ?? $row->model?->uuid ?? $this->notAvailable,
            $refId,
            $suRefId,
            $epRefId,
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
            $row->insuranceProvider?->text ?? $this->notAvailable,
            $payment?->invoice_description ?? $this->notAvailable,
            $payment?->insurer_tax_number ?? $this->notAvailable,
            $payment?->insurer_commmission_invoice_number ?? $this->notAvailable,
            $leadStatus,
            $payment?->collected_sage_receipt_ids ?? $this->notAvailable,
            $row->sage_api_status ?? $this->notAvailable,
            $row->failed_api ?? $this->notAvailable,
            $row->failed_error ?? $this->notAvailable,
            $row->imcrm_error ?? $this->notAvailable,
        ];
    }

    public function title(): string
    {
        return 'Failed Sage Processes';
    }

    private function formatLeadCreateDate($createdAt): string
    {
        if (! $createdAt) {
            return $this->notAvailable;
        }

        try {
            $createdAt = Carbon::parse($createdAt);

            return $createdAt->format(config('constants.DATETIME_DISPLAY_FORMAT'));
        } catch (\Exception $e) {
            return $this->notAvailable;
        }
    }

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
