<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HealthQuotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Ref ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'WC ADVISOR',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'HEALTH TEAM TYPE',
            'TRANSAPP CODE',
            'LOST REASON',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LEAD TYPE',
            'SALARY BAND',
            'MEMBER CATEGORY',
            'CURRENTLY INSURED WITH',
            'IS ECOMMERCE',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->quote_status_id_text,
            $quote->advisor_id_text,
            $quote->wcu_id_text,
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->updated_at)),
            $quote->health_team_type,
            $quote->transapp_code,
            $quote->lost_reason,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->lead_type_id_text,
            $quote->salary_band_id_text,
            $quote->member_category_id_text,
            $quote->currently_insured_with_id_text,
            $quote->is_ecommerce ? 'Yes' : 'No',
        ];
    }
}
