<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\YachtQuoteRequest;
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
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\AMLService;
use App\Services\LookupService;
use AWS\CRT\HTTP\Request;

class YachtQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = YachtQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return $value['id'] != QuoteStatusEnum::Lost;
        })->values();

        return inertia('YachtQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('YachtQuote/Form');
    }

    /**
     * @param $quoteTypeCode
     * @param  BikeQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(YachtQuoteRequest $request)
    {
        $response = YachtQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect('personal-quotes/yacht/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $quote = YachtQuoteRepository::getBy('uuid', $uuid);

        return inertia('YachtQuote/Form', ['quote' => $quote]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = YachtQuoteRepository::getBy('uuid', $uuid);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::YACHT->name);
        $quote->load('documents.createdBy:id,name,email');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::YACHT->id())->get();
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::YACHT->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::YACHT->value);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::YACHT->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        if (AMLService::checkAMLStatusFailed(QuoteTypes::YACHT->id(), $quote->id)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::YACHT->id(), $quote->id);
        $lookupService = app(LookupService::class);
        $industryType = $lookupService->getCompanyTypes();
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Yacht);

        return inertia('YachtQuote/Show', [
            'quoteType' => QuoteTypes::YACHT,
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
            'modelType' => QuoteTypes::YACHT,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::YachtManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'quoteNotes' => $quoteNotes,
        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param  BikeQuoteRequest  $request
     * @return void
     */
    public function update($uuid, YachtQuoteRequest $request)
    {
        YachtQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/yacht/'.$uuid)->with('message', 'Quote updated successfully');
    }

    public function cardsViewHome(Request $request)
    {
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::NewLead)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::Allocated)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::Quoted)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::FollowedUp)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::InNegotiation)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PaymentPending)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::TransactionApproved)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::YACHT->value, QuoteStatusEnum::PolicyIssued)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $isManagerOrAdminAccess = auth()->user()->hasAnyRole([RolesEnum::YachtManager, RolesEnum::Admin]);

        if (!$isManagerOrAdminAccess) {
            if (auth()->user()->hasRole(RolesEnum::YachtNewBusinessAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::Allocated,
                    QuoteStatusEnum::InNegotiation
                ])->values()->toArray();

            } elseif (auth()->user()->hasRole(RolesEnum::YachtRenewalAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::NewLead,
                    QuoteStatusEnum::InNegotiation,
                ])->values()->toArray();
            } else {
                $quotes = [];
            }
        }

        return inertia('YachtQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnums' => $quoteStatusEnums,
            'quoteTypeId' => QuoteTypes::YACHT->id(),
        ]);
    }
}
