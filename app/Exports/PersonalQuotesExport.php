<?php

namespace App\Exports;

use App\Enums\QuoteTypes;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use App\Traits\ExcelExportable;

class PersonalQuotesExport
{
    use ExcelExportable;

    private const PC_CUSTOMER = 'PC CUSTOMER';
    private const ADVISOR_ASSIGNED_DATE = 'ADVISOR ASSIGNED DATE';

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
            QuoteTypes::HOME->value,
        ];
    }

    public function collection($requestParams)
    {
        return match (ucfirst($this->quoteType)) {
            QuoteTypes::BIKE->value => BikeQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::YACHT->value => YachtQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::PET->value => PetQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::CYCLE->value => CycleQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::JETSKI->value => JetskiQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::HOME->value => HomeQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            default => abort(404),
        };
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery($requestParams = [])
    {
        switch (ucfirst($this->quoteType)) {
            case QuoteTypes::BIKE->value:
                return BikeQuoteRepository::getData(true, requestParams: $requestParams);

            case QuoteTypes::YACHT->value:
                return YachtQuoteRepository::getData(true, requestParams: $requestParams);

            case QuoteTypes::PET->value:
                return PetQuoteRepository::getData(true, requestParams: $requestParams);

            case QuoteTypes::CYCLE->value:
                return CycleQuoteRepository::getData(true, requestParams: $requestParams);

            case QuoteTypes::JETSKI->value:
                return JetskiQuoteRepository::getData(true, requestParams: $requestParams);

            case QuoteTypes::HOME->value:
                return HomeQuoteRepository::getData(true, requestParams: $requestParams);

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
                    self::ADVISOR_ASSIGNED_DATE,
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'CURRENTLY INSURED WITH',
                    'IS ECOMMERCE',
                    'PREVIOUS POLICY EXPIRY DATE',
                    'PREVIOUS POLICY PREMIUM',
                    'PREVIOUS POLICY NUMBER',
                    'TRANSACTION APPROVED DATE',
                    'BOOKING DATE',
                    self::PC_CUSTOMER,
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
                    self::ADVISOR_ASSIGNED_DATE,
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'CURRENTLY INSURED WITH',
                    'IS ECOMMERCE',
                    'RENEWAL BATCH',
                    'PREVIOUS POLICY EXPIRY DATE',
                    'PREVIOUS POLICY PREMIUM',
                    'PREVIOUS POLICY NUMBER',
                    'TRANSACTION APPROVED DATE',
                    'BOOKING DATE',
                    self::PC_CUSTOMER,
                ];

            case QuoteTypes::PET->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    self::ADVISOR_ASSIGNED_DATE,
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
                    'RENEWAL BATCH',
                    'PREVIOUS POLICY EXPIRY DATE',
                    'PREVIOUS POLICY PREMIUM',
                    'PREVIOUS POLICY NUMBER',
                    'TRANSACTION APPROVED DATE',
                    'BOOKING DATE',
                    self::PC_CUSTOMER,
                ];

            case QuoteTypes::CYCLE->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    self::ADVISOR_ASSIGNED_DATE,
                    'LAST MODIFIED DATE',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'SOURCE',
                    'IS ECOMMERCE',
                    'RENEWAL BATCH',
                    'PREVIOUS POLICY EXPIRY DATE',
                    'PREVIOUS POLICY PREMIUM',
                    'PREVIOUS POLICY NUMBER',
                    'TRANSACTION APPROVED DATE',
                    'BOOKING DATE',
                    self::PC_CUSTOMER,
                ];

            case QuoteTypes::HOME->value:
                return [
                    'REF-ID',
                    'FIRST NAME',
                    'LAST NAME',
                    'LEAD STATUS',
                    'ADVISOR',
                    'CREATED DATE',
                    self::ADVISOR_ASSIGNED_DATE,
                    'LAST MODIFIED DATE',
                    'TRANSAPP CODE',
                    'SOURCE',
                    'LOST REASON',
                    'PREMIUM',
                    'POLICY NUMBER',
                    'RENEWAL BATCH',
                    'PREVIOUS POLICY EXPIRY DATE',
                    'PREVIOUS POLICY PREMIUM',
                    'PREVIOUS POLICY NUMBER',
                    self::PC_CUSTOMER,
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
        $baseFields = [
            'code' => $quote->code,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'lead_status' => optional($quote->quoteStatus)->text,
            'advisor' => optional($quote->advisor)->name,
            'created_date' => date(config('constants.datetime_format'), strtotime($quote->created_at)),
            'advisor_assigned_date' => isset($quote->quoteDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->quoteDetail->advisor_assigned_date)) : '',
            'last_modified_date' => date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            'premium' => ! empty($quote->premium) ? $quote->premium : $quote->price_with_vat,
            'policy_number' => $quote->policy_number,
            'source' => $quote->source,
            'is_ecommerce' => $quote->is_ecommerce ? 'Yes' : 'No',
            'previous_policy_expiry_date' => $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            'previous_policy_premium' => $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            'previous_policy_number' => $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            'transaction_approved_date' => $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            'booking_date' => $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            'pc_customer' => $quote->customer?->pcp_tag_formatted ?? '',
        ];

        return match (ucfirst($quoteType)) {
            QuoteTypes::BIKE->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                date(config('constants.datetime_format'), strtotime($quote->dob)),
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['created_date'],
                $baseFields['advisor_assigned_date'],
                $baseFields['last_modified_date'],
                $baseFields['premium'],
                $baseFields['policy_number'],
                $baseFields['source'],
                optional($quote->currentlyInsuredWith)->text,
                $baseFields['is_ecommerce'],
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
                $baseFields['pc_customer'],
            ],
            QuoteTypes::YACHT->value, QuoteTypes::JETSKI->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['created_date'],
                $baseFields['advisor_assigned_date'],
                $baseFields['last_modified_date'],
                $baseFields['premium'],
                $baseFields['policy_number'],
                $baseFields['source'],
                optional($quote->currentlyInsuredWith)->text,
                $baseFields['is_ecommerce'],
                $quote->renewal_batch,
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
                $baseFields['pc_customer'],
            ],
            QuoteTypes::PET->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['created_date'],
                $baseFields['advisor_assigned_date'],
                $baseFields['last_modified_date'],
                optional($quote->quoteDetail)->transapp_code,
                $baseFields['source'],
                optional($quote->petQuoteRequestDetail)->lostReason?->text,
                $baseFields['premium'],
                $baseFields['policy_number'],
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
                $baseFields['is_ecommerce'],
                $quote->renewal_batch,
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
                $baseFields['pc_customer'],
            ],
            QuoteTypes::CYCLE->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['created_date'],
                $baseFields['advisor_assigned_date'],
                $baseFields['last_modified_date'],
                $baseFields['premium'],
                $baseFields['policy_number'],
                $baseFields['source'],
                $baseFields['is_ecommerce'],
                $quote->renewal_batch,
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
                $baseFields['pc_customer'],
            ],
            QuoteTypes::HOME->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['created_date'],
                $baseFields['advisor_assigned_date'],
                $baseFields['last_modified_date'],
                $quote?->homeQuote?->homeQuoteRequestDetail?->transapp_code,
                $baseFields['source'],
                $quote?->homeQuote?->homeQuoteRequestDetail?->lostReason?->text,
                $baseFields['premium'],
                $baseFields['policy_number'],
                $quote->renewal_batch,
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['pc_customer'],
            ],
            default => [],
        };
    }
}
