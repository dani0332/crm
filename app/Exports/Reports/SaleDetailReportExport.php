<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Events\AfterSheet;

class SaleDetailReportExport extends BaseReportsExport
{
    public function headings(): array
    {
        return [
            'Ref-ID',
            'Policy No.',
            'Department',
            'Transactions',
            'Policy Start Date',
            'Payment Due Date',
            'Team',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Total Commission',
            'Collects',
            'Tax Invoice Number',
            'Tax Invoice Date',
            'Transaction Payment Status',
            'Date Paid',
            'Collected Amount',
            'Customer Name',
            'Customer Type',
            'Insurer',
            'Line of Business',
            'Sub-Type',
            'Advisor',
            'Policy Issuer ',
            'Commission Tax Invoice Number',
            'Commission Percentage',
            'Transaction Type',
            'Lead Source',
            'Booking Date',
            'Sage Receipt ID',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->department ?? 'N/A',
            $quote->transactions ? $quote->transactions : 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ? $quote->payment_due_date : ($quote->due_date ?? 'N/A'),
            $quote->team ?? 'N/A',
            $this->resolveNumberFormat($quote->price_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->vat ?? 0),
            $this->resolveNumberFormat($quote->price_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->discount ?? 0),
            $this->resolveNumberFormat($quote->total_price ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->commission_vat ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->total_commission ?? 0),
            $quote->collects ?? 'N/A',
            $quote->insurer_tax_invoice_number ?? 'N/A',
            $quote->insurer_tax_invoice_date ?? 'N/A',
            $quote->transaction_payment_status ?? 'N/A',
            $quote->date_paid ?? 'N/A',
            $this->resolveNumberFormat($quote->collected_amount ?? 0),
            $quote->customer_name ?? 'N/A',
            $quote->customer_type ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->sub_type_line_of_business ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->policy_issuer ?? 'N/A',
            $quote->insurer_commmission_invoice_number ?? 'N/A',
            $quote->commmission_percentage ?? 'N/A',
            $quote->transaction_type ?? 'N/A',
            $quote->source ?? 'N/A',
            $quote->policy_booking_date ?? 'N/A',
            $quote->sage_reciept_id ?? 'N/A',
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        self::performSum($event, ['I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'W']);
    }
}
