<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Requests\StoreLifeRequest;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LifeQuoteService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\ResponseFactory;

class LifeController extends Controller
{
    protected $lifeQuoteService;
    protected $lookupService;
    protected $crudService;
    protected $genericModel;

    public const TYPE = quoteTypeCode::Life;
    public const TYPE_ID = QuoteTypeId::Life;

    /**
     * TravelController constructor.
     *
     * @param  LifeQuoteService  $service
     */
    public function __construct(LifeQuoteService $lifeQuoteService, LookupService $lookupService, CRUDService $crudService)
    {
        $this->lifeQuoteService = $lifeQuoteService;
        $this->genericModel = $this->lifeQuoteService->getGenericModel(self::TYPE);
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
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $gridData = $this->lifeQuoteService->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();

        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);

        return inertia('LifeQuote/Index', [
            'quotes' => $quotes,
            'dropdownSource' => $dropdownSource,
            'advisors' => $advisors,
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'lifeAdvisor' => auth()->user()->hasRole(RolesEnum::LifeAdvisor),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LifeManager) ? true : false,
                'isLeadPool' => auth()->user()->isLeadPool(),
                'isManagerORDeputy' => auth()->user()->isManagerOrDeputy(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $isRenewalUser = auth()->user()->isRenewalUser();
        $renewalAdvisors = $this->lifeQuoteService->getRenewalAdvisors();
        $this->lifeQuoteService->fillData();

        $fieldsToCreate = $this->lifeQuoteService->getFieldsToCreate('skipProperties');
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
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
            $value = explode('|', $value);
            $typeOfField = array_diff($value, ['title', 'input', 'required']);
            $typeOfField = array_shift($typeOfField);

            if (in_array('static', $value)) {
                $options = $this->lifeQuoteService->getStaticFields($value);
                $dropdownSource[$property] = $options;
                $typeOfField = 'select';
            }

            $fields[$property] = [
                'type' => $typeOfField,
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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLifeRequest $request)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $record = $this->lifeQuoteService->saveLifeQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        }

        return redirect()->route('life.show', data_get($record, 'quoteUID'))->with('message', 'Quote created successfully.');
    }

    public function show($uuid)
    {
        $quote = $this->lifeQuoteService->getEntity($uuid);
        abort_if(! $quote, 404);
        $quoteType = strtolower($this->genericModel->modelType);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $quote->code);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);

        $isRenewalUser = false;
        $renewalAdvisors = $this->lifeQuoteService->getRenewalAdvisors();
        $this->lifeQuoteService->fillData();

        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fields = $this->lifeQuoteService->fieldsToDisplay($this->lifeQuoteService->getFieldsToShow(), $quote);
        $customTitles = [];
        foreach ($fields as $property => $value) {
            if (in_array('title', $value)) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            } else {
                $customTitles[$property] = ucwords(str_replace('_', ' ', $property));
            }
        }

        $assignmentTypes = [GenericRequestEnum::ASSIGN_WITHOUT_EMAIL => 'Without Email', GenericRequestEnum::ASSIGN_WITH_EMAIL => 'With Email'];
        $isQuoteDocumentEnabled = $this->lifeQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->lifeQuoteService->getQuoteDocuments($this->genericModel->modelType, $quote->id);
        $displaySendPolicyButton = $this->lifeQuoteService->displaySendPolicyButton($quote, $quoteDocuments, self::TYPE_ID);
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Life);

        $customerAdditionalContacts = $this->lifeQuoteService->getAdditionalContacts($quote->customer_id, $quote->mobile_no);
        $activities = $this->lifeQuoteService->getActivityByLeadId($quote->id, strtolower($this->genericModel->modelType));

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        if (! auth()->user()->hasRole(RolesEnum::Engineering)) {
            unset($fields['id']);
        }

        return inertia('LifeQuote/Show', [
            'quote' => $quote,
            'quoteType' => QuoteTypes::LIFE,
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
            'emailStatuses' => $this->lifeQuoteService->getEmailStatus(self::TYPE_ID, $quote->id),
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
                'canEditQuote' => auth()->user()->can(strtolower($this->genericModel->modelType).'-quotes-edit'),
            ],
            'paymentDocument' => $paymentDocument,
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
        $dropdownSource = $this->lifeQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $fieldsToUpdate = $this->lifeQuoteService->getFieldsToUpdate('skipProperties');
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
                $options = $this->lifeQuoteService->getStaticFields($value, $property);
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

    public function cardsView(Request $request)
    {
        $dropdownSourceService = app(DropdownSourceService::class);
        $leadStatuses = $dropdownSourceService->getDropdownSource('quote_status_id', self::TYPE_ID);
        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION;
        })->toArray();

        $leadStatuses = array_map(function ($item) {
            $item['data'] = getDataAgainstStatus(self::TYPE, $item['id']);

            return $item;
        }, $leadStatuses);

        return inertia('LifeQuote/Cards', [
            'quotes' => array_values($leadStatuses),
        ]);
    }
}
