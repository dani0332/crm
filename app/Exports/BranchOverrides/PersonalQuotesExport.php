<?php

namespace App\Exports\BranchOverrides;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
use App\Models\User;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\BranchAssignmentService;
use App\Services\Life\LifeQuoteService;
use App\Services\Quotes\CyberQuoteService;
use App\Services\Quotes\DeviceQuoteService;
use App\Services\Quotes\SavingsQuoteService;
use App\Traits\ExcelExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class PersonalQuotesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ExcelExportable;

    private const PRIVATE_CLIENT = 'PRIVATE CLIENT';
    private const ADVISOR_ASSIGNED_DATE = 'ADVISOR ASSIGNED DATE';
    private const LAST_MODIFIED_DATE = 'LAST MODIFIED DATE';
    private const RENEWAL_BATCH = 'RENEWAL BATCH';
    private const TRANSACTION_APPROVED_DATE = 'TRANSACTION APPROVED DATE';
    private const FIRST_NAME = 'FIRST NAME';
    private const LAST_NAME = 'LAST NAME';
    private const LEAD_STATUS = 'LEAD STATUS';
    private const ADVISOR = 'ADVISOR';
    private const BRANCH = 'BRANCH';
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
    private const SUB_SOURCE = 'IMCRM SUB-SOURCE';
    private const OVERRIDE_FLAG = 'OVERRIDE FLAG';
    private const OVERRIDE_REASON = 'OVERRIDE REASON';
    private const ORIGINAL_BRANCH = 'ORIGINAL BRANCH';
    private const TARGET_BRANCH = 'TARGET BRANCH';
    private const OVERRIDE_APPLIED_DATE = 'OVERRIDE APPLIED DATE';
    private const TOTAL_COMMISSION = 'TOTAL COMMISSION';
    private const COMMISSION_PERCENT = 'COMMISSION %';
    private const PLAN_NAME = 'PLAN NAME';
    private const COVERAGE_UP_TO = 'COVERAGE UP TO';

    private string $quoteType = '';
    private array $quoteTypes = [];

    public function __construct(string $quoteType)
    {
        $this->quoteType = $quoteType;
        $this->quoteTypes = [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::SAVINGS->value,
            QuoteTypes::HOME->value,
            QuoteTypes::CYBER->value,
            QuoteTypes::DEVICE->value,
        ];
    }

    private function getQuoteQuery($requestParams = [])
    {
        $user = User::where('name', UserNameEnum::System)->first();
        $requestParams = [
            'user' => $user,
            'booking_date' => [
                now()->subDays(7)->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
                now()->subDays(1)->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH')),
            ],
        ];
        if (in_array(ucfirst($this->quoteType), [QuoteTypes::SAVINGS->value, QuoteTypes::LIFE->value, QuoteTypes::CYBER->value, QuoteTypes::DEVICE->value])) {
            foreach ($requestParams as $key => $value) {
                request()->merge([$key => $value]);
            }

            if (in_array(ucfirst($this->quoteType), [QuoteTypes::LIFE->value, QuoteTypes::CYBER->value, QuoteTypes::DEVICE->value]) && ! Auth::check()) {
                Auth::login($user);
            }
        }

        $query = match (ucfirst($this->quoteType)) {
            QuoteTypes::BIKE->value => BikeQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::YACHT->value => YachtQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::PET->value => PetQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::CYCLE->value => CycleQuoteRepository::getData(true, requestParams: $requestParams),
            QuoteTypes::HOME->value => HomeQuoteRepository::getData(true, false, $requestParams),
            QuoteTypes::LIFE->value => app(LifeQuoteService::class)->getLifeQuoteQuery(isExportRequest: true),
            QuoteTypes::SAVINGS->value => app(SavingsQuoteService::class)->getData(getQuery: true),
            QuoteTypes::CYBER->value => app(CyberQuoteService::class)->getData(getQuery: true),
            QuoteTypes::DEVICE->value => app(DeviceQuoteService::class)->getData(false, false, false),
            default => abort(404),
        };

        $query = $query->with(
            'branchOverride',
            'branchOverride.branchOverrideConfig',
            'branchOverride.branchOverrideConfig.sourceBranch',
            'branchOverride.branchOverrideConfig.targetBranch',
            'branchOverride.branchOverrideConfig.quoteType',
            'payments'
        )
            ->whereHas('branchOverride');

        return $query;
    }

    public function collection($requestParams = [])
    {
        return $this->getQuoteQuery($requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery($requestParams = []): ?Builder
    {
        return $this->getQuoteQuery($requestParams);
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
                self::BRANCH,
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
                self::BRANCH,
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
                self::BRANCH,
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
                self::SUB_SOURCE,
            ],
            QuoteTypes::CYCLE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::BRANCH,
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
                self::BRANCH,
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
                self::TRANSACTION_APPROVED_DATE,
                self::BOOKING_DATE,
                self::PRIVATE_CLIENT,
                self::SUB_SOURCE,
            ],
            QuoteTypes::SAVINGS->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::BRANCH,
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
                self::SUB_SOURCE,
            ],
            QuoteTypes::LIFE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::ADVISOR,
                self::BRANCH,
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
            QuoteTypes::CYBER->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::SOURCE,
                self::PLAN_NAME,
                self::COVERAGE_UP_TO,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::ADVISOR,
                self::BRANCH,
                self::CREATED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
            ],
            QuoteTypes::DEVICE->value => [
                self::REF_ID,
                self::FIRST_NAME,
                self::LAST_NAME,
                self::LEAD_STATUS,
                self::SOURCE,
                self::PLAN_NAME,
                self::PREMIUM,
                self::POLICY_NUMBER,
                self::ADVISOR,
                self::BRANCH,
                self::CREATED_DATE,
                self::LAST_MODIFIED_DATE,
                self::PREVIOUS_POLICY_EXPIRY_DATE,
            ],
        ];

        $baseHeadings = $headings[ucfirst($quoteType)] ?? [];
        $mergedHeadings = array_merge($baseHeadings, [
            self::OVERRIDE_FLAG,
            self::OVERRIDE_REASON,
            self::ORIGINAL_BRANCH,
            self::TARGET_BRANCH,
            self::OVERRIDE_APPLIED_DATE,
            self::TOTAL_COMMISSION,
            self::COMMISSION_PERCENT,
        ]);

        return $mergedHeadings;
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
        $branchName = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypes::getIdFromValue($quoteType)));

        $baseFields = [
            'code' => $quote->code,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'lead_status' => optional($quote->quoteStatus)->text,
            'advisor' => optional($quote->advisor)->name,
            'branch' => $branchName,
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

        $branchFields = [
            'override_flag' => 'TRUE',
            'override_reason' => $quote->branchOverride?->branchOverrideConfig?->override_text ?? '',
            'original_branch' => $quote->branchOverride?->branchOverrideConfig?->sourceBranch?->name ?? '',
            'target_branch' => $quote->branchOverride?->branchOverrideConfig?->targetBranch?->name ?? '',
            'override_applied_date' => date(config('constants.datetime_format'), strtotime($quote->branchOverride?->created_at)),
            'total_commission' => $quote->payments?->first()?->commission ?? '0',
            'commission_percent' => ($quote->payments?->first()?->commmission_percentage ?? '0').'%',
        ];

        return match (ucfirst($quoteType)) {
            QuoteTypes::BIKE->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                date(config('constants.datetime_format'), strtotime($quote->dob)),
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                ...$branchFields,
            ],
            QuoteTypes::YACHT->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                ...$branchFields,
            ],
            QuoteTypes::PET->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                optional($quote->subSource)->text,
                ...$branchFields,
            ],
            QuoteTypes::CYCLE->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                ...$branchFields,
            ],
            QuoteTypes::HOME->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                $baseFields['transaction_approved_date'],
                $baseFields['booking_date'],
                $baseFields['pc_customer'],
                optional($quote->subSource)->text,
                ...$branchFields,
            ],
            QuoteTypes::SAVINGS->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['advisor'],
                $baseFields['branch'],
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
                optional($quote->subSource)->text,
                ...$branchFields,
            ],
            QuoteTypes::LIFE->value => [
                $quote->code,
                $quote->first_name,
                $quote->last_name,
                optional($quote->quoteStatus)->text ?? '',
                optional($quote->advisor)->name,
                $baseFields['branch'],
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
                ...$branchFields,
            ],
            QuoteTypes::CYBER->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['source'],
                $quote?->insuranceProviderPlan?->text ?? '',
                $quote?->cyberQuote?->coverage ? '$ '.$quote->cyberQuote->coverage->text : '',
                $baseFields['premium'],
                $baseFields['policy_number'],
                $baseFields['advisor'],
                $baseFields['branch'],
                $baseFields['created_date'],
                $baseFields['last_modified_date'],
                $baseFields['previous_policy_expiry_date'],
                ...$branchFields,
            ],
            QuoteTypes::DEVICE->value => [
                $baseFields['code'],
                $baseFields['first_name'],
                $baseFields['last_name'],
                $baseFields['lead_status'],
                $baseFields['source'],
                $quote?->insuranceProviderPlan?->text ?? '',
                $baseFields['premium'],
                $baseFields['policy_number'],
                $baseFields['advisor'],
                $baseFields['branch'],
                $baseFields['created_date'],
                $baseFields['last_modified_date'],
                $baseFields['previous_policy_expiry_date'],
                ...$branchFields,
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
            QuoteTypes::HOME->value => QuoteTypeId::Home,
            QuoteTypes::CYBER->value => QuoteTypeId::Cyber,
            QuoteTypes::DEVICE->value => QuoteTypeId::Device,
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
