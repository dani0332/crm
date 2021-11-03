<?php

namespace App\Http\Controllers;

use App\Models\GenericModel;
use Illuminate\Http\Request;
use App\Services\DropdownSourceService;
use App\Services\HealthQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\TeamService;
use DataTables;
class CRUDController extends Controller
{
    protected $genericModel;
    protected $healthQuoteService;
    protected $teamService;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    public function __construct(Request $request, HealthQuoteService $healthService, TeamService $teamService, CRUDService $crudService, DropdownSourceService $dropdownSourceService,
    CarQuoteService $carQuoteService)
    {
        $this->genericModel = new GenericModel();
        $this->healthQuoteService = $healthService;
        $this->teamService = $teamService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->setModelType($request);
        $this->fillModelByModelType($this->genericModel->modelType);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = $this->genericModel;
        $gridData = $this->crudService->getGridData($this->genericModel);

        $customTitles = [];
        $dropdownSource = [];

        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if(str_contains($value, 'select')){
                $dropdownValue = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $dropdownValue;
            }
        }

        if ($request->ajax()) {
            foreach ($model->searchProperties as $item) {
                if(!empty($request[$item])){
                    $gridData = $gridData->where($item, '=', $request[$item]);
                }
            }
            return DataTables::of($gridData)
            ->addIndexColumn()
            ->make(true);
            return view('shared.view', compact('model','dropdownSource', 'customTitles'));
        }
        return view('shared.view', compact('model','dropdownSource', 'customTitles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->fillModelByModelType($this->genericModel->modelType);
        $model = $this->genericModel;
        $dropdownSource = [];
        $customTitles = [];
        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if(str_contains($value, 'select')){
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
        }
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
        $modelPropertiesList = json_decode($request->all()['model'], true);
        $validateArray = [];
        foreach($modelPropertiesList as $property => $value) {
            if(strpos($value, 'required')){
                $validateArray[$property] = 'required';
            }
        }
        $this->validate($request,$validateArray);
        $this->crudService->saveModelByType(json_decode($request->modelType, true), $request);
        return redirect()->back()->with('success', json_decode($request->modelType, true).' has been stored');
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
        $model = $this->genericModel;
        $customTitles = [];

        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
        }

        if($this->genericModel->modelType == "Car") { // Car plans to display on detail view
            $quotePlans = $this->carQuoteService->getQuotePlans($id);
            $listQuotePlans = $quotePlans->quotes->plans;
            //echo "<pre>"; print_r($listQuotePlans); exit;
            return view('shared.show', compact(['record', 'model', 'customTitles', 'listQuotePlans']));
        }
        else {
            return view('shared.show', compact(['record', 'model', 'customTitles']));
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
        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if(str_contains($value, 'select')){
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
        }
        return view('shared.edit', compact(['record', 'model', 'dropdownSource', 'customTitles']));
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
        foreach($modelPropertiesList as $property => $value) {
            if(strpos($value, 'required')){
                $validateArray[$property] = 'required';
            }
        }
        $this->validate($request,$validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        return redirect()->back()->with('success', json_decode($request->modelType, true).' has been stored');
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

    private function setModelType(Request $request){
        if(strpos($request->fullUrl(), 'health')) $this->genericModel->modelType = 'Health';
        if(strpos($request->fullUrl(), 'life')) $this->genericModel->modelType = 'Life';
        if(strpos($request->fullUrl(), 'teams')) $this->genericModel->modelType = 'Teams';
        if(strpos($request->fullUrl(), 'car')) $this->genericModel->modelType = 'Car';
    }

    private function fillModelByModelType ($modelType)
    {
        switch ($modelType) {
            case 'Car':
                $this->genericModel->properties = $this->carQuoteService->fillModelProperties();
                $this->genericModel->skipProperties = $this->carQuoteService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->carQuoteService->fillModelSearchProperties();
                break;
            case 'Health':
                $this->genericModel->properties = $this->healthQuoteService->fillModelProperties();
                $this->genericModel->skipProperties = $this->healthQuoteService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->healthQuoteService->fillModelSearchProperties();
                break;
            case 'Teams':
                $this->genericModel->properties = $this->teamService->fillModelProperties();
                $this->genericModel->skipProperties = $this->teamService->fillModelSkipProperties();
                break;
            default:
                break;
        }
    }

    public function getDropdownSourceNameForDisplay($modelType, $propertyName, $recordId){

        $data = $this->dropdownSourceService->getDropdownSource($propertyName);
        $recordName = '';
        $record = $this->crudService->getEntity($modelType, $recordId);
        foreach ($data as $item) {
            if($item->id == $record[$propertyName]){
                $recordName = $item->text ?? $item->name;
            }
        }
        return $recordName;
    }

    public function plan_details($quoteId, $planId){

        $quotePlans = $this->carQuoteService->getQuotePlans($quoteId);
        $listQuotePlans = $quotePlans->quotes->plans;

        foreach($listQuotePlans as $listQuotePlan) { // Main

            if($listQuotePlan->id == $planId) {
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
                $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->benefits->policyDetail;

                foreach($listQuotePlanAddonss as $listQuotePlanAddon) {
                    $listQuotePlanAddons[] = $listQuotePlanAddon; // Get Addons Names

                    foreach($listQuotePlanAddon->carAddonOption as $listQuotePlanAddonsOptions) {
                        $listQuotePlanAddonValues[] = $listQuotePlanAddonsOptions->value;
                        $listQuotePlanAddonPrices[] = $listQuotePlanAddonsOptions->price;
                        //echo "<pre>"; print_r($listQuotePlanAddonPrices);
                    }
                }
                //echo "<pre>"; print_r($listQuotePlanAddonPrices);
            }
        }

        //echo "<pre>"; print_r($listQuotePlanAddonPrices);
        return view('shared.plan_details', compact(['listQuotePlanName','providerCode','providerName','repairType'
        ,'actualPremium','discountPremium','listQuotePlanAddons','listQuotePlanAddonValues','listQuotePlanBenefitsInclusions'
        ,'listQuotePlanBenefitsExclusions','listQuotePlanBenefitsFeatures','listQuotePlanBenefitsRsas'
        ,'listQuotePlanBenefitsPolicyDetails','listQuotePlanAddonPrices']));
    }

}
