<?php

namespace App\Services\Quotes;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\PaymentStatus;
use App\Models\RenewalBatch;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Services\AMLService;
use App\Services\BaseService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\EmailStatusService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\CentralTrait;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

abstract class BaseQuoteService extends BaseService
{
    use CentralTrait, GenericQueriesAllLobs;

    public function __construct(public QuoteTypes $quoteType) {}

    protected function baseQuery(): Builder
    {
        $model = $this->quoteType->model();
        $tableName = $model->getTable();
        $sortBy = request()->sortBy ?? "{$tableName}.created_at";

        // If sortBy doesn't have a table prefix, add it
        if ($sortBy && ! str_contains($sortBy, '.')) {
            $sortBy = "{$tableName}.{$sortBy}";
        }

        return $model
            ->when($this->quoteType->isPersonalQuote(), fn ($query) => $query->where("{$tableName}.quote_type_id", $this->quoteType->id()))
            ->when($this->isAdvisor(), fn ($query) => $query->where(function ($q) use ($tableName) {
                $q->where("{$tableName}.advisor_id", Auth::id())
                    ->orWhere("{$tableName}.expert_advisor_id", Auth::id());
            }))
            ->filterByAdvisors(request('advisors'))
            ->orderBy($sortBy, request()->sortType ?? 'desc');
    }

    protected function isAdvisor()
    {
        $user = Auth::user();

        return $this->hasAnyRole($user, $this->quoteType->advisorRoles());
    }

    public function getAdvisors()
    {
        return UserRepository::getPersonalQuoteAdvisors($this->quoteType->value);
    }

    public function getQuoteStatuses($ignoreList = [])
    {
        $cacheKey = $this->getQuoteStatusesCacheKey($ignoreList);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($ignoreList) {
            $quoteStatuses = QuoteStatusRepository::byQuoteTypeId($this->quoteType->id())->get();

            return collect($quoteStatuses)->filter(fn ($value) => ! in_array($value['id'], $ignoreList))->values();
        });
    }

    public function getRenewalBatches()
    {
        return RenewalBatch::getAllBatches($this->quoteType !== QuoteTypes::CAR);
    }

    public function getPaymentAuthorizedDays()
    {
        return Cache::remember('payment_authorized_days', now()->addHours(6), function () {
            return ApplicationStorage::where('key_name', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        });
    }

    public function hasOtherFilters()
    {
        return count(array_diff_key(request()->all(), ['page' => ''])) > 0;
    }

    public function getShowCommonData($quote)
    {
        $quoteType = $this->quoteType;

        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails($quoteType->value, $quote);
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled($quoteType->value);
        $quoteStatuses = $this->getQuoteStatuses([QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, $quoteType->id(), $quoteStatuses);

        $user = Auth::user();
        if (! $this->can($user, PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $activities = ActivityRepository::where([
            'quote_type_id' => $quoteType->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes($quoteType->id());

        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($quoteType->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, $quoteType->value, $quote->payments, $quoteDocuments);

        $membersDetails = CustomerMembersRepository::getBy($quote->id, $quoteType->name);
        $nationalities = Nationality::getActiveNationalities();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($quoteType->id());

        $advisors = $this->getAdvisors();
        $personalPlans = PersonalPlanRepository::get();
        $duplicateAllowedLobs = (new CentralService)->duplicateAllowedLobsList($quoteType->value, $quote->code);
        $embeddedProducts = EmbeddedProductRepository::byQuoteType($quoteType->id(), $quote->id);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $uboDetails = CustomerMembersRepository::getBy($quote->id, $quoteType->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $noteDocumentType = DocumentTypeRepository::where('code', DocumentTypeCode::OD)->first();

        $quoteNotes = QuoteNoteRepository::getBy($quote->id, $quoteType->value);

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($quoteType->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $emailStatuses = app(EmailStatusService::class)->getEmailStatus($quoteType->id(), $quote->id);

        $planURL = $this->getEcomQuoteLink($quoteType, $quote->uuid);

        return [
            'quote' => $quote,
            'quoteType' => $quoteType,
            'modelType' => $quoteType,
            'quoteTypeId' => $quoteType->id(),
            'duplicateAllowedLobs' => $duplicateAllowedLobs,
            'embeddedProducts' => $embeddedProducts,
            'advisors' => $advisors,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'activities' => $activities,
            'quoteStatuses' => $quoteStatuses,
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'documentTypes' => $documentTypes,
            'paymentDocument' => $paymentDocument,
            'paymentMethods' => $paymentMethods,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote?->payments,
            'membersDetails' => $membersDetails,
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'lostReasons' => $lostReasons,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'noteDocumentType' => $noteDocumentType,
            'quoteDocuments' => $quoteDocuments,
            'quoteNotes' => $quoteNotes,
            'storageUrl' => storageUrl(),
            'isBetaUser' => $this->hasRole(Auth::user(), RolesEnum::BetaUser),
            'cdnPath' => config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/',
            'vatPercentage' => getAppStorageValueByKey(ApplicationStorageEnums::VAT_VALUE, 0),
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'emailStatuses' => $emailStatuses,
            'planURL' => $planURL,
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
        ];
    }

    protected function hasRole($user, $role)
    {
        return $user && method_exists($user, 'hasRole') && $user->hasRole($role);
    }

    protected function hasAnyRole($user, $roles)
    {
        return $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);
    }

    protected function can($user, $permission)
    {
        return $user && method_exists($user, 'can') && $user->can($permission);
    }

    public function getInsurerAMLStatuses(): array
    {
        return Cache::remember('insurer_aml_statuses', now()->addHours(6), function () {
            return AMLService::getInsurerAMLStatuses();
        });
    }

    public function getPaymentStatuses()
    {
        return Cache::remember('payment_statuses_active', now()->addHours(6), function () {
            return PaymentStatus::where('is_active', 1)
                ->orderBy('text')
                ->get(['id', 'text']);
        });
    }

    private function getQuoteStatusesCacheKey(array $ignoreList): string
    {
        $sortedIgnoreList = $ignoreList;
        sort($sortedIgnoreList);

        $ignoreListKey = empty($sortedIgnoreList)
            ? 'none'
            : implode('_', array_map('strval', $sortedIgnoreList));

        return "quote_statuses_{$this->quoteType->value}_{$ignoreListKey}";
    }

}
