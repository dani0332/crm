<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\InvestmentFrequencyEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Events\LeadsCount;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsQuoteRequest;
use App\Models\Nationality;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Services\CentralService;
use App\Services\QuoteDocumentService;
use App\Services\Quotes\SavingsQuoteService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;

class SavingsQuoteController extends Controller
{
    public function __construct(
        public SavingsQuoteService $savingsQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_LIST, ['only' => ['index']]);
    }

    public function index()
    {
        $advisors = $this->savingsQuoteService->getAdvisors();
        $quoteStatuses = $this->savingsQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $renewalBatches = $this->savingsQuoteService->getRenewalBatches();
        $authorizedDays = $this->savingsQuoteService->getPaymentAuthorizedDays();

        $query = $this->savingsQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->savingsQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->savingsQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        return inertia('SavingsQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'investmentFrequencies' => InvestmentFrequencyEnum::withLabels(),
        ]);
    }

    public function create()
    {
        $data = $this->savingsQuoteService->getFormOptions();

        return inertia('SavingsQuote/Form', $data);
    }

    public function store(SavingsQuoteRequest $request)
    {
        $response = $this->savingsQuoteService->create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        LeadsCount::dispatch($this->savingsQuoteService->getData(forExport: true, getTotalCount: true));

        return redirect(route('savings-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $data = $this->savingsQuoteService->getFormOptions();
        $quote = $this->savingsQuoteService->getOne($uuid);

        return inertia('SavingsQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    public function update(SavingsQuoteRequest $request, $uuid)
    {
        $this->savingsQuoteService->update($uuid, $request->validated());

        return redirect(route('savings-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data */
        $quote = $this->savingsQuoteService->getOne($uuid, true);
        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled($this->savingsQuoteService->quoteType->value);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails($this->savingsQuoteService->quoteType->value, $quote);

        $quoteStatuses = $this->savingsQuoteService->getQuoteStatuses([QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);

        $activities = ActivityRepository::where([
            'quote_type_id' => $this->savingsQuoteService->quoteType->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes($this->savingsQuoteService->quoteType->id());

        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($this->savingsQuoteService->quoteType->value, $quote->id);
        $bookPolicyDetails = $this->savingsQuoteService->bookPolicyPayload($quote, $this->savingsQuoteService->quoteType->value, $quote->payments, $quoteDocuments);

        $membersDetails = CustomerMembersRepository::getBy($quote->id, $this->savingsQuoteService->quoteType->name);
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $advisors = $this->savingsQuoteService->getAdvisors();

        return inertia('SavingsQuote/Show', [
            'quoteType' => $this->savingsQuoteService->quoteType,
            'advisors' => $advisors,
            'quote' => $quote,
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
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
        ]);

        // (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::PET->value);
        // /* End - Temporarily adding for correcting historic data */

        // $quote = PetQuoteRepository::getBy('uuid', $uuid);
        // if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
        //     $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
        //         return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        //     })->values();
        // }
        // $noteDocumentType = DocumentTypeRepository::where('code', DocumentTypeCode::OD)->first();
        // $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name);
        // $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        // $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::PET->id());
        // $personalPlans = PersonalPlanRepository::get();
        // $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        // $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        // $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name, CustomerTypeEnum::Entity);
        // $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        // $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        // $sendUpdateOptions = [];
        // $sendUpdateLogs = [];
        // $sendUpdateEnum = (object) [];
        // $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        // if ($hasPolicyIssuedStatus) {
        //     $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::PET->id());
        //     $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
        //     $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        // }

        // $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        // $duplicateAllowedLobs = (new CentralService)->duplicateAllowedLobsList(QuoteTypes::PET->value, $quote->code);
        // $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::PET->id(), $quote->id);

        // $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::PET->id(), $quoteStatuses);

        // $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        // $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        // $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Pet);
        // $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        // return inertia('PetQuote/Show', [
        //     'amlStatusName' => $amlStatusName,
        //     'paymentMethods' => $paymentMethods,
        //     'insuranceProviders' => $insuranceProviders,
        //     'personalPlans' => $personalPlans,
        //     'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
        //     'storageUrl' => storageUrl(),
        //     'duplicateAllowedLobs' => $duplicateAllowedLobs,
        //     'modelType' => QuoteTypes::PET,
        //     'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::PetManager),
        //     'embeddedProducts' => $embeddedProducts,
        //     'membersDetails' => $membersDetail,
        //     'memberRelations' => $memberRelations,
        //     'nationalities' => $nationalities,
        //     'quoteTypeId' => QuoteTypeId::Pet,
        //     'industryType' => $industryType,
        //     'emirates' => $emirates,
        //     'UBOsDetails' => $uboDetails,
        //     'UBORelations' => $uboRelations,
        //     'noteDocumentType' => $noteDocumentType,
        //     'quoteDocuments' => $quoteNotes,
        //     'cdnPath' => $cdnPath,
        //     'vatPercentage' => $vatPercentage,
        //     'bookPolicyDetails' => $bookPolicyDetails,
        //     'sendUpdateOptions' => $sendUpdateOptions,
        //     'sendUpdateLogs' => $sendUpdateLogs,
        //     'sendUpdateEnum' => $sendUpdateEnum,
        //     'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
        // ]);
    }
}
