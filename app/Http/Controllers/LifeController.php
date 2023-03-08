<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Services\CRUDService;
use App\Services\LifeQuoteService;
use App\Services\LookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\ResponseFactory;

class LifeController extends Controller
{
    protected $service;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    const TYPE = quoteTypeCode::Life;
    const TYPE_ID = QuoteTypeId::Life;

    /**
     * TravelController constructor.
     *
     * @param  LifeQuoteService  $service
     * @param  LookupService  $lookupService
     * @param  CRUDService  $crudService
     */
    public function __construct(LifeQuoteService $service, LookupService $lookupService, CRUDService $crudService)
    {
        $this->service = $service;
        $this->genericModel = $this->service->getGenericModel(self::TYPE);
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
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $gridData = $this->service->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();

        return inertia('LifeQuote/Index', [
            'quotes' => $quotes,
            'dropdownSource' => $dropdownSource,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $isRenewalUser = Auth::user()->isRenewalUser();
        $renewalAdvisors = $this->service->getRenewalAdvisors();
        $this->service->fillData();

        $fieldsToCreate = $this->service->getFieldsToCreate('skipProperties');
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
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

        return inertia('LifeQuote/Create', [
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
    public function store(Request $request)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $record = $this->service->saveLifeQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        }

        redirect('/quotes/life')->with('message', 'Record created successfully');
    }

    public function show($uuid)
    {
        $quote = $this->service->getEntity($uuid);
        abort_if(! $quote, 404);
        $quoteType = strtolower($this->genericModel->modelType);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $quote->code);
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);

        $isRenewalUser = false;
        $renewalAdvisors = $this->service->getRenewalAdvisors();
        $this->service->fillData();

        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fields = $this->service->fieldsToDisplay($this->service->getFieldsToShow(), $quote);
        $customTitles = [];
        foreach ($fields as $property => $value) {
            if (in_array('title', $value)) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->service->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->service->getQuoteDocuments($this->genericModel->modelType, $quote->id);
        $displaySendPolicyButton = $this->service->displaySendPolicyButton($quote, $quoteDocuments, self::TYPE_ID);
        $documentTypes = $this->service->getQuoteDocumentsForUpload(self::TYPE_ID);
        $documentTypes = collect($documentTypes)->groupBy('category');

        $customerAdditionalContacts = $this->service->getAdditionalContacts($quote->customer_id, $quote->mobile_no);
        $activities = $this->service->getActivityByLeadId($quote->id, strtolower($this->genericModel->modelType));

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        return inertia('LifeQuote/Show', [
            'quote' => $quote,
            'fieldsToDisplay' => $fields,
            'activities' => $activities,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'advisors' => $advisors,
            'renewalAdvisors' => $renewalAdvisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'assignmentTypes' => $assignmentTypes,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'quoteDocuments' => $quoteDocuments,
            'documentTypes' => $documentTypes,
            'cdnPath' => $cdnPath,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'emailStatuses' => $this->service->getEmailStatus(self::TYPE_ID, $quote->id),
            'isAdmin' => auth()->user()->isAdmin(),
            'customerAdditionalContacts' => $customerAdditionalContacts,
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
            ],
            'enums' => [
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $dropdownSource = $this->service->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->service->getFieldsToUpdate('skipProperties');
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
            $value = explode('|', $value);
            $typeOfField = array_diff($value, ['title', 'input', 'required']);
            $typeOfField = array_shift($typeOfField);

            if (in_array('static', $value)) {
                $options = $this->service->getStaticFields($value);
                $dropdownSource[$property] = $options;
                $typeOfField = 'select';
            }

            $fields[$property] = [
                'type' => $typeOfField,
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

        return inertia('LifeQuote/Edit', [
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
    public function update(Request $request, $id)
    {
        $modelPropertiesList = json_decode($request->all()['model'], true);

        $validateArray = [];

        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && $modelSkipPropertiesList != null && ! strpos($modelSkipPropertiesList['update'], $property)) {
                $validateArray[$property] = 'required';
            }
        }
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $this->validate($request, $validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);

        return redirect('/quotes/life'.'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
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
