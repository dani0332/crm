<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Reports\BaseReportsExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export class for failed sage processes
 * 
 * This class handles the export of failed sage processes data to Excel format.
 */
class SageProcessesExport extends BaseReportsExport implements WithTitle
{
    /**
     * @var string Default value for unavailable data
     */
    private string $notAvailable = 'N/A';

    /**
     * Define the headings for the Excel export
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'SAGE PROCESS ID',
            'QUOTE CODE',
            'QUOTE TYPE',
            'QUOTE STATUS',
            'INSURANCE PROVIDER',
            'USER NAME',
            'USER EMAIL',
            'SAGE REQUEST TYPE',
            'SAGE ENDPOINT',
            'SAGE API STATUS',
            'SAGE RESPONSE',
            'PROCESS MESSAGE',
            'PROCESS CREATED AT',
            'PROCESS UPDATED AT',
            'SAGE API LOG CREATED AT',
        ];
    }

    /**
     * Map the data for each row in the Excel export
     *
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        // Parse sage response to extract error message if available
        $sageResponse = $this->notAvailable;
        if (!empty($row->sage_response)) {
            $responseData = json_decode($row->sage_response, true);
            if (is_array($responseData)) {
                // Try to extract error message from common response formats
                $sageResponse = $responseData['error']['message']['value'] ?? 
                               $responseData['message'] ?? 
                               $responseData['error'] ?? 
                               substr($row->sage_response, 0, 200); // Limit to 200 chars
            } else {
                $sageResponse = substr($row->sage_response, 0, 200);
            }
        }

        // Format dates
        $processCreatedAt = $row->created_at ? date('d-m-Y H:i:s', strtotime($row->created_at)) : $this->notAvailable;
        $processUpdatedAt = $row->updated_at ? date('d-m-Y H:i:s', strtotime($row->updated_at)) : $this->notAvailable;
        $sageApiLogCreatedAt = $row->sage_api_log_created_at ? date('d-m-Y H:i:s', strtotime($row->sage_api_log_created_at)) : $this->notAvailable;

        return [
            $row->id ?? $this->notAvailable,
            $row->quote_code ?? $this->notAvailable,
            $row->quote_type_name ?? $this->notAvailable,
            $row->quote_status ?? $this->notAvailable,
            $row->insurance_provider_name ?? $this->notAvailable,
            $row->user_name ?? $this->notAvailable,
            $row->user_email ?? $this->notAvailable,
            $row->sage_request_type ?? $this->notAvailable,
            $row->sage_end_point ?? $this->notAvailable,
            $row->sage_api_status ?? $this->notAvailable,
            $sageResponse,
            $row->message ?? $this->notAvailable,
            $processCreatedAt,
            $processUpdatedAt,
            $sageApiLogCreatedAt,
        ];
    }

    /**
     * Define the title for the Excel sheet
     *
     * @return string
     */
    public function title(): string
    {
        return 'Failed Sage Processes';
    }
}

