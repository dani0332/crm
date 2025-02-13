<?php

namespace App\Services\Quotes;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
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
use App\Repositories\UserRepository;
use App\Services\CentralService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseQuoteService
{
    use GenericQueriesAllLobs;

    public function __construct(public QuoteTypes $quoteType) {}

    protected function baseQuery(): Builder
    {
        return $this->quoteType->model()
            ->when($this->quoteType->isPersonalQuote(), fn ($query) => $query->where('quote_type_id', $this->quoteType->id()))
            ->when($this->isAdvisor(), fn ($query) => $query->where('advisor_id', Auth::id()))
            ->filterByAdvisors(request('advisors'))
            ->orderBy((request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');
    }

    protected function isAdvisor()
    {
        return Auth::user()->hasAnyRole($this->quoteType->advisorRoles());
    }

    public function getAdvisors()
    {
        return UserRepository::getPersonalQuoteAdvisors($this->quoteType->value);
    }

    public function getQuoteStatuses($ignoreList = [])
    {
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId($this->quoteType->id())->get();

        return collect($quoteStatuses)->filter(fn ($value) => ! in_array($value['id'], $ignoreList))->values();
    }

    public function getRenewalBatches()
    {
        return RenewalBatch::getAllBatches($this->quoteType !== QuoteTypes::CAR);
    }

    public function getPaymentAuthorizedDays()
    {
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
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
        $activities = ActivityRepository::where([
            'quote_type_id' => $quoteType->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes($quoteType->id());

        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($quoteType->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, $quoteType->value, $quote->payments, $quoteDocuments);

        $membersDetails = CustomerMembersRepository::getBy($quote->id, $quoteType->name);
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
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
            'quoteDocuments' => $quoteNotes,
            'storageUrl' => storageUrl(),
            'isBetaUser' => Auth::user()->hasRole(RolesEnum::BetaUser),
        ];
    }
}
