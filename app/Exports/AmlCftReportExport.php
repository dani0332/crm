<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\AMLService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class AmlCftReportExport implements FromCollection, WithEvents, WithHeadings, WithMapping, CsvExportableInterface
{
    use ModernCsvExportable;

    protected array $summary;
    protected $data;

    public function __construct()
    {
        $report = app(AMLService::class)->generateAmlCftReport();
        $this->data = $report['collection'];
        $this->summary = $report['summary'];
    }

    public function collection(array $requestParams = []): Collection
    {
        $year = Carbon::now()->year;
        
        // Create header rows for CSV
        $headerRows = collect([
            // Title row
            ['AFIA Insurance Brokerage Services LLC', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            // Subtitle row  
            ["AML/CFT Monitoring purpose Customer Risk Profile Report {$year}", '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            // Department row
            ['Requested By Compliance Dept.', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            // Empty row
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);

        // Merge header rows with actual data
        return $headerRows->merge($this->data);
    }

    public function headings(): array
    {
        // Simple CSV headings for email export compatibility
        return [
            'Customer Full Name',
            'Ref-ID',
            'Customer EID No/Trade license',
            'Customer Type',
            'Resident/Non-Resident customer',
            'Risk Score',
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
        ];
    }

    public function map($item): array
    {
        // Handle header rows - return as-is if it's already an array
        if (is_array($item) && !isset($item['code']) && !is_object($item)) {
            return $item;
        }
        
        // Convert object to array for consistent access
        $data = is_array($item) ? $item : (array) $item;
        
        return [
            isset($data['customer_first_name']) ? trim(($data['customer_first_name'] ?? '').' '.($data['customer_last_name'] ?? '')) : trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
            $data['code'] ?? '',
            $data['emirates_id'] ?? '',
            $data['customer_type'] ?? '',
            isset($data['residential_status']) ? ($data['residential_status'] === 'uaeResident' ? 'Resident' : 'Non-Resident') : 'Non-Resident',
            $data['risk_score'] ?? '',
            is_null($data['risk_score'] ?? null) ? 'N/A' : (($data['risk_score'] ?? 0) <= 25 ? 'Low' : (($data['risk_score'] ?? 0) <= 34 && ($data['risk_score'] ?? 0) >= 26 ? 'Medium' : 'High')),
            $data['policy_number'] ?? '',
            $data['quote_type_name'] ?? '',
            ($data['customer_type'] ?? CustomerTypeEnum::Individual) === CustomerTypeEnum::Individual ? ($data['premium_tenure'] ?? '') : ($data['transaction_volume'] ?? ''),
            $data['premium'] ?? '',
            $data['insurance_provider'] ?? '',
            $data['policy_start_date'] ?? '',
            $data['policy_expiry_date'] ?? '',
            $data['lead_status'] ?? '',
            ($data['is_owner_pep'] ?? 0) === 1 ? 'Yes' : 'No',
            $data['last_aml_screening_date'] ?? '',
            $data['remarks'] ?? 'N/A',
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

                $sheet->setCellValue('B'.($lastRow + 1), 'High Risk Customers');
                $sheet->setCellValue('D'.($lastRow + 1), $this->summary['high_risk'] ?? '');
                $sheet->setCellValue('B'.($lastRow + 2), 'Medium Risk Customers');
                $sheet->setCellValue('D'.($lastRow + 2), $this->summary['medium_risk'] ?? '');
                $sheet->setCellValue('B'.($lastRow + 3), 'Low Risk Customers');
                $sheet->setCellValue('D'.($lastRow + 3), $this->summary['low_risk'] ?? '');

                $sheet->setCellValue('A'.($lastRow + 9), 'Note:');
                $sheet->setCellValue('B'.($lastRow + 9), '"This report contains sensitive personal data. Do not share externally. For compliance use only."');
                $sheet->getStyle('B'.($lastRow + 9))->getFont()->setBold(true);
            },
        ];
    }

    /**
     * Get export metadata for AML CTF reports
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'exportType' => 'aml_ctf_report',
            'description' => 'AML/CFT Monitoring purpose Customer Risk Profile Report',
            'includesPII' => true, // Contains personally identifiable information
            'dataSource' => 'aml_service',
        ];
    }
}
