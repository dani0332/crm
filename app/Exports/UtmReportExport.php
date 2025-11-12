<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UtmReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            return [
                'utm_id' => $item['utm_id'] ?? '',
                'utm_source' => $item['utm_source'] ?? '',
                'utm_medium' => $item['utm_medium'] ?? '',
                'utm_campaign' => $item['utm_campaign'] ?? '',
                'utm_term' => $item['utm_term'] ?? '',
                'utm_content' => $item['utm_content'] ?? '',
                'leads_count' => $item['leads_count'] ?? 0,
                'authorized' => $item['authorized'] ?? 0,
                'captured' => $item['captured'] ?? 0,
                'booked_policies' => isset($item['booked_policies']) && $item['booked_policies'] !== null ? $item['booked_policies'] : '0',
                'authorized_sum' => $item['authorized_sum'] ?? 0,
                'captured_sum' => $item['captured_sum'] ?? 0,
                'total_sum' => $item['total_sum'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'UTM ID',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
            'UTM Term',
            'UTM Content',
            'Leads',
            'Authorized',
            'Captured',
            'Booked Policies',
            'Authorized (AED)',
            'Captured (AED)',
            'Total Price (AED)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function map($row): array
    {
        return [
            $row['utm_id'],
            $row['utm_source'],
            $row['utm_medium'],
            $row['utm_campaign'],
            $row['utm_term'],
            $row['utm_content'],
            $row['leads_count'],
            $row['authorized'],
            $row['captured'],
            isset($row['booked_policies']) && $row['booked_policies'] !== null ? $row['booked_policies'] : '0',
            $row['authorized_sum'],
            $row['captured_sum'],
            $row['total_sum'],
        ];
    }
}
