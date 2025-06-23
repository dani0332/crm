<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\CustomerTypeEnum;
use App\Services\AMLService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class AmlCftReportExport implements CsvExportableInterface, FromCollection, WithEvents, WithHeadings, WithMapping
{
    use Exportable, ModernCsvExportable {
        Exportable::download insteadof ModernCsvExportable;
    }

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
        // For Excel export, return only the actual data
        // Headers, summary, and styling will be handled by registerEvents()
        return $this->data;
    }

    public function headings(): array
    {
        // Return simple headings - complex formatting handled by registerEvents()
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
                $year = Carbon::now()->year;

                // Insert 4 rows at the top for headers
                $sheet->insertNewRowBefore(1, 4);

                // Add title rows
                $sheet->setCellValue('A1', 'AFIA Insurance Brokerage Services LLC');
                $sheet->setCellValue('A2', "AML/CFT Monitoring purpose Customer Risk Profile Report {$year}");
                $sheet->setCellValue('A3', 'Requested By Compliance Dept.');
                // Row 4 is intentionally left empty

                // Merge title rows across all columns
                $sheet->mergeCells('A1:R1');
                $sheet->mergeCells('A2:R2');
                $sheet->mergeCells('A3:R3');

                // Style title rows
                $sheet->getStyle('A1')->getFont()->setBold(true);

                // Center align title rows
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('left');

                // Style header row (now row 5)
                $sheet->getStyle('A5:R5')->getFont()->setBold(true);
                $sheet->getStyle('A5:R5')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle('A5:R5')->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Style data rows with borders
                $lastRow = $sheet->getHighestRow() + 3;
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

                // Add note section
                $noteRow = $lastRow + 8;
                $sheet->setCellValue("A{$noteRow}", 'Note:');
                $sheet->setCellValue("B{$noteRow}", '"This report contains sensitive personal data. Do not share externally. For compliance use only."');
                $sheet->getStyle("A{$noteRow}:B{$noteRow}")->getFont()->setBold(true);

            },
        ];
    }

    /**
     * Override emailCSV to provide a simpler CSV structure for email
     */
    public function emailCSV(string $fileName, array $requestParams = []): \Illuminate\Http\JsonResponse
    {
        // For email, we'll create a simplified CSV without Excel formatting
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');
        $requestParams = $this->processEmailParameters($fileName, $requestParams);

        // Add CSV-specific parameters
        $requestParams['exportTitle'] = 'AML/CFT Report';
        $requestParams['fileName'] = $fileName;

        // Dispatch the email job
        \App\Jobs\ExportCsvAndSendEmailJob::dispatch(
            static::class,
            $requestParams['recipientEmail'],
            $requestParams
        );

        return response()->json([
            'message' => 'Your AML/CTF report is being processed. You will receive an email with the CSV file shortly.',
        ]);
    }

    /**
     * Process email parameters (copied from trait but made accessible)
     */
    private function processEmailParameters(string $fileName, array $requestParams): array
    {
        // Ensure we have a user for the job context
        if (! isset($requestParams['user']) && \Illuminate\Support\Facades\Auth::check()) {
            $requestParams['user'] = \Illuminate\Support\Facades\Auth::user();
        }

        // Set recipient email if not provided
        if (empty($requestParams['recipientEmail'])) {
            if (! \Illuminate\Support\Facades\Auth::check()) {
                throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('', 'User not authenticated');
            }

            $currentUser = \App\Models\User::find(\Illuminate\Support\Facades\Auth::user()->id);
            $requestParams['recipientEmail'] = $currentUser->email;
            $requestParams['recipientName'] = $currentUser->name;
        }

        // Set filename and export title
        $requestParams['fileName'] = $fileName;

        if (empty($requestParams['exportTitle'])) {
            $requestParams['exportTitle'] = 'AML/CTF Report';
        }

        // Set subject if not provided
        if (empty($requestParams['subject'])) {
            $currentDate = Carbon::now()->format('d-m-Y');
            $requestParams['subject'] = "{$requestParams['exportTitle']} Export - {$currentDate}";
        }

        return $requestParams;
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
