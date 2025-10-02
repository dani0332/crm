<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use App\Services\Reports\TransactionReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;

class TransactionReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private TransactionReportService $transactionReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);

        $this->columnTotals = collect();

        $totalsRow = $this->getEmptyRow();

        $totalsRow[0] = 'Totals';

        $this->columnTotals = collect($totalsRow);
    }
    public function headings(): array
    {
        return [
            'Ref-ID',
            'Department',
            'Policy Number',
            'Transactions',
            'Policy Start Date',
            'Payment Due Date',
            'Payment Ref ID',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Collected Amount',
            'Payment Date',
            'Unpaid',
            'Collects',
            'Insurer',
            'Currently Insured With',
            'Line Of Business',
            'Sub-Type',
            'Customer Name',
            'OE/AE',
            'Advisor',
            'Policy Issuer',
            'Invoice Description',
            'Payment Method',
            'Payment Gateway',
            'Insurer Invoice No.',
            'Insurer Invoice Date',
            'Broker Invoice No',
            'Commission Tax Invoice Number',
            'Commission Percentage',
            'Transaction Type',
            'Lead Status',
            'Lead Source',
            'Booking Date',
            'Sage Receipt ID',
            'Private Client',
            'Sub Source',
        ];
    }

    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);

        return $this->transactionReportService->getReportData($request);
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?\Illuminate\Database\Eloquent\Builder
    {
        $request = request()->merge($requestParams);

        return $this->transactionReportService->getReportQueryBuilder($request);
    }

    public function map($quote): array
    {
        $row = collect([
            $quote->code ?? 'N/A',
            $quote->department ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->transactions ?? 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ?? ($quote->due_date ?? 'N/A'),
            $quote->code ?? 'N/A',
            $this->resolveNumberFormat($quote->price_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->vat ?? 0),
            $this->resolveNumberFormat($quote->price_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->discount ?? 0),
            $this->resolveNumberFormat($quote->total_price ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->commission_vat ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->collected_amount ?? 0),
            $quote->payment_date ?? 'N/A',
            $this->resolveNumberFormat($quote->pending_balance ?? 0),
            $quote->collects ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->currently_insured_with_text ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->sub_type_line_of_business ?? 'N/A',
            $quote->customer_name ?? 'N/A',
            $quote->support_user ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->policy_issuer ?? 'N/A',
            $quote->invoice_description ?? 'N/A',
            $quote->payment_method ?? 'N/A',
            $quote->payment_gateway ?? 'N/A',
            $quote->insurer_invoice_number ?? 'N/A',
            $quote->insurer_tax_invoice_date ?? 'N/A',
            $quote->broker_invoice_number ?? 'N/A',
            $quote->insurer_commmission_invoice_number ?? 'N/A',
            $quote->commmission_percentage ?? 'N/A',
            $quote->transaction_type ?? 'N/A',
            $quote->quote_status ?? 'N/A',
            $quote->source ?? 'N/A',
            $quote->policy_booking_date ?? 'N/A',
            $quote->sage_reciept_id ?? 'N/A',
            $quote->pcp_tag_formatted ?? 'N/A',
            $quote->sub_source ?? 'N/A',
        ]);

        foreach ($this->columnTotals as $index => $field) {

            $sumColumns = [8, 9, 10, 11, 12, 13, 14, 15, 16, 18];

            if (is_numeric($row->get($index)) && in_array($index + 1, $sumColumns)) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }

        return $row->values()->toArray();
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;

        LoggerService::info(__CLASS__.' processChunkedQuery Start');

        $requestParams = request()->merge($requestParams);

        $query->chunk($chunkSize, function ($chunk) use (&$totalRecords, $stream) {
            $this->transactionReportService->formatData($chunk);

            // Now process ALL records in the chunk (just like the download path does)
            foreach ($chunk as $record) {
                fputcsv($stream, $this->map($record));
                $totalRecords++;
            }
        });

        $this->postDataRows($stream);

        return $totalRecords;
    }

    private function postDataRows($stream)
    {
        $totalsRow = $this->getEmptyRow();

        foreach ($this->columnTotals as $index => $key) {
            $totalsRow[$index] = $this->resolveNumberFormat($this->columnTotals->get($index));
        }

        fputcsv($stream, $totalsRow);
    }

    private function getEmptyRow(): array
    {
        return array_fill(0, count($this->map((object) [])), '');
    }
}
