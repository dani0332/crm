<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarRevivalQuoteRequest;
use App\Repositories\CarRevivalQuoteRepository;
use Illuminate\Http\Request;

use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanExclusionsCode;
use App\Enums\CarPlanFeaturesCode;
use App\Enums\HealthTeamType;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\GenericModel;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\AMLService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;

class CarRevivalQuoteController extends Controller
{
    protected $genericModel;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    protected $activityService;
    protected $customerService;
    protected $quoteDocumentService;
    protected $lookupService;

    use GenericQueriesAllLobs;

    public function __construct(
        CRUDService $crudService,
        DropdownSourceService $dropdownSourceService,
        CarQuoteService $carQuoteService,
        Request $request,
        ActivitiesService $activityService,
        CustomerService $customerService,
        QuoteDocumentService $quoteDocumentService,
        LookupService $lookupService,
    ) {
        $this->genericModel = new GenericModel();
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->activityService = $activityService;
        $this->customerService = $customerService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->lookupService = $lookupService;
        $this->setModelType($request);
        $this->fillModelByModelType(ucwords($this->genericModel->modelType), $request);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions();
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions(false);
        $quote = CarRevivalQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'CarRevivalQuote/Form',
            [
                'form_options' => $formOptionsData,
                'quote' => $quote,
            ]
        );
    }

    public function show($id, Request $request)
    {
        if (strrpos(request()->getRequestUri(), '/') === strlen(request()->getRequestUri()) - 1) {
            return redirect(request()->url());
        }
        $quoteType = strtolower($this->genericModel->modelType);
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $paymentTooltipEnum = PaymentTooltip::asArray();

        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);

        abort_if(!$record, 404);
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', $quoteTypeId);
        $model = $this->genericModel;
        if (AMLService::checkAMLStatusFailed($quoteTypeId, $record->id)) {
            $leadStatuses = collect($leadStatuses)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::TransactionApproved;
            })->values();
        }
        $advisors = [];
        if (
            !(auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor])) &&
            strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && ($record->health_team_type == HealthTeamType::EBP ||
                $record->health_team_type == HealthTeamType::RM_NB || $record->health_team_type == HealthTeamType::RM_SPEED)
        ) {
            $advisors = $this->crudService->getEBPAndRMAdvisors();
        } elseif (strtolower($this->genericModel->modelType) == 'business') {
            $advisors = $this->crudService->getRMAndBusinessAdvisors();
        } else {
            $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        }

        $documentTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload(QuoteTypeId::Car);

        $lostReasons = $this->lookupService->getLostReasons();

        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType) . 'QuoteService'}->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;

        $paymentMethods = $this->lookupService->getPaymentMethods();

        $listQuotePlans = $this->carQuoteService->getPlans($id);
        $quoteDocuments = $this->quoteDocumentService->getQuoteDocuments($model->modelType, $record->id);

        $displaySendPolicyButton = (bool) $this->quoteDocumentService->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/';
        $ecomCarInsuranceQuoteUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL');

        $activitiesData = $this->activityService->getActivityByLeadId($record->id, strtolower($model->modelType));
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
            ];
            array_push($activities, $updatedActivity);
        }

        $customerAdditionalContacts = $this->customerService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $storageUrl = storageUrl();
        $paymentStatusEnum = PaymentStatusEnum::asArray();
        return inertia('CarRevivalQuote/Show', [
            'quote' => $record,
            'leadStatuses' => array_values($leadStatuses->toArray()),
            'advisors' => $advisors,
            'documentTypes' => $documentTypes,
            'lostReasons' => $lostReasons,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'carPlanFeaturesCode' => CarPlanFeaturesCode::asArray(),
            'carPlanExclusionsCode' => CarPlanExclusionsCode::asArray(),
            'carPlanAddonsCode' => CarPlanAddonsCode::asArray(),
            'quoteType' => quoteTypeCode::Car,
            'can' => [
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && !auth()->user()->hasRole(RolesEnum::PA),
            ],
            'payments' => $payments,
            'quoteRequest' => $paymentEntityModel,
            'paymentMethods' => $paymentMethods,
            'listQuotePlans' => $listQuotePlans,
            'quoteDocuments' => $quoteDocuments,
            'sendPolicy' => (bool) $displaySendPolicyButton,
            'cdnPath' => $cdnPath,
            'activities' => $activities,
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomCarInsuranceQuoteUrl' => $ecomCarInsuranceQuoteUrl,
            'storageUrl' => $storageUrl,
            'paymentStatusEnum' => $paymentStatusEnum,
            'paymentTooltipEnum' => $paymentTooltipEnum,

        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, CarRevivalQuoteRequest $carRevivalQuoteRequest)
    {
        CarRevivalQuoteRepository::where(['uuid' => $uuid])->update($carRevivalQuoteRequest->validated());

        return back()->with('message', 'Quote updated successfully');
    }

    public function updateQuote(Request $request)
    {

        CarRevivalQuoteRepository::updateQuote($request);
    }

    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();

        if (strpos($url, 'car')) {
            $this->genericModel->modelType = 'Car';
        }
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;
        if ($modelType == null) {
            $modelType = $request->get('modelType');
        }
        $ignoreModelTypes = [quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht];
        if (!in_array($modelType, $ignoreModelTypes) && $modelType != null) {
            $quoteTypes = 'Health,Car,Travel,Life,Home,Business,Pet';
            $serviceType = str_contains($quoteTypes, ucwords($modelType)) ? strtolower($modelType) . 'QuoteService' : lcfirst(ucwords($modelType)) . 'Service';
            $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
            $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
            $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
        }
    }
}
