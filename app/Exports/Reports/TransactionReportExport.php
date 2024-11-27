<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Events\AfterSheet;

class TransactionReportExport extends BaseReportsExport
{
    public function headings(): array
    {
        return [
            'Ref-ID',
            'Department',
            'Policy Number',
            'Transactions',
            'Policy Start Date',
            'Payment Due Date',
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
            'Line Of Business',
            'Sub-Type',
            'Customer Name',
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
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code ?? 'N/A',
            $quote->department ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->transactions ? $quote->transactions : 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ? $quote->payment_due_date : ($quote->due_date ?? 'N/A'),
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
            $quote->line_of_business ?? 'N/A',
            $quote->sub_type_line_of_business ?? 'N/A',
            $quote->customer_name ?? 'N/A',
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
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        self::performSum($event, ['G', 'H', 'I', 'J', 'K', 'L', 'K', 'M', 'N', 'O', 'Q']);
    }
}
