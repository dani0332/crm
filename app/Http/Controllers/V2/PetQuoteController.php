<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PetQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
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
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();

        return inertia('PetQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return inertia('PetQuote/Form');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PetQuoteRequest $request)
    {

        $response = PetQuoteRepository::create($request);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Quote created successfully');
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

        $quote->load('documents.createdBy');

        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::PET->id())->get();

        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();

        $insuranceProviders = InsuranceProviderRepository::getList();
        $personalPlans = PersonalPlanRepository::get();
        $advisors = UserRepository::getPersonalQuoteAdvisors();


        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::PET->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        return inertia('PetQuote/Show', [
            'quoteType' => QuoteTypes::PET,
            'quote' => $quote,
            'activities' => $activities,
            'lostReasons' => $lostReasons,
            'advisors' => $advisors,
            'quoteStatusesEnum' => QuoteStatusEnum::asArray(),
            'documentTypes' => $documentTypes,
            'quoteStatuses' => $quoteStatuses,
            'paymentMethods' => $paymentMethods,
            'insuranceProviders' => $insuranceProviders,
            'personalPlans' => $personalPlans,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'storageUrl' => storageUrl(),
            'can' => [
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),
            ],
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
        $quote = PetQuoteRepository::getBy('uuid', $uuid);
//
//        dd( $quote->toArray());
        return inertia('PetQuote/Form',[ 'quote' => $quote]);
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
        PetQuoteRepository::update($uuid, $request->toArray());

        return back()->with('message', 'Quote updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
