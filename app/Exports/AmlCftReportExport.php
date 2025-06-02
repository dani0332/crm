<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use App\Services\AMLService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AmlCftReportExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    
    use Exportable;

    protected array $summary;
    protected $collection;

    public function __construct()
    {
        $report = app(AMLService::class)->generateAmlCftReport();
        $this->collection = $report['collection'];
        $this->summary = $report['summary'];
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        // Multi-row headings for title, subtitle, etc.
        return [
            ['AFIA Insurance Brokerage Services LLC'],
            ['AML/CFT Monitoring purpose Customer Risk Profile based Quarterly Report for Q4-2024'],
            ['Quarter - Q4-2024'],
            ['Requested By Compliance Dept.'],
            [
                'Customer Full Name',
                'Ref-ID',
                'Customer EID No/Trade license',
                'Customer Type',
                'Resident/Non-Resident customer',
                'Customer Risk Profile (High /Medium/Low)',
                'Insurance Policy Number',
                'Type of insurance Policy',
                'Number of Transactions',
                'Transaction Amount',
                'Name of the Insurance Company',
                'Policy Start Date',
                'Policy End Date',
                'Lead status',
                'Whether PEP Customer or Not',
                'Date of last AML screening',
                'Remarks',
            ],
        ];
    }

    public function map($item): array
    {
        // Ensure QuoteTypes::getName returns a string (the enum value)
        $quoteType = QuoteTypes::getName($item->quote_type_id);
        $quoteTypeName = $quoteType ? ($quoteType->value ?? (string)$quoteType) : '';

        return [
            isset($item->first_name) ? trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')) : trim(($item->customer_first_name ?? '') . ' ' . ($item->customer_last_name ?? '')),
            $item->code ?? '',
            $item->emirates_id ?? '',
            $item->customer_type ?? '',
            isset($item->customer_id)
                ? ($item->residential_status === 'uaeResident' || $item->customer_is_uae_resident === 1 ? 'Resident' : 'Non-Resident')
                : 'Non-Resident',
            is_null($item->risk_score) ? 'N/A' : ($item->risk_score <= 25 ? 'Low' : ($item->risk_score <= 34 && $item->risk_score >= 26 ? 'Medium' : 'High')),
            $item->policy_number ?? '',
            $quoteTypeName,
            $item->customer_type === CustomerTypeEnum::Individual ? $item->premium_tenure : $item->transaction_volume,
            $item->premium,
            $item->insurance_provider ?? '',
            $item->policy_start_date ?? '',
            $item->policy_end_date ?? '',
            $item->lead_status ?? '',
            $item->is_owner_pep ? 'Yes' : 'No',
            $item->last_aml_screening_date ?? '',
            $item->remarks ?? 'N/A',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Merge and style title rows
                $sheet->mergeCells('A1:R1');
                $sheet->mergeCells('A2:R2');
                $sheet->mergeCells('A3:R3');
                $sheet->mergeCells('A4:R4');
                $sheet->getStyle('A1:A1')->getFont()->setBold(true);

                // Style header row
                $sheet->getStyle('A5:R5')->getFont()->setBold(true);
                $sheet->getStyle('A5:R5')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9E1F2');

                // Example: Color data rows (optional)
                // $sheet->getStyle('A6:R10')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');

                // Add summary rows at the bottom
                $lastRow = $sheet->getHighestRow() + 2;
                $sheet->setCellValue("B{$lastRow}", 'Total Number of Customers');
                $sheet->setCellValue("D{$lastRow}", $this->summary['total_customers'] ?? '');
                $sheet->getStyle("B{$lastRow}:D{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("B{$lastRow}:D{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFFF00');

                $sheet->setCellValue("B" . ($lastRow + 1), 'High Risk Customers');
                $sheet->setCellValue("D" . ($lastRow + 1), $this->summary['high_risk'] ?? '');
                $sheet->setCellValue("B" . ($lastRow + 2), 'Medium Risk Customers');
                $sheet->setCellValue("D" . ($lastRow + 2), $this->summary['medium_risk'] ?? '');
                $sheet->setCellValue("B" . ($lastRow + 3), 'Low Risk Customers');
                $sheet->setCellValue("D" . ($lastRow + 3), $this->summary['low_risk'] ?? '');

                $sheet->setCellValue("A" . ($lastRow + 9), 'Note:');
                $sheet->setCellValue("B" . ($lastRow + 9), '"This report contains sensitive personal data. Do not share externally. For compliance use only."');
                $sheet->getStyle("B" . ($lastRow + 9))->getFont()->setBold(true);
            }
        ];
    }
} 