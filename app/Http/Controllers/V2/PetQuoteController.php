<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Events\LeadsCount;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetQuoteRequest;
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
use App\Repositories\PetQuoteRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use Illuminate\Http\Request;

class PetQuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $personalQuotes = PetQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();
        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return $value['id'] != QuoteStatusEnum::Lost;
        })->values();

        return inertia('PetQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => PetQuoteRepository::getData(true, true),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = PetQuoteRepository::getFormOptions();

        return inertia('PetQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(PetQuoteRequest $request)
    {
        $response = PetQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        event(new LeadsCount(PetQuoteRepository::getData(true, true)));

        return redirect(route('pet-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        $quote = PetQuoteRepository::getBy('uuid', $uuid);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();
        $noteDocumentType = DocumentTypeRepository::where('code', DocumentTypeCode::OD)->first();
        $membersDetail = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name);
        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::PET->id());
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::PET->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::PET->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $duplicateAllowedLobs = (new CentralService())->duplicateAllowedLobsList(QuoteTypes::PET->value, $quote->code);
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::PET->id(), $quote->id);
        if (AMLService::checkAMLStatusFailed(QuoteTypes::PET->id(), $quote->id)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $quoteNotes = QuoteNoteRepository::getBy($quote->id, quoteTypeCode::Pet);

        return inertia('PetQuote/Show', [
            'quoteType' => QuoteTypes::PET,
            'quote' => fn () => $quote,
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
            'modelType' => QuoteTypes::PET,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::PetManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'quoteTypeId' => QuoteTypeId::Pet,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'noteDocumentType' => $noteDocumentType,
            'quoteNotes' => $quoteNotes,
            'cdnPath' => $cdnPath,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = PetQuoteRepository::getFormOptions();
        $quote = PetQuoteRepository::getBy('uuid', $uuid);

        return inertia('PetQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PetQuoteRequest $request, $uuid)
    {
        PetQuoteRepository::update($uuid, $request->validated());

        return redirect(route('pet-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function cardsView(Request $request)
    {
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::PET->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $isManagerOrAdminAccess = auth()->user()->hasAnyRole([RolesEnum::PetManager, RolesEnum::Admin]);

        if (! $isManagerOrAdminAccess) {
            if (auth()->user()->hasRole(RolesEnum::PetNewBusinessAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::Allocated,
                    QuoteStatusEnum::InNegotiation,
                ])->values()->toArray();

            } elseif (auth()->user()->hasRole(RolesEnum::PetRenewalAdvisor)) {
                $quotes = collect($quotes)->whereNotIn('id', [
                    QuoteStatusEnum::NewLead,
                    QuoteStatusEnum::InNegotiation,
                ])->values()->toArray();
            } else {
                $quotes = [];
            }
        }

        // Todo:: Need to send total Counts and Oppurtunity Counts

        return inertia('PetQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::PET->id(),
            'quoteType' => QuoteTypes::PET->value,
            'totalCount' => PetQuoteRepository::getData(true, true),
        ]);
    }
}
