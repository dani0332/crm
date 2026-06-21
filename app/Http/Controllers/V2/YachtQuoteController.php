<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamNameEnum;
use App\Events\LeadsCount;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\YachtQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class YachtQuoteController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * @return Response|ResponseFactory
     */
    public function index()
    {
        $personalQuotes = YachtQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return $value['id'] != QuoteStatusEnum::Lost;
        })->values();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        // PD Revert
        // $count = $personalQuotes->count();

        $count = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $subSources = app(LookupService::class)->getSubSource();

        return inertia('YachtQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'renewalBatches' => $renewalBatches,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $count : YachtQuoteRepository::getData(true, true),
            'authorizedDays' => intval($authorizedDays->value),
            'insurerAMLStatus' => AMLService::getInsurerAMLStatuses(),
            'subSources' => $subSources,
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function create(Request $request)
    {
        // Log parameters from CreateLeadModal
        LoggerService::info('Yacht create method called with parameters', [
            'type' => $request->input('type'),
            'subSourceId' => $request->input('subSourceId'),
            'subSourceOptionsId' => $request->input('subSourceOptionsId'),
        ]);

        $data = YachtQuoteRepository::getFormOptions();
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [
            'type' => $request->input('type'),
            'subSource' => $request->input('subSourceId'),
            'subSourceOption' => $request->input('subSourceOptionsId'),
        ];

        return inertia('YachtQuote/Form', $data);
    }

    /**
     * @param  $quoteTypeCode
     * @param  BikeQuoteRequest  $request
     * @return RedirectResponse
     */
    public function store(YachtQuoteRequest $request)
    {
        $response = YachtQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        event(new LeadsCount(YachtQuoteRepository::getData(true, true)));

        return redirect('personal-quotes/yacht/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return Response|ResponseFactory
     */
    public function edit($uuid)
    {
        $data = YachtQuoteRepository::getFormOptions();
        $quote = YachtQuoteRepository::getBy('uuid', $uuid);
        $subSources = app(LookupService::class)->getSubSource();

        return inertia('YachtQuote/Form', array_merge($data, [
            'quote' => $quote,
            'subSources' => $subSources,
            'leadSourceParams' => [],
        ]));
    }

    /**
     * @return Response|ResponseFactory
     */
    public function show($uuid)
    {

        /* Start - Temporarily adding for correcting historic data */
        $quote = YachtQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::YACHT->value);
        /* End - Temporarily adding for correcting historic data */

        $quote = YachtQuoteRepository::getBy('uuid', $uuid);
        $quote->load('subSource', 'subSourceOption', 'leadGenerator', 'expertAdvisor');
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::YACHT->value, $quote);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::YACHT->name);
        $quote->load('documents.createdBy:id,name,email');

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Yacht);

        $noteDocumentType = DocumentTypeRepository::where('code', DocumentTypeCode::OD)->first();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::getActiveNationalities();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::YACHT->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::YACHT->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::YACHT->id(), $quoteStatuses);

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::YACHT->id(), $quote->id);
        $lookupService = app(LookupService::class);
        $industryType = $lookupService->getCompanyTypes();
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Yacht);
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = $lookupService->getSendUpdateOptions(QuoteTypes::YACHT->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::YACHT->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::YACHT->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::YACHT->value, $quote->payments, $quoteDocuments);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return inertia('YachtQuote/Show', [
            'quoteType' => QuoteTypes::YACHT,
            'quote' => fn () => $quote,
            'amlStatusName' => $amlStatusName,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::YACHT->id(),
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'modelType' => QuoteTypes::YACHT,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::YachtManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'noteDocumentType' => $noteDocumentType,
            'quoteDocuments' => $quoteNotes,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'sendUpdateEnum' => $sendUpdateEnum,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote?->payments,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
            'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
        ]);
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @param  BikeQuoteRequest  $request
     * @return void
     */
    public function update($uuid, YachtQuoteRequest $request)
    {
        YachtQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/yacht/'.$uuid)->with('message', 'Quote updated successfully');
    }

    public function cardsView(Request $request)
    {
        $userTeams = auth()->user()->getUserTeams(auth()->id())->toArray();

        $newBusinessTeam = in_array(TeamNameEnum::YACHT_TEAM, $userTeams);
        $renewalsTeam = in_array(TeamNameEnum::YACHT_RENEWALS, $userTeams);

        $areBothTeamsPresent = $newBusinessTeam && $renewalsTeam;

        $isManagerOrDeputy = auth()->user()->hasAnyRole([RolesEnum::YachtManager]);

        if (($request->is_renewal === null && $areBothTeamsPresent) || ($request->is_renewal === null && $isManagerOrDeputy)) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        } elseif ($request->is_renewal === null && $newBusinessTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::noText]);
        } elseif ($request->is_renewal === null && $renewalsTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        }

        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::ProposalFormRequested, 'title' => quoteStatusCode::PROPOSAL_FORM_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::ProposalFormRequested, $request)],
            ['id' => QuoteStatusEnum::ProposalFormReceived, 'title' => quoteStatusCode::PROPOSAL_FORM_RECEIVED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::ProposalFormReceived, $request)],
            ['id' => QuoteStatusEnum::AdditionalInformationRequested, 'title' => quoteStatusCode::ADDITIONAL_INFORMATION_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::AdditionalInformationRequested, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::QuoteRequested, 'title' => quoteStatusCode::QUOTE_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::QuoteRequested, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::PendingRenewalInformation, 'title' => quoteStatusCode::PENDING_RENEWAL_INFORMATION, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PendingRenewalInformation, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PaymentLinkSentToCustomer, 'title' => quoteStatusCode::PAYMENT_LINK_SENT_TO_CUSTOMER, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PaymentLinkSentToCustomer, $request)],
            ['id' => QuoteStatusEnum::PaymentInitiated, 'title' => quoteStatusCode::PaymentInitiated, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PaymentInitiated, $request)],
            ['id' => QuoteStatusEnum::FinalizingTerms, 'title' => quoteStatusCode::FINALIZING_TERMS, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::FinalizingTerms, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $newBusiness = [
            QuoteStatusEnum::NewLead => 0,
            QuoteStatusEnum::ProposalFormRequested => 1,
            QuoteStatusEnum::ProposalFormReceived => 2,
            QuoteStatusEnum::AdditionalInformationRequested => 3,
            QuoteStatusEnum::QuoteRequested => 4,
            QuoteStatusEnum::Quoted => 5,
            QuoteStatusEnum::FinalizingTerms => 6,
            QuoteStatusEnum::PolicyIssued => 7,
        ];

        $renewals = [
            QuoteStatusEnum::Allocated => 0,
            QuoteStatusEnum::FollowedUp => 1,
            QuoteStatusEnum::PendingRenewalInformation => 2,
            QuoteStatusEnum::QuoteRequested => 3,
            QuoteStatusEnum::Quoted => 4,
            QuoteStatusEnum::FinalizingTerms => 5,
            QuoteStatusEnum::PolicyIssued => 6,
        ];

        if ($areBothTeamsPresent || $isManagerOrDeputy) {
            if ($request->is_renewal === quoteTypeCode::yesText) {
                $renewalKeys = array_keys($renewals);
                $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                    return in_array($quote['id'], $renewalKeys);
                });

                // Sort filtered quotes based on the renewals array order
                usort($quotes, function ($a, $b) use ($renewals) {
                    return $renewals[$a['id']] <=> $renewals[$b['id']];
                });
            }
            if ($request->is_renewal === quoteTypeCode::noText) {
                $newBusinessKeys = array_keys($newBusiness);
                $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                    return in_array($quote['id'], $newBusinessKeys);
                });

                // Sort filtered quotes based on the newBusiness array order
                usort($quotes, function ($a, $b) use ($newBusiness) {
                    return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
                });
            }

        } elseif ($newBusinessTeam) {
            $newBusinessKeys = array_keys($newBusiness);
            $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                return in_array($quote['id'], $newBusinessKeys);
            });

            // Sort filtered quotes based on the newBusiness array order
            usort($quotes, function ($a, $b) use ($newBusiness) {
                return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
            });
        } elseif ($renewalsTeam) {
            $renewalKeys = array_keys($renewals);
            $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                return in_array($quote['id'], $renewalKeys);
            });

            // Sort filtered quotes based on the renewals array order
            usort($quotes, function ($a, $b) use ($renewals) {
                return $renewals[$a['id']] <=> $renewals[$b['id']];
            });
        } elseif (array_intersect([TeamNameEnum::YACHT_TEAM], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::InNegotiation,
                QuoteStatusEnum::FinalizingTerms,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::YACHT_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::FinalizingTerms,
                QuoteStatusEnum::InNegotiation, ])->values()->toArray();
        }

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::Yacht);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Yacht);
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('YachtQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::YACHT->id(),
            'quoteType' => QuoteTypes::YACHT->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : YachtQuoteRepository::getData(true, true),
            'areBothTeamsPresent' => $areBothTeamsPresent || $isManagerOrDeputy ? true : false,
            'is_renewal' => ($areBothTeamsPresent || $isManagerOrDeputy ? 'Yes' : $renewalsTeam) ? 'Yes' : ($newBusinessTeam ? 'No' : null),
            'renewalBatches' => $renewalBatches,
        ]);
    }
}
