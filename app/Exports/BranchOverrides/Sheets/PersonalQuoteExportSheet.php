<?php

namespace App\Exports\BranchOverrides\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Exports\BranchOverrides\PersonalQuotesExport;

/**
 * Sheet wrapper for TravelQuoteExport
 * Implements WithTitle to set the Excel sheet tab name
 */
class PersonalQuoteExportSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private PersonalQuotesExport $export;
    private string $title;
    
    public function __construct(string $title, string $quoteType)
    {
        $this->title = $title;
        $this->export = new PersonalQuotesExport($quoteType);
    }

    /**
     * Set the sheet title/tab name
     */
    public function title(): string
    {
        return $this->title;
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

