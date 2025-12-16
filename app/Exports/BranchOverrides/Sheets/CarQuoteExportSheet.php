<?php

namespace App\Exports\BranchOverrides\Sheets;

use App\Exports\BranchOverrides\CarQuoteExport;
use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet wrapper for CarQuoteExport
 * Implements WithTitle to set the Excel sheet tab name
 */
class CarQuoteExportSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private CarQuoteExport $export;

    public function __construct(CarQuoteService $carQuoteService)
    {
        $this->export = new CarQuoteExport($carQuoteService);
    }

    /**
     * Set the sheet title/tab name
     */
    public function title(): string
    {
        return 'Car Quote';
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

