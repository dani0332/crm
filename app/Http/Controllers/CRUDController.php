<?php

namespace App\Http\Controllers;

use App\Models\GenericModel;
use App\Models\InsuranceProvider;
use App\Services\BusinessQuoteService;
use Illuminate\Http\Request;
use App\Services\DropdownSourceService;
use App\Services\HealthQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\HomeQuoteService;
use App\Services\LeadStatusService;
use App\Services\LifeQuoteService;
use App\Services\TeamService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use DataTables;
use Auth;
use Illuminate\Support\Facades\Redirect;
use Config;
use DB;

class CRUDController extends Controller
{
    protected $genericModel;
    protected $healthQuoteService;
    protected $teamsService;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    protected $leadStatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    protected $businessQuoteService;
    protected $userService;
    public function __construct(
        HealthQuoteService $healthService,
        TeamService $teamsService,
        CRUDService $crudService,
        DropdownSourceService $dropdownSourceService,
        CarQuoteService $carQuoteService,
        LeadStatusService $leadStatusService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService,
        UserService $userService,
        Request $request
    ) {
        $this->genericModel = new GenericModel();
        $this->healthQuoteService = $healthService;
        $this->teamsService = $teamsService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->leadStatusService = $leadStatusService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->userService = $userService;
        $this->setModelType($request);
        $this->fillModelByModelType(ucwords($this->genericModel->modelType), $request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $gridData = $this->crudService->getGridData($this->genericModel, $request);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
        $dropdownSource = $customTitles = [];
        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $dropdownValue = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $dropdownValue;
            }
        }
        $model = $this->genericModel;
        if ($request->ajax()) {
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
            return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy'));
        }
        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $customTitles = $dropdownSource = [];
        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
        }
        $model = $this->genericModel;
        return view('shared.add', compact('model', 'dropdownSource', 'customTitles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelPropertiesList = json_decode($request->get('model'), true);
        $modelType = json_decode($request->modelType, true);
        $validateArray = [];
        if ($modelType == 'Home') {
            $validateArray = $this->homeQuoteService->getValidationArray($modelPropertiesList, $request);
        } else {
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id') {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $this->validate($request, $validateArray);
        $recordUUID = $this->crudService->saveModelByType($modelType, $request);
        if(str_contains($recordUUID, 'Error')) {
            return Redirect::back()->with('message', $modelType . ' '. explode(':', $recordUUID)[1])->withInput();
        }
        else{
            return redirect('/quotes/' . strtolower($modelType) . '/' . $recordUUID)->with('success', ((str_contains(strtolower($modelType), 'team') ? 'Team' : (str_contains(strtolower($modelType), 'leadstatus') ? 'Lead Status' : $modelType))) . ' has been stored');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        if(!$record) abort(404);
        $model = $this->genericModel;
        $customTitles = $customTableList = [];
        foreach ($model->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'customTable')) {
                $customTableList[$property] = $this->dropdownSourceService->getOnlySelectedItemName($property, $id);
            }
        }
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business';
        $serviceType = str_contains($quoteTypes, ucwords($model->modelType)) ? strtolower($model->modelType) . 'QuoteService' : lcfirst(ucwords($model->modelType)) . 'Service';

        if ($this->genericModel->modelType == "Car") { // Car plans to display on detail view

            $listQuotePlans = '';
            $quotePlans = $this->carQuoteService->getQuotePlans($id);
            $carQuotePlanAddons = $this->carQuoteService->getCarQuotePlanAddons($id);

            if (gettype($quotePlans) != 'string') {
                $listQuotePlans = $quotePlans->quotes->plans;
            } else {
                $listQuotePlans = $quotePlans;
            }

            $ecomCarInsuranceQuoteUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL');

            return view('shared.show', compact(['record', 'model', 'customTitles', 'listQuotePlans', 'customTableList','ecomCarInsuranceQuoteUrl','carQuotePlanAddons']));
        } else {
            return view('shared.show', compact(['record', 'model', 'customTitles', 'customTableList']));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response        klm[jo]
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $model = $this->genericModel;
        $dropdownSource = [];
        $customTitles = [];
        $customLists = [];
        foreach ($model->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
            if (str_contains($value, 'customTable')) {
                $data = $this->dropdownSourceService->getCustomDropdownList($property, $record[0]->id);
                $customLists[$property] = $data;
            }
        }
        return view('shared.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists']));
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
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && ($property !== "email" && $property !== "mobile_no"))
            {
                $validateArray[$property] = 'required';
            }

        }
        $this->validate($request, $validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        return redirect('/quotes/' . strtolower(str_replace('"', '', $request->modelType)) . '/' . $id)->with('success', json_decode($request->modelType, true) . ' has been stored');
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

    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();
        if (strpos($url, 'health')) $this->genericModel->modelType = 'Health';
        if (strpos($url, 'travel')) $this->genericModel->modelType = 'Travel';
        if (strpos($url, 'teams')) $this->genericModel->modelType = 'Teams';
        if (strpos($url, 'car')) $this->genericModel->modelType = 'Car';
        if (strpos($url, 'life')) $this->genericModel->modelType = 'Life';
        if (strpos($url, 'home')) $this->genericModel->modelType = 'Home';
        if (strpos($url, 'business')) $this->genericModel->modelType = 'Business';
        if (strpos($url, 'leadstatus')) $this->genericModel->modelType = 'LeadStatus';

    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = $request->get('modelType') ?? $type;
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business';
        $serviceType = str_contains($quoteTypes, ucwords($modelType)) ? strtolower($modelType) . 'QuoteService' : lcfirst(ucwords($modelType)) . 'Service';
        $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
        $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
        $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
    }

    public function getDropdownSourceNameForDisplay($modelType, $propertyName, $recordId)
    {

        $data = $this->dropdownSourceService->getDropdownSource($propertyName);
        $recordName = '';
        $record = $this->crudService->getEntity($modelType, $recordId);
        foreach ($data as $item) {
            if ($item->id == $record[$propertyName]) {
                $recordName = $item->text ?? $item->name;
            }
        }
        return $recordName;
    }

    public function plan_details($quoteId, $planId)
    {
        $quotePlans = $this->carQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;
            foreach ($listQuotePlans as $listQuotePlan) { // Main

                if ($listQuotePlan->id == $planId) {
                    $listQuotePlanName = $listQuotePlan->name;
                    $providerCode = $listQuotePlan->providerCode;
                    $providerName = $listQuotePlan->providerName;
                    $repairType = $listQuotePlan->repairType;
                    $actualPremium = $listQuotePlan->actualPremium;
                    $discountPremium = $listQuotePlan->discountPremium;
                    $listQuotePlanAddonss = $listQuotePlan->addons;
                    $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                    $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                    $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                    $listQuotePlanBenefitsRsas = $listQuotePlan->benefits->roadSideAssistance;
                    $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;

                    foreach ($listQuotePlanAddonss as $listQuotePlanAddon) {
                        $listQuotePlanAddons[] = $listQuotePlanAddon; // Get Addons Names

                        foreach ($listQuotePlanAddon->carAddonOption as $listQuotePlanAddonsOptions) {
                            $listQuotePlanAddonValues[] = $listQuotePlanAddonsOptions->value;
                            $listQuotePlanAddonPrices[] = $listQuotePlanAddonsOptions->price;
                        }
                    }
                    foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                        $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                    }
                }
            }
            return view('shared.plan_details', compact([
                'listQuotePlanName', 'providerCode', 'providerName', 'repairType',
                'actualPremium', 'discountPremium', 'listQuotePlanAddons', 'listQuotePlanAddonValues', 'listQuotePlanBenefitsInclusions',
                'listQuotePlanBenefitsExclusions', 'listQuotePlanBenefitsFeatures', 'listQuotePlanBenefitsRsas',
                'listQuotePlanBenefitsPolicyDetailLink', 'listQuotePlanAddonPrices'
            ]));
        }
    }

    public function manualLeadAssign(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId;
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $tmLeadsId) {
            $updateTmLead = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($tmLeadsId);
            $userId = (int)$assignedToUserIdNew;
            $updateTmLead->advisor_id = $userId;
            $updateTmLead->save();
        }

        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);
        return Redirect::back()->with('success', $request->modelType . ' Leads has been Assigned To ' . $assignedUserName);
    }

    public function updateDiscountedPremium(Request $request)
    {
        foreach($request->discountedPremium as $tmLeadsId) {
            echo "<pre>"; print_r($tmLeadsId);
        }
    }

    public function add_quote(Request $request)
    {
        $insuranceproviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return view('shared.add_quote', compact('insuranceproviders'));
    }
}
