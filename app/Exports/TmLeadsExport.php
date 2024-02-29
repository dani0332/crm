<?php

namespace App\Exports;

use App\Traits\ExcelExportable;

class TmLeadsExport
{
    use ExcelExportable;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function headings(): array
    {
        return [
            'TM ID',
            'CUSTOMER NAME',
            'INSURANCE TYPE',
            'LEAD STATUS',
            'NOTES',
            'ENQUIER DATE',
            'ALLOCATION DATE',
            'NEXT FOLLOW-UP DATE',
            'ADVISOR',
            'CREATED AT',
            'UPDATED AT',
        ];
    }

    public function map($tmlead): array
    {
        return [
            $tmlead->cdb_id,
            $tmlead->customer_name,
            $tmlead->tm_insurance_types_text,
            $tmlead->tm_lead_status_text,
            $tmlead->notes,
            $tmlead->enquiry_date,
            $tmlead->allocation_date,
            $tmlead->next_followup_date,
            $tmlead->handlers_name,
            $tmlead->tm_created_at,
            $tmlead->tm_updated_at,
        ];
    }

    public function collection()
    {
        return $this->query->get();
    }
}
