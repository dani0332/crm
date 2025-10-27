<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * BuyLeadsExport
 *
 * Exports buy leads data to Excel, following the structure and conventions of NonPUAQuoteExport.
 * Accepts a collection of buy leads data and formats it for export.
 */
class BuyLeadsExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable;

    protected $type;
    protected Collection $data;

    /**
     * Constructor
     *
     * @param  Collection|array  $data
     */
    public function __construct($data, ?string $type = null)
    {
        $this->data = collect($data);
        $this->type = $type;
    }

    /**
     * Return the collection for export
     */
    public function collection(): Collection
    {
        // You can add summary/statistics rows here if needed, similar to NonPUAQuoteExport
        return $this->data;
    }

    /**
     * Define the Excel headings
     */
    public function headings(): array
    {
        if ($this->type === 'summary') {
            return [
                'Advisor',
                'Requested Count',
                'Allocated Count',
                'Teams',
                'Created At',
                'Quote Type ID',
            ];
        }
        if ($this->type === 'detailed') {
            return [
                'RefID',
                'Advisor Requested',
                'Assignment Type',
                'Lead Created At',
                'Advisor',
                'Lead Status',
                'Cost',
                'Teams',
                'Department',
                'Advisor Code',
            ];
        }

        return [];
    }

    /**
     * Map each row for export
     *
     * @param  object  $row
     */
    public function map($row): array
    {
        if ($this->type === 'summary') {
            return [
                $row->advisor ?? '',
                $row->requested_count ?? 0,
                $row->allocated_count ?? 0,
                $row->teams ?? '',
                $row->created_at ?? '',
                $row->quote_type_id ?? '',
            ];
        }
        if ($this->type === 'detailed') {
            return [
                $row->RefID ?? '',
                $row->advisor_requested ?? '',
                $row->assignment_type ?? '',
                $row->lead_created_at ?? '',
                $row->advisor ?? '',
                $row->lead_status ?? '',
                $row->cost ?? '',
                $row->teams ?? '',
                $row->department ?? '',
                $row->advisor_code ?? '',
            ];
        }

        return [];
    }
}
