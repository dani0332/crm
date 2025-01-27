<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
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
use App\Http\Requests\CycleQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CycleQuoteRepository;
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
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class CycleQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = CycleQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CYCLE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return $value['id'] != QuoteStatusEnum::Lost;
        })->values();

        $count = $personalQuotes->count();
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('CycleQuote/Index', [
            'quotes' => $personalQuotes->simplePaginate(10)->withQueryString(),
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $count : CycleQuoteRepository::getData(true, true),
            'authorizedDays' => intval($authorizedDays->value),
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = CycleQuoteRepository::getFormOptions();

        return inertia('CycleQuote/Form', $data);
    }

    /**
     * @param  $quoteTypeCode
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CycleQuoteRequest $request)
    {
        $response = CycleQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        event(new LeadsCount(CycleQuoteRepository::getData(true, true)));

        return redirect('personal-quotes/cycle/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = CycleQuoteRepository::getFormOptions();

        $quote = CycleQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'CycleQuote/Form',
            array_merge($data, [
                'quote' => $quote,
            ])
        );
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @return void
     */
    public function update($uuid, CycleQuoteRequest $request)
    {
        CycleQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/cycle/'.$uuid)->with('message', 'Quote updated successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {

        /* Start - Temporarily adding for correcting historic data */
        $quote = CycleQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::CYCLE->value);
        /* End - Temporarily adding for correcting historic data */

        $quote = CycleQuoteRepository::getBy('uuid', $uuid);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::CYCLE->value, $quote);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $quote->load('documents.createdBy:id,name,email');

        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Cycle);

        $noteDocumentType = DocumentTypeRepository::where('code', DocumentTypeCode::OD)->first();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::CYCLE->name);
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::CYCLE->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CYCLE->value);
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::CYCLE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::CYCLE->id(), $quoteStatuses);

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $duplicateAllowedLobs = (new CentralService)->duplicateAllowedLobsList(QuoteTypes::CYCLE->value, $quote->code);
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::CYCLE->id(), $quote->id);
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::CYCLE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Cycle);
        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::CYCLE->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::CYCLE->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::CYCLE->value, $quote->payments, $quoteDocuments);
        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::CYCLE->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return inertia('CycleQuote/Show', [
            'quoteType' => QuoteTypes::CYCLE,
            'quote' => fn () => $quote,
            'amlStatusName' => $amlStatusName,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'quoteTypeId' => QuoteTypes::CYCLE->id(),
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'duplicateAllowedLobs' => $duplicateAllowedLobs,
            'modelType' => QuoteTypes::CYCLE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::CycleManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $quote?->payments,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'noteDocumentType' => $noteDocumentType,
            'quoteDocuments' => $quoteNotes,
            'cdnPath' => $cdnPath,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
        ]);
    }

    public function cardsView(Request $request)
    {
        $userTeams = auth()->user()->getUserTeams(auth()->id())->toArray();

        $newBusinessTeam = in_array(TeamNameEnum::CYCLE, $userTeams);
        $renewalsTeam = in_array(TeamNameEnum::CYCLE_RENEWALS, $userTeams);

        $areBothTeamsPresent = $newBusinessTeam && $renewalsTeam;

        $isManagerOrDeputy = auth()->user()->hasAnyRole([RolesEnum::CycleManager]);

        if (($request->is_renewal === null && $areBothTeamsPresent) || ($request->is_renewal === null && $isManagerOrDeputy)) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        } elseif ($request->is_renewal === null && $newBusinessTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::noText]);
        } elseif ($request->is_renewal === null && $renewalsTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        }

        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $newBusiness = [
            QuoteStatusEnum::NewLead => 0,
            QuoteStatusEnum::Quoted => 1,
            QuoteStatusEnum::FollowedUp => 2,
            QuoteStatusEnum::PaymentPending => 3,
            QuoteStatusEnum::TransactionApproved => 4,
            QuoteStatusEnum::PolicyIssued => 5,
        ];

        $renewals = [
            QuoteStatusEnum::Allocated => 0,
            QuoteStatusEnum::Quoted => 1,
            QuoteStatusEnum::FollowedUp => 2,
            QuoteStatusEnum::PaymentPending => 3,
            QuoteStatusEnum::TransactionApproved => 4,
            QuoteStatusEnum::PolicyIssued => 5,
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
        } elseif (array_intersect([TeamNameEnum::CYCLE], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::CYCLE_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::InNegotiation, ])->values()->toArray();
        }

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::Cycle);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Cycle);

        return inertia('CycleQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::CYCLE->id(),
            'quoteType' => QuoteTypes::CYCLE->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : CycleQuoteRepository::getData(true, true),
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            // 'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'areBothTeamsPresent' => $areBothTeamsPresent || $isManagerOrDeputy ? true : false,
            'is_renewal' => ($areBothTeamsPresent || $isManagerOrDeputy ? 'Yes' : $renewalsTeam) ? 'Yes' : ($newBusinessTeam ? 'No' : null),
        ]);
    }
}
