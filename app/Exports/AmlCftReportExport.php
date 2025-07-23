<?php

declare(strict_types=1);

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\CustomerTypeEnum;
use App\Services\AMLService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AmlCftReportExport implements CsvExportableInterface, FromCollection, WithHeadings, WithMapping
{
    use Exportable, ModernCsvExportable {
        Exportable::download insteadof ModernCsvExportable;
    }

    protected array $summary;
    protected $data;

    public function __construct(array $requestParams = [])
    {
        // Map export parameters to the format expected by AMLService
        $serviceParams = empty($requestParams) ? [] : $this->mapExportParameters($requestParams);

        $report = app(AMLService::class)->generateAmlCftReport($serviceParams);
        $this->data = $report['collection'];
        $this->summary = $report['summary'];
    }

    public function collection(array $requestParams = []): Collection
    {
        // For Excel export, return only the actual data
        return $this->data;
    }

    public function headings(): array
    {
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
            trim((isset($data['first_name']) ? $data['first_name'] : '').' '.(isset($data['last_name']) ? $data['last_name'] : '')),
            $data['code'] ?? '',
            $data['emirates_id'] ?? '',
            $data['customer_type'] ?? '',
            isset($data['residential_status']) ? ($data['residential_status'] === 'uaeResident' ? 'Resident' : 'Non-Resident') : 'Non-Resident',
            $data['risk_score'] ?? '',
            is_null($data['risk_score'] ?? null) ? 'N/A' : (($data['risk_score'] ?? 0) <= 25 ? 'Low' : (($data['risk_score'] ?? 0) <= 34 && ($data['risk_score'] ?? 0) >= 26 ? 'Medium' : 'High')),
            $data['policy_number'] ?? '',
            $data['quote_type_name'] ?? '',
            ($data['customer_type'] ?? CustomerTypeEnum::Individual) === CustomerTypeEnum::Individual ? ($data['premium_tenure'] ?? '') : ($data['transaction_volume'] ?? ''),
            $data['price_with_vat'] ?? '',
            $data['insurance_provider'] ?? '',
            $data['policy_start_date'] ?? '',
            $data['policy_expiry_date'] ?? '',
            $data['lead_status'] ?? '',
            ($data['is_owner_pep'] ?? 0) === 1 ? 'Yes' : 'No',
            $data['last_aml_screening_date'] ?? '',
            $data['remarks'] ?? 'N/A',
        ];
    }

    /**
     * Override sendEmailWithCSVAttachment to support custom formatting
     */
    public function sendEmailWithCSVAttachment($recipientEmail, $emailSubject, $requestParams, $ccRecipients = [], $fileName = 'export')
    {
        $this->sendCustomFormattedCsvEmail($recipientEmail, $emailSubject, $requestParams, $ccRecipients);
    }

    /**
     * Send CSV email with custom formatting (headers and summary)
     */
    private function sendCustomFormattedCsvEmail($recipientEmail, $emailSubject, $requestParams, $ccRecipients = [])
    {
        // Use data already fetched in constructor - no need to fetch again
        $year = Carbon::now()->year;
        $csvContent = $this->generateCustomFormattedCsv($year, $this->data, $this->summary);
        $fileName = $requestParams['fileName'] ?? 'AML_CTF_Report';

        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'aml_ctf_');
        file_put_contents($tempFile, $csvContent);

        // Prepare email template data
        $templateData = [
            'recipientName' => $requestParams['recipientName'] ?? 'Valued User',
            'exportTitle' => 'AML/CFT Customer Risk Profile Report',
            'currentDate' => Carbon::now()->format('d M Y'),
            'recordCount' => $this->data->count(),
            'fileSize' => round(strlen($csvContent) / 1024, 2), // Approximate file size in KB
            'systemName' => 'AFIA Insurance Brokerage Services LLC',
        ];

        // Send email with attachment
        Mail::send('ExportCSVMail', $templateData, function ($message) use ($recipientEmail, $emailSubject, $tempFile, $fileName, $ccRecipients) {
            $message->to($recipientEmail)
                ->subject($emailSubject)
                ->attach($tempFile, [
                    'as' => $fileName.'.csv',
                    'mime' => 'text/csv',
                ]);

            if (! empty($ccRecipients)) {
                $message->cc($ccRecipients);
            }
        });

        // Clean up temporary file
        unlink($tempFile);
    }

    /**
     * Generate CSV content with custom headers and summary
     */
    private function generateCustomFormattedCsv($year, $data = null, $summary = null): string
    {
        // Use provided data or fall back to cached data
        $data = $data ?? $this->data;
        $summary = $summary ?? $this->summary;

        $output = fopen('php://temp', 'r+');

        // Add custom headers
        fputcsv($output, ['AFIA Insurance Brokerage Services LLC']);
        fputcsv($output, ["AML/CFT Monitoring purpose Customer Risk Profile Report {$year}"]);
        fputcsv($output, ['Requested By Compliance Dept.']);
        fputcsv($output, []); // Empty row

        // Add column headers
        fputcsv($output, $this->headings());

        // Add data rows
        foreach ($data as $record) {
            fputcsv($output, $this->map($record));
        }

        // Add empty rows before summary
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);

        // Add summary section
        fputcsv($output, ['', 'Total Number of Customers', '', $summary['total_customers'] ?? '']);
        fputcsv($output, ['', 'High Risk Customers', '', $summary['high_risk'] ?? '']);
        fputcsv($output, ['', 'Medium Risk Customers', '', $summary['medium_risk'] ?? '']);
        fputcsv($output, ['', 'Low Risk Customers', '', $summary['low_risk'] ?? '']);

        // Add note section
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, ['Note:', 'This report contains sensitive personal data. Do not share externally. For compliance use only.']);

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Map export parameters to the format expected by AMLService
     */
    private function mapExportParameters(array $requestParams): array
    {
        return [
            'amlCreatedStartDate' => $requestParams['date_range']['start'] ?? $requestParams['amlCreatedStartDate'] ?? null,
            'amlCreatedEndDate' => $requestParams['date_range']['end'] ?? $requestParams['amlCreatedEndDate'] ?? null,
            'searchType' => $requestParams['filters']['searchType'] ?? $requestParams['searchType'] ?? null,
            'searchField' => $requestParams['filters']['searchField'] ?? $requestParams['searchField'] ?? null,
            'quoteType' => $requestParams['filters']['quoteType'] ?? $requestParams['quoteType'] ?? null,
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
