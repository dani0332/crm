<?php

namespace App\Exports;

use App\Traits\ExcelExportable;
use Illuminate\Support\Collection;

class KycLogs
{
    use ExcelExportable;

    public function __construct(public Collection $data) {}

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'AML ID',
            'Input',
            'Search type',
            'Match Found',
            'Result Found',
            'Created At',
            'AML CRM Status',
            'Final Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->uuid ?? '',
            $item->id,
            $item->input,
            $item->search_type,
            $item->match_found,
            $item->results_found,
            date(config('constants.datetime_format'), strtotime($item->created_at)),
            $item->aml_status,
            $item->decision,
        ];
    }
}
