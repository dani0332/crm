<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Http\Requests\StoreTravelRequest;
use App\Http\Requests\UpdateTravelRequest;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\TravelQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Inertia\ResponseFactory;
use RuntimeException;

class TravelController extends Controller
{
    protected $travelQuoteService;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    const TYPE = quoteTypeCode::Travel;
    const TYPE_ID = QuoteTypeId::Travel;

    /**
     * TravelController constructor.
     *
     * @param  TravelQuoteService  $travelQuoteService
     */
    public function __construct(TravelQuoteService $travelQuoteService, LookupService $lookupService, CRUDService $crudService)
    {
        $this->travelQuoteService = $travelQuoteService;
        $this->genericModel = $this->travelQuoteService->getGenericModel(self::TYPE);
        $this->lookupService = $lookupService;
        $this->crudService = $crudService;
    }

    /**
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function index(Request $request)
    {
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $gridData = $this->travelQuoteService->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();

        return inertia('TravelQuote/Index', [
            'quotes' => $quotes,
            'dropdownSource' => $dropdownSource,
        ]);
    }

    /**
     * @param $id
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function show($id)
    {
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);

        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $record->code);
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);

        $isRenewalUser = auth()->user()->isRenewalUser();
        $renewalAdvisors = $this->travelQuoteService->getRenewalAdvisors();
        $this->travelQuoteService->fillData();

        $ecomDetails = [
            'premium' => $record->premium,
            'paidAt' => $record->paid_at,
            'paymentStatus' => $record->payment_status_id_text,
            'planName' => $record->plan_id_text,
        ];
        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->travelQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->travelQuoteService->getQuoteDocuments($this->genericModel->modelType, $record->id);
        $displaySendPolicyButton = $this->travelQuoteService->displaySendPolicyButton($record, $quoteDocuments, self::TYPE_ID);
        $documentTypes = $this->travelQuoteService->getQuoteDocumentsForUpload(self::TYPE_ID);
        $documentTypes = collect($documentTypes)->groupBy('category');

        $activities = $this->travelQuoteService->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        $customerAdditionalContacts = $this->travelQuoteService->getAdditionalContacts($record->customer_id, $record->mobile_no);

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

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
            'travelers' => $this->travelQuoteService->getMembersDetail($record->id),
            'ecomDetails' => $ecomDetails,
            'quoteDocuments' => $quoteDocuments,
            'documentTypes' => $documentTypes,
            'cdnPath' => $cdnPath,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'emailStatuses' => $this->travelQuoteService->getEmailStatus(self::TYPE_ID, $record->id),
            'listQuotePlans' => $this->travelQuoteService->listQuotePlans($id),
            'activities' => $activities,
            'isAdmin' => auth()->user()->isAdmin(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'ecomTravelInsuranceQuoteUrl' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
                'canNotApprovePayments' => auth()->user()->cannot(PermissionsEnum::ApprovePayments),
            ],
            'enums' => [
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $isRenewalUser = auth()->user()->isRenewalUser();

        $renewalAdvisors = $this->travelQuoteService->getRenewalAdvisors();

        $this->travelQuoteService->fillData();

        $fieldsToCreate = $this->travelQuoteService->getFieldsToCreate('skipProperties');
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $customTitles = [];
        foreach ($fieldsToCreate as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $fields = [];
        foreach ($fieldsToCreate as $property => $value) {
            $value = array_diff(explode('|', $value), ['title']);
            $type = $value[0] == 'select' ? 'select' : $value[1] ?? 'text';
            $fields[$property] = [
                'type' => $type,
                'required' => in_array('required', $value),
                'readonly' => in_array('readonly', $value),
                'disabled' => in_array('disabled', $value),
                'value' => '',
                'label' => $customTitles[$property],
                'options' => $dropdownSource[$property] ?? [],
            ];
        }

        $model = $this->genericModel;

        return inertia('TravelQuote/Create', [
            'model' => json_encode($model->properties),
            'customTitles' => $customTitles,
            'fields' => $fields,
            'dropdownSource' => $dropdownSource,
            'renewalAdvisors' => $renewalAdvisors ?? [],
            'isRenewalUser' => $isRenewalUser,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTravelRequest $request)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $record = $this->travelQuoteService->saveTravelQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        }

        redirect('/quotes/travel')->with('message', 'Record created successfully');
    }

    /**
     * @param  Request  $request
     * @param $id
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $dropdownSource = $this->travelQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->travelQuoteService->getFieldsToUpdate('skipProperties');
        $customTitles = [];
        foreach ($fieldsToUpdate as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $fields = [];
        foreach ($fieldsToUpdate as $property => $value) {
            $value = array_diff(explode('|', $value), ['title']);
            $type = $value[0] == 'select' ? 'select' : $value[1] ?? 'text';
            $fields[$property] = [
                'type' => $type,
                'required' => in_array('required', $value),
                'readonly' => in_array('readonly', $value),
                'disabled' => in_array('disabled', $value),
                'value' => $record->$property ?? '',
                'label' => $customTitles[$property],
                'options' => $dropdownSource[$property] ?? [],
            ];
        }
        $fields['email']['disabled'] = true;
        $fields['mobile_no']['disabled'] = true;

        return inertia('TravelQuote/Edit', [
            'quote' => $record,
            'modelType' => $this->genericModel->modelType,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'dropdownSource' => $dropdownSource,
            'model' => json_encode($this->genericModel->properties),
            'fields' => $fields,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTravelRequest $request, $id)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        $this->travelQuoteService->updateTravelQuote($request, $id);

        return redirect('/quotes/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
    }

    public function planDetails($quoteId, $planId)
    {
        $quotePlans = $this->travelQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) == 'string') {
            return response()->json([
                'message' => $quotePlans,
            ], 404);
        }

        $listQuotePlans = $quotePlans->quotes->plans;
        foreach ($listQuotePlans as $listQuotePlan) {
            if ($listQuotePlan->id == $planId) {
                $listQuotePlansMembers = $listQuotePlan->memberPremiumBreakdown;
                $listQuotePlanName = $listQuotePlan->name;
                $providerCode = $listQuotePlan->providerCode;
                $providerName = $listQuotePlan->providerName;
                $travelType = $listQuotePlan->travelType;
                $actualPremium = $listQuotePlan->actualPremium;
                $discountPremium = $listQuotePlan->discountPremium;
                $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                $listQuotePlanBenefitstravelInconvenienceCover = $listQuotePlan->benefits->travelInconvenienceCover;
                $listQuotePlanBenefitsemergencyMedicalCover = $listQuotePlan->benefits->emergencyMedicalCover;
                $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                $listQuotePlanBenefitsCovid19 = $listQuotePlan->benefits->covid19;
                $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;

                foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                    $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                }
            }
        }

        $data = [
            'listQuotePlanName' => $listQuotePlanName,
            'providerCode' => $providerCode,
            'providerName' => $providerName,
            'travelType' => $travelType,
            'actualPremium' => $actualPremium,
            'discountPremium' => $discountPremium,
            'listQuotePlanBenefitsInclusions' => $listQuotePlanBenefitsInclusions,
            'listQuotePlanBenefitsExclusions' => $listQuotePlanBenefitsExclusions,
            'listQuotePlanBenefitsFeatures' => $listQuotePlanBenefitsFeatures,
            'listQuotePlanBenefitsCovid19' => $listQuotePlanBenefitsCovid19,
            'listQuotePlanBenefitsPolicyDetails' => $listQuotePlanBenefitsPolicyDetails,
            'listQuotePlanBenefitsPolicyDetailLink' => $listQuotePlanBenefitsPolicyDetailLink ?? '',
            'modelName' => self::TYPE,
            'listQuotePlansMembers' => $listQuotePlansMembers,
            'listQuotePlanBenefitstravelInconvenienceCover' => $listQuotePlanBenefitstravelInconvenienceCover,
            'listQuotePlanBenefitsemergencyMedicalCover' => $listQuotePlanBenefitsemergencyMedicalCover,
        ];

        return response()->json($data, 200);
    }
}
