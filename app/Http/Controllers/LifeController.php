<?php

namespace App\Http\Controllers;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Http\Requests\StoreLifeRequest;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\Life\LifeQuoteService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\ResponseFactory;
use App\Http\Requests\LifeCardLoadMoreRequest;

class LifeController extends Controller
{
    use GenericQueriesAllLobs;

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
    public function index()
    {
        $data = $this->lifeQuoteService->getData();

        return inertia('LifeQuote/Index', $data);
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

        return inertia('LifeQuote/Form', [
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

        return redirect('/personal-quotes/life')->with('message', 'Quote created successfully.');
    }

    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data  */
        $quote = $this->lifeQuoteService->getQuoteBy('uuid', $uuid);
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */

        $data = $this->lifeQuoteService->getShowData($uuid);
        return inertia('LifeQuote/Show', $data);
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

        return inertia('LifeQuote/Form', [
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

        return redirect('/personal-quotes/life'.'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
    }

    public function cardsView()
    {
        $data = $this->lifeQuoteService->getCardsViewData();

        return inertia('LifeQuote/Cards', $data);
    }

    public function getCardsViewLoadMore(LifeCardLoadMoreRequest $request)
    {
        return $this->lifeQuoteService->getCardsViewLoadMore($request->validated());
    }
}
