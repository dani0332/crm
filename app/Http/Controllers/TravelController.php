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
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Services\LookupService;
use App\Enums\PaymentStatusEnum;
use App\Enums\GenericRequestEnum;
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

        $ecomDetails = [
            'premium' => $record->premium,
            'paidAt' => $record->paid_at,
            'paymentStatus' => $record->payment_status_id_text,
            'planName' => $record->plan_id_text,
        ];
        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->service->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->service->getQuoteDocuments($this->genericModel->modelType, $record->id);
        $displaySendPolicyButton = $this->service->displaySendPolicyButton($record, $quoteDocuments, self::TYPE_ID);
        $documentTypes = $this->service->getQuoteDocumentsForUpload(self::TYPE_ID);
        $documentTypes = collect($documentTypes)->groupBy('category');

        $membersDetail = $this->service->getMembersDetail($record->id);
        $activities = $this->service->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        
        $cdnPath = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/';
        return inertia('TravelQuote/Show', [
            'quote' => $record,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'assignmentTypes' => $assignmentTypes,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'travelers' => $this->service->getMembersDetail($record->id),
            'ecomDetails' => $ecomDetails,
            'quoteDocuments' => $quoteDocuments,
            'documentTypes' => $documentTypes,
            'cdnPath' => $cdnPath,
            'membersDetail' => $membersDetail,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'emailStatuses' => $this->service->getEmailStatus(self::TYPE_ID, $record->id),
            'listQuotePlans' => $this->service->listQuotePlans($id),
            'activities' => $activities,
            'isAdmin' => auth()->user()->isAdmin(),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => !auth()->user()->hasRole(RolesEnum::PA),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
            ],
            'enums' => [
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            ],
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
