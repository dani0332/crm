<?php

namespace App\Http\Controllers;


use Inertia\Response;
use RuntimeException;
use App\Enums\RolesEnum;
use App\Enums\QuoteTypeId;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\TravelQuoteService;
use Illuminate\Support\Facades\Auth;


class TravelController extends Controller
{
    protected $service;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    const TYPE = quoteTypeCode::Travel;

    const TYPE_ID = QuoteTypeId::Travel;

    /**
     * TravelController constructor.
     * @param TravelQuoteService $travelQuoteService
     */
    public function __construct(TravelQuoteService $travelQuoteService, LookupService $lookupService, CRUDService $crudService)
    {
        $this->service = $travelQuoteService;
        $this->genericModel = $this->service->getGenericModel(self::TYPE);
        $this->lookupService = $lookupService;
        $this->crudService = $crudService;
    }


    /**
     * @return ResponseFactory|Response
     * @throws RuntimeException
     */
    public function index(Request $request)
    {
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $gridData = $this->service->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();
        return inertia('TravelQuote/Index', [
            'quotes' => $quotes,
            'dropdownSource' => $dropdownSource,
        ]);
    }



    public function show($id)
    {

        $quote = $this->service->getQuoteByUUID($id);
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $payments = $quote->payments;
        $paymentMethods = $this->lookupService->getPaymentMethods();
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $quote->code);
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        //Checking if the loggedIn user is Renewal User

        $isRenewalUser = Auth::user()->isRenewalUser();
        $renewalAdvisors = [];
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $this->crudService->fillNewBusinessData($this->genericModel);
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }

        return inertia('TravelQuote/Show', [
            'quote' => $quote,
            'dropdownSource' => $dropdownSource,
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'permissions' => [
                'dmin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
            ]
        ]);
    }



    /**
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws RuntimeException
     */
    public function cardsView(Request $request)
    {
        $quotes = [];
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $allowedStatusIds = $this->service->getQuoteStatuses();

        foreach ($dropdownSource['quote_status_id'] as $key => $status) {
            if(!in_array($status['id'], $allowedStatusIds)) {
                continue;
            }

            $quotes[] = [
                'id' => $status['id'],
                'title' => $status['text'],
                'data' => getDataAgainstStatus('Travel', $status['id'])
            ];
        }

        return inertia('TravelQuote/Cards', [
            'quotes' => $quotes,
        ]);
    }
}
