<?php

namespace App\Exports;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\TravelQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PersonalQuotesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    private $quoteType = "";
    private $quoteTypes = [];

    public function __construct()
    {
        $this->quoteType = request()->segment(1);
        $this->quoteTypes = array_merge(array_column(QuoteTypes::cases(), 'value'), [ucfirst(strtolower(quoteTypeCode::Amt))]);
    }

    public function collection()
    {
        switch ($this->quoteType) {
            case strtolower(QuoteTypes::HOME->value):
                return HomeQuoteRepository::getData(true);
                break;

            case strtolower(quoteTypeCode::Amt):
                return BusinessQuoteRepository::getData(quoteTypeCode::GroupMedical, true);
                break;

            case strtolower(QuoteTypes::BUSINESS->value):
                return BusinessQuoteRepository::getData(quoteTypeCode::CORPLINE, true);
                break;

            case strtolower(QuoteTypes::BIKE->value):
                return BikeQuoteRepository::getData(true);
                break;

            case strtolower(QuoteTypes::YACHT->value):
                return YachtQuoteRepository::getData(true);
                break;

            case strtolower(QuoteTypes::TRAVEL->value):
                return TravelQuoteRepository::getData(true);
                break;

            case strtolower(QuoteTypes::PET->value):
                return PetQuoteRepository::getData(true);
                break;

            case strtolower(QuoteTypes::CYCLE->value):
                return CycleQuoteRepository::getData(true);
                break;

            case strtolower(QuoteTypes::JETSKI->value):
                return JetskiQuoteRepository::getData(true);
                break;

            default:
                return abort(404);
                break;
        }
    }

    public function headings(): array
    {
        if(in_array(ucfirst($this->quoteType), $this->quoteTypes)):
            return $this->getHeadings($this->quoteType);

        else:
            return abort(404);
        endif;
    }

    protected function getHeadings($quoteType)
    {

        switch ($quoteType) {
            case strtolower(QuoteTypes::HOME->value):
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
                ];
                break;

            case strtolower(quoteTypeCode::Amt):
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'PREMIUM',
                    'COMPANY NAME',
                    'POLICY NUMBER',
                    'LOST REASON',
                    'SOURCE',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                ];
                break;

            case strtolower(QuoteTypes::BUSINESS->value):
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'COMPANY NAME',
                    'TRANSAPP CODE',
                    'SOURCE',
                    'POLICY NUMBER',
                    'LOST REASON',
                    'ADVISOR',
                    'LEAD STATUS',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'NUMBER OF EMPLOYEES',
                    'BUSINESS INSURANCE TYPE',
                    'GENDER'
                ];
                break;

            case strtolower(QuoteTypes::BIKE->value):
            case strtolower(QuoteTypes::YACHT->value):
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
                    'IS ECOMMERCE'
                ];
                break;

            case strtolower(QuoteTypes::TRAVEL->value):
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
                    'DOB',
                    'TRANSAPP CODE',
                    'LOST REASON',
                    'SOURCE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'DESTINATION',
                    'CURRENTLY LOCATED IN',
                    'EXPIRY DATE',
                    'IS ECOMMERCE',
                    'PAYMENT STATUS'
                ];
                break;

            case strtolower(QuoteTypes::PET->value):
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    'LAST MODIFIED DATE',
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
                    'TRANSAPP CODE',
                    'IS ECOMMERCE'
                ];
                break;

            case strtolower(QuoteTypes::CYCLE->value):
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
                    'IS ECOMMERCE'
                ];
                break;

            case strtolower(QuoteTypes::JETSKI->value):
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
                break;
        }
    }

    public function map($quote): array
    {
        if(in_array(ucfirst($this->quoteType), $this->quoteTypes)):
            return $this->getValues($this->quoteType, $quote);
        else:
            return abort(404);
        endif;

    }

    protected function getValues($quoteType, $quote)
    {
        switch ($quoteType) {
            case strtolower(QuoteTypes::HOME->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    optional($quote->homeQuoteRequestDetail)->transapp_code,
                    $quote->source,
                    optional($quote->homeQuoteRequestDetail)->lostReason?->text,
                    $quote->premium,
                    $quote->policy_number,
                ];
                break;

            case strtolower(quoteTypeCode::Amt):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    $quote->premium,
                    $quote->company_name,
                    $quote->policy_number,
                    optional($quote->businessQuoteRequestDetail)->lostReason?->text,
                    $quote->source,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                ];
                break;

            case strtolower(QuoteTypes::BUSINESS->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    $quote->company_name,
                    optional($quote->businessQuoteRequestDetail)->transapp_code,
                    $quote->source,
                    $quote->policy_number,
                    optional($quote->businessQuoteRequestDetail)->lostReason?->text,
                    optional($quote->advisor)->name,
                    optional($quote->quoteStatus)->text,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->number_of_employees,
                    optional($quote->typeOfInsurance)->text,
                    $quote->gender,
                ];
                break;

            case strtolower(QuoteTypes::BIKE->value):
            case strtolower(QuoteTypes::YACHT->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    $quote->dob_formatted,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->policy_number,
                    $quote->source,
                    optional($quote->currentlyInsuredWith)->text,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];
                break;

            case strtolower(QuoteTypes::TRAVEL->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    $quote->dob,
                    optional($quote->travelQuoteRequestDetail)->transapp_code,
                    optional($quote->travelQuoteRequestDetail)->lostReason?->text,
                    $quote->source,
                    $quote->premium,
                    $quote->policy_number,
                    optional($quote->destination)->text,
                    optional($quote->currentlyLocatedIn)->text,
                    $quote->expiry_date,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                    optional($quote->paymentStatus)->text
                ];
                break;

            case strtolower(QuoteTypes::PET->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
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
                    optional($quote->petQuoteRequestDetail)->transapp_code,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];
                break;

            case strtolower(QuoteTypes::CYCLE->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->policy_number,
                    $quote->source,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];
                break;

            case strtolower(QuoteTypes::JETSKI->value):
                return [
                    $quote->code,
                    $quote->first_name,
                    $quote->last_name,
                    $quote->dob_formatted,
                    optional($quote->quoteStatus)->text,
                    optional($quote->advisor)->name,
                    date('d-m-Y H:i:s', strtotime($quote->created_at)),
                    date('d-m-Y H:i:s', strtotime($quote->updated_at)),
                    $quote->premium,
                    $quote->policy_number,
                    $quote->source,
                    optional($quote->currentlyInsuredWith)->text,
                    $quote->is_ecommerce ? 'Yes' : 'No',
                ];
                break;
        }
    }
}
