<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\Life\LifeQuoteService;
use App\Services\Quotes\SavingsQuoteService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PersonalQuotesExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    private const PRIVATE_CLIENT = 'PRIVATE CLIENT';
    private const ADVISOR_ASSIGNED_DATE = 'ADVISOR ASSIGNED DATE';
    private const LAST_MODIFIED_DATE = 'LAST MODIFIED DATE';
    private const RENEWAL_BATCH = 'RENEWAL BATCH';
    private const TRANSACTION_APPROVED_DATE = 'TRANSACTION APPROVED DATE';
    private const FIRST_NAME = 'FIRST NAME';
    private const LAST_NAME = 'LAST NAME';
    private const LEAD_STATUS = 'LEAD STATUS';
    private const ADVISOR = 'ADVISOR';
    private const CREATED_DATE = 'CREATED DATE';
    private const PREMIUM = 'PREMIUM';
    private const POLICY_NUMBER = 'POLICY NUMBER';
    private const SOURCE = 'SOURCE';
    private const IS_ECOMMERCE = 'IS ECOMMERCE';
    private const PREVIOUS_POLICY_EXPIRY_DATE = 'PREVIOUS POLICY EXPIRY DATE';
    private const PREVIOUS_POLICY_PREMIUM = 'PREVIOUS POLICY PREMIUM';
    private const PREVIOUS_POLICY_NUMBER = 'PREVIOUS POLICY NUMBER';
    private const BOOKING_DATE = 'BOOKING DATE';
    private const DOB = 'DOB';
    private const CURRENTLY_INSURED_WITH = 'CURRENTLY INSURED WITH';
    private const TRANSAPP_CODE = 'TRANSAPP CODE';
    private const LOST_REASON = 'LOST REASON';
    private const TYPE_OF_PET = 'TYPE OF PET';
    private const BREED_OF_PET = 'BREED OF PET';
    private const AGE_OF_PET = 'AGE OF PET';
    private const IS_NEUTERED = 'IS NEUTERED';
    private const IS_MICROCHIPPED = 'IS MICROCHIPPED';
    private const MICROCHIP_NO = 'MICROCHIP NO';
    private const IS_MIXED_BREED = 'IS MIXED BREED';
    private const HAS_INJURY = 'HAS INJURY';
    private const ACCOMMODATION_TYPE = 'ACCOMMODATION TYPE';
    private const POSSESION_TYPE = 'POSSESION TYPE';
    private const REF_ID = 'REF-ID';
    private const CURRENCY = 'CURRENCY';
    private const SUM_ASSURED = 'SUM ASSURED';
    private const SUM_ASSURED_CURRENCY = 'SUM ASSURED CURRENCY';
    private const POLICY_SUM_ASSURED = 'POLICY SUM ASSURED';
    private const SUB_SOURCE = 'SUB SOURCE';

    private string $quoteType = '';
    private array $quoteTypes = [];

    public function __construct(
        private BikeQuoteRepository $bikeQuoteRepository,
        private YachtQuoteRepository $yachtQuoteRepository,
        private PetQuoteRepository $petQuoteRepository,
        private CycleQuoteRepository $cycleQuoteRepository,
        private JetskiQuoteRepository $jetskiQuoteRepository,
        private HomeQuoteRepository $homeQuoteRepository,
        ?string $quoteType = null
    ) {
        // Use provided quote type first, then try request input (for job context),
        // then fall back to URL segment (for direct calls)
        $this->quoteType = $quoteType ?? request()->input('quoteType') ?? request()->segment(1) ?? '';
        $this->quoteTypes = [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::SAVINGS->value,
            QuoteTypes::HOME->value,
        ];
    }

    public function collection(array $requestParams = []): Collection
    {
        return match (ucfirst($this->quoteType)) {
            QuoteTypes::BIKE->value => BikeQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::YACHT->value => YachtQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::PET->value => PetQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::CYCLE->value => CycleQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::JETSKI->value => JetskiQuoteRepository::getData(true, requestParams: $requestParams)->get(),
            QuoteTypes::HOME->value => HomeQuoteRepository::getData(true, false, $requestParams)->get(),
            QuoteTypes::LIFE->value => app(LifeQuoteService::class)->getLifeQuotes(isExportRequest: true),
            default => abort(404),
        };
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return match (ucfirst($this->quoteType)) {
            QuoteTypes::BIKE->value => BikeQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::YACHT->value => YachtQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::PET->value => PetQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::CYCLE->value => CycleQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::JETSKI->value => JetskiQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::HOME->value => HomeQuoteRepository::getData(true, false, $requestParams),
            QuoteTypes::LIFE->value => app(LifeQuoteService::class)->getLifeQuotes(isExportRequest: true),
            QuoteTypes::SAVINGS->value => app(SavingsQuoteService::class)->getData(forExport: true),
            default => abort(404),
        };
    }

    public function headings(): array
    {
        if (in_array(ucfirst($this->quoteType), $this->quoteTypes)) {
            return $this->getHeadings($this->quoteType);
        } else {
            abort(404);
        }
    }

    protected function getHeadings(string $quoteType): array
    {

        $headings = [
            QuoteTypes::BIKE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::DOB,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::SOURCE,
                self::CURRENTLY_INSURED_WITH,
                self::IS_ECOMMERCE,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
                self::SUB_SOURCE,
            ],
            QuoteTypes::YACHT->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::SOURCE,
                self::CURRENTLY_INSURED_WITH,
                self::IS_ECOMMERCE,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
            ],
            QuoteTypes::PET->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::TRANSAPP_CODE,
                self::SOURCE,
                self::LOST_REASON,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::TYPE_OF_PET,
                self::BREED_OF_PET,
                self::AGE_OF_PET,
                self::IS_NEUTERED,
                self::IS_MICROCHIPPED,
                self::MICROCHIP_NO,
                self::IS_MIXED_BREED,
                self::HAS_INJURY,
                self::ACCOMMODATION_TYPE,
                self::POSSESION_TYPE,
                self::IS_ECOMMERCE,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
            ],
            QuoteTypes::CYCLE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::SOURCE,
                self::IS_ECOMMERCE,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
                self::SUB_SOURCE,
            ],
            QuoteTypes::HOME->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::TRANSAPP_CODE,
                self::SOURCE,
                self::LOST_REASON,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::PRIVATE_CLIENT,
                self::SUB_SOURCE,
            ],
            QuoteTypes::SAVINGS->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::ADVISOR_ASSIGNED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::SOURCE,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
            ],
            QuoteTypes::LIFE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::CREATED_DATE,
                self::LAST_MODIFIED_DATE,
                self::TRANSAPP_CODE,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::SOURCE,
                self::LOST_REASON,
                self::IS_ECOMMERCE,
                self::RENEWAL_BATCH,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
                self::PREVIOUS_POLICY_PREMIUM,
                self::PREVIOUS_POLICY_NUMBER,
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
                self::CURRENCY,
                self::SUM_ASSURED,
                self::SUM_ASSURED_CURRENCY,
                self::POLICY_SUM_ASSURED,
                self::SUB_SOURCE,
            ],
        ];

        $quoteType = ucfirst($quoteType);
        if ($quoteType === QuoteTypes::JETSKI->value) {
            $quoteType = QuoteTypes::YACHT->value;
        }

        return $headings[$quoteType] ?? [];
    }

    public function map($quote): array
    {
        if (in_array(ucfirst($this->quoteType), $this->quoteTypes)) {
            return $this->getValues($this->quoteType, $quote);
        } else {
            abort(404);
        }
    }

    protected function getValues(string $quoteType, $quote): array
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
                optional($quote->subSource)->text,
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
                optional($quote->subSource)->text,
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
                optional($quote->subSource)->text,
            ],
            QuoteTypes::SAVINGS->value => [
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
                $quote->renewal_batch,
                $baseFields['previous_policy_expiry_date'],
                $baseFields['previous_policy_premium'],
                $baseFields['previous_policy_number'],
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
            ],
            QuoteTypes::LIFE->value => [
                $quote->code,
                $quote->first_name,
                $quote->last_name,
                optional($quote->quoteStatus)->text ?? '',
                optional($quote->advisor)->name,
                date(config('constants.datetime_format'), strtotime($quote->created_at)),
                date(config('constants.datetime_format'), strtotime($quote->updated_at)),
                $quote->transapp_code,
                $quote->premium,
                $quote->policy_number,
                $quote->source,
                optional($quote->quoteDetail)?->lostReason->text ?? '',
                $quote->is_ecommerce ? 'Yes' : 'No',
                $quote->renewal_batch,
                $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
                $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
                $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
                $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
                $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
                $baseFields['pc_customer'],
                $quote->lifeQuote?->sumInsuredCurrency->text ?? '',
                $quote->lifeQuote?->sum_insured_value ?? '',
                $quote->lifeQuote?->policySumAssuredCurrency->text ?? '',
                $quote->lifeQuote?->policy_sum_assured ?? '',
                optional($quote->subSource)->text,
            ],
            default => [],
        };
    }

    public function getExportMetadata(array $requestParams = []): array
    {
        $quoteTypeIdMap = [
            QuoteTypes::BIKE->value => QuoteTypeId::Bike,
            QuoteTypes::YACHT->value => QuoteTypeId::Yacht,
            QuoteTypes::PET->value => QuoteTypeId::Pet,
            QuoteTypes::CYCLE->value => QuoteTypeId::Cycle,
            QuoteTypes::JETSKI->value => QuoteTypeId::Jetski,
            QuoteTypes::HOME->value => QuoteTypeId::Home,
        ];

        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => $quoteTypeIdMap[ucfirst($this->quoteType)] ?? null,
            'exportType' => 'personal_quotes',
            'dynamicQuoteType' => ucfirst($this->quoteType),
        ];
    }
}
