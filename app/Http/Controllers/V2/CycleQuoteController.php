<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Events\LeadsCount;
use App\Http\Controllers\Controller;
use App\Http\Requests\CycleQuoteRequest;
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
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use Illuminate\Http\Request;

class CycleQuoteController extends Controller
{
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

        return inertia('CycleQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => CycleQuoteRepository::getData(true, true),
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
     * @param $quoteTypeCode
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
     * @param $quoteTypeCode
     * @param $quoteId
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
        $quote = CycleQuoteRepository::getBy('uuid', $uuid);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();

        $quote->load('documents.createdBy:id,name,email');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
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
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        if (AMLService::checkAMLStatusFailed(QuoteTypes::CYCLE->id(), $quote->id)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $duplicateAllowedLobs = (new CentralService())->duplicateAllowedLobsList(QuoteTypes::CYCLE->value, $quote->code);
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::CYCLE->id(), $quote->id);
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::CYCLE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Cycle);

        return inertia('CycleQuote/Show', [
            'quoteType' => QuoteTypes::CYCLE,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
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
            'noteDocumentType' => $noteDocumentType,
            'quoteNotes' => $quoteNotes,
        ]);
    }

    public function cardsView(Request $request)
    {
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::NewLead)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::Allocated)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::Quoted)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::FollowedUp)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::InNegotiation)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::PaymentPending)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::TransactionApproved)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::CYCLE->value, QuoteStatusEnum::PolicyIssued)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $isManagerOrAdminAccess = auth()->user()->hasAnyRole([RolesEnum::CycleManager, RolesEnum::Admin]);

        if (! $isManagerOrAdminAccess) {
            if (auth()->user()->hasRole(RolesEnum::CycleNewBusinessAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::Allocated,
                    QuoteStatusEnum::InNegotiation,
                ])->values()->toArray();

            } elseif (auth()->user()->hasRole(RolesEnum::CycleRenewalAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::NewLead,
                    QuoteStatusEnum::InNegotiation,
                ])->values()->toArray();
            } else {
                $quotes = [];
            }
        }

        return inertia('CycleQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::CYCLE->id(),
            'quoteType' => QuoteTypes::CYCLE->value,
            'totalCount' => CycleQuoteRepository::getData(true, true),
        ]);
    }
}
