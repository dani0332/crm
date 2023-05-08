<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\CarQuote;
use App\Models\GenericModel;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CarRevivalQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Services\CarQuoteService;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();
        $formOptionsData = CarQuoteRepository::getFormOptions();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
            'advisors' => []
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
//    public function edit($uuid)
//    {
//        dd($uuid);
//        $data = CycleQuoteRepository::getFormOptions();
//
//        $quote = CycleQuoteRepository::getBy('uuid', $uuid);
//
//        return inertia('CycleQuote/Form', array_merge($data, [
//                'quote' => $quote,
//            ])
//        );
//    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
//        $quote = CarRevivalQuoteRepository::getBy('uuid', $uuid);
//
//        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
//
//        $quote->load('documents.createdBy');
//
//        $documentTypes = DocumentTypeRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();
//        $paymentMethods = PaymentMethodRepository::orderBy('name')->get();
//
//        $insuranceProviders = InsuranceProviderRepository::getList();
//        $personalPlans = PersonalPlanRepository::get();
//        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CYCLE->value);
//
//        $activities = ActivityRepository::where([
//            'quote_type_id' => QuoteTypes::CYCLE->id(),
//            'quote_request_id' => $quote->id,
//        ])->with('assignee')->orderBy('created_at', 'desc')->get();
//
//        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        return inertia('CarRevivalQuote/Show', [
            'quotes' => [],
            'leadStatuses' => [],
            'advisors' => []
        ]);

//        return inertia('CarRevivalQuote/Show', [
//            'quoteType' => QuoteTypes::CYCLE,
//            'quote' => $quote,
//            'activities' => $activities,
//            'lostReasons' => $lostReasons,
//            'advisors' => $advisors,
//            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
//            'documentTypes' => $documentTypes,
//            'quoteStatuses' => $quoteStatuses,
//            'paymentMethods' => $paymentMethods,
//            'insuranceProviders' => $insuranceProviders,
//            'personalPlans' => $personalPlans,
//            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
//            'storageUrl' => storageUrl(),
//        ]);
    }

}
