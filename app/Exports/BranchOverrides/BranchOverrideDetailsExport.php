<?php

namespace App\Exports\BranchOverrides;

use App\Enums\QuoteTypes;
use App\Exports\BranchOverrides\Sheets\BusinessQuoteExportSheet;
use App\Exports\BranchOverrides\Sheets\CarQuoteExportSheet;
use App\Exports\BranchOverrides\Sheets\PersonalQuoteExportSheet;
use App\Exports\BranchOverrides\Sheets\TravelQuoteExportSheet;
use App\Services\CarQuoteService;
use App\Services\TravelQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet Excel export for Branch Override Details
 * Contains multiple tabs with different quote exports showing branch override information
 */
class BranchOverrideDetailsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private array $requestParams = []
    ) {}

    /**
     * Define the sheets to be included in the export
     * Each sheet will appear as a separate tab in the Excel file
     */
    public function sheets(): array
    {
        return [
            new CarQuoteExportSheet(app(CarQuoteService::class)),
            new TravelQuoteExportSheet(app(TravelQuoteService::class)),
            new PersonalQuoteExportSheet('Life Quote', QuoteTypes::LIFE->value),
            new PersonalQuoteExportSheet('Savings Quote', QuoteTypes::SAVINGS->value),
            new PersonalQuoteExportSheet('Home Quote', QuoteTypes::HOME->value),
            new PersonalQuoteExportSheet('Pet Quote', QuoteTypes::PET->value),
            new PersonalQuoteExportSheet('Bike Quote', QuoteTypes::BIKE->value),
            new PersonalQuoteExportSheet('Cycle Quote', QuoteTypes::CYCLE->value),
            new PersonalQuoteExportSheet('Yacht Quote', QuoteTypes::YACHT->value),
            new BusinessQuoteExportSheet,
            new PersonalQuoteExportSheet('Cyber Quote', QuoteTypes::CYBER->value),
            new PersonalQuoteExportSheet('Smartphone Quote', QuoteTypes::DEVICE->value),
        ];
    }
}
