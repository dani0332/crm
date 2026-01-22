<?php

namespace App\Exports\BranchOverrides\Sheets;

use App\Exports\BranchOverrides\TravelQuoteExport;
use App\Services\TravelQuoteService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet wrapper for TravelQuoteExport
 * Implements WithTitle to set the Excel sheet tab name
 */
class TravelQuoteExportSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private TravelQuoteExport $export;

    public function __construct(TravelQuoteService $travelQuoteService)
    {
        $this->export = new TravelQuoteExport($travelQuoteService);
    }

    /**
     * Set the sheet title/tab name
     */
    public function title(): string
    {
        return 'Travel Quote';
    }

    /**
     * Delegate to the underlying export
     */
    public function collection($requestParams = [])
    {
        return $this->export->collection($requestParams);
    }

    /**
     * Delegate to the underlying export
     */
    public function headings(): array
    {
        return $this->export->headings();
    }

    /**
     * Delegate to the underlying export
     */
    public function map($quote): array
    {
        return $this->export->map($quote);
    }
}
