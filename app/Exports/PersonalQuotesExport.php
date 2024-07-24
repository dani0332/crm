<?php

namespace App\Exports;

use App\Enums\QuoteTypes;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use App\Traits\ExcelExportable;

class PersonalQuotesExport
{
    use ExcelExportable;

    private $quoteType = '';
    private $quoteTypes = [];

    public function __construct()
    {
        $this->quoteType = request()->segment(1);
        $this->quoteTypes = [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ];
    }

    public function collection()
    {
        switch (ucfirst($this->quoteType)) {
            case QuoteTypes::BIKE->value:
                return BikeQuoteRepository::getData(true);

            case QuoteTypes::YACHT->value:
                return YachtQuoteRepository::getData(true);

            case QuoteTypes::PET->value:
                return PetQuoteRepository::getData(true);

            case QuoteTypes::CYCLE->value:
                return CycleQuoteRepository::getData(true);

            case QuoteTypes::JETSKI->value:
                return JetskiQuoteRepository::getData(true);

            default:
                return abort(404);
        }
    }

    public function headings(): array
    {
        if (in_array(ucfirst($this->quoteType), $this->quoteTypes)) {
            return $this->getHeadings($this->quoteType);
        } else {
            return abort(404);
        }
    }

    protected function getHeadings($quoteType)
    {   
        
        switch (ucfirst($quoteType)) {
            case QuoteTypes::BIKE->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'DOB',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'CURRENTLY INSURED WITH',
                    'IS ECOMMERCE',
                ];
            case QuoteTypes::YACHT->value:
            case QuoteTypes::JETSKI->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'CURRENTLY INSURED WITH',
                    'IS ECOMMERCE',
                ];

            case QuoteTypes::PET->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'TRANSAPP CODE',
                    'SOURCE',
                    'LOST REASON',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'TYPE OF PET',
                    'BREED OF PET',
                    'AGE OF PET',
                    'IS NEUTERED',
                    'IS MICROCHIPPED',
                    'MICROCHIP NO',
                    'IS MIXED BREED',
                    'HAS INJURY',
                    'ACCOMMODATION TYPE',
                    'POSSESION TYPE',
                    'IS ECOMMERCE',
                ];

            case QuoteTypes::CYCLE->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'IS ECOMMERCE',
                ];
        }
    }

    public function map($quote): array
    {
        if (in_array(ucfirst($this->quoteType), $this->quoteTypes)) {
            return $this->getValues($this->quoteType, $quote);
        } else {
            return abort(404);
        }
    }

    protected function getValues($quoteType, $quote)
    {
        switch (ucfirst($quoteType)) {
            case QuoteTypes::BIKE->value:
            case QuoteTypes::YACHT->value:
            case QuoteTypes::JETSKI->value:
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    date(config('constants.DATE_FORMAT'), strtotime($quote->quote->dob)),
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date(config('constants.datetime_format'), strtotime($quote->created_at)),
                    date(config('constants.datetime_format'), strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->policy_number,
                    $quote->source,
                    optional($quote->currentlyInsuredWith)->text,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];

            case QuoteTypes::PET->value:
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date(config('constants.datetime_format'), strtotime($quote->created_at)),
                    date(config('constants.datetime_format'), strtotime($quote->updated_at)),
                    optional($quote->quoteDetail)->transapp_code,
                    $quote->source,
                    optional($quote->petQuoteRequestDetail)->lostReason?->text,
                    $quote->premium,
                    $quote->policy_number,
                    optional($quote->petQuote)->petType?->text,
                    optional($quote->petQuote)->breed_of_pet1,
                    optional($quote->petQuote)->petAge?->text,
                    optional($quote->petQuote)->is_neutered ? 'Yes' : 'No',
                    optional($quote->petQuote)->is_microchipped ? 'Yes' : 'No',
                    optional($quote->petQuote)->microchip_no,
                    optional($quote->petQuote)->is_mixed_breed ? 'Yes' : 'No',
                    optional($quote->petQuote)->has_injury ? 'Yes' : 'No',
                    optional($quote->petQuote)->accomodationType?->text,
                    optional($quote->petQuote)->possessionType?->text,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];

            case QuoteTypes::CYCLE->value:
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date(config('constants.datetime_format'), strtotime($quote->created_at)),
                    date(config('constants.datetime_format'), strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->policy_number,
                    $quote->source,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];
        }
    }
}
