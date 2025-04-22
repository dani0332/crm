<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UtmReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
                'utm_source' => $item['utm_source'] ?? '',
                'utm_medium' => $item['utm_medium'] ?? '',
                'utm_campaign' => $item['utm_campaign'] ?? '',
                'leads_count' => $item['leads_count'] ?? 0,
                'authorized' => $item['authorized'] ?? 0,
                'captured' => $item['captured'] ?? 0,
                'booked_policies' => $item['booked_policies'] ?? 0,
                'authorized_sum' => $item['authorized_sum'] ?? 0,
                'captured_sum' => $item['captured_sum'] ?? 0,
                'total_sum' => $item['total_sum'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
            'Leads',
            'Authorized',
            'Captured',
            'Booked Policies',
            'Authorized (AED)',
            'Captured (AED)',
            'Total Price',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 