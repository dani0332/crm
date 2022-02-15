<?php

namespace App\Http\Controllers;

use App\Models\GenericModel;
use App\Models\InsuranceProvider;
use App\Services\InsuranceProviderService;
use App\Services\CarPlanService;
use App\Services\DropdownSourceService;
use App\Services\CRUDService;
use App\Services\CarPlanCoverageService;
use App\Services\CarPlanAddOnService;
use App\Services\CarPlanAddOnOptionService;
use App\Enums\InsuranceProvder;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Config;
use DB;
class GenericCrudController extends Controller
{
    protected $genericModel;
    protected $insuranceProviderService;
    protected $carPlanService;
    protected $dropdownSourceService;
    protected $carPlanAddOnService;
    protected $carPlanCoverageService;
    protected $carPlanAddOnOptionService;
    public function __construct(
        InsuranceProviderService $insuranceProviderService,
        CRUDService $crudService,
        CarPlanService $carPlanService,
        DropdownSourceService $dropdownSourceService,
        Request $request,
        CarPlanAddOnService $carPlanAddOnService,
        CarPlanCoverageService $carPlanCoverageService,
        CarPlanAddOnOptionService $carPlanAddOnOptionService
    ) {
        $this->genericModel = new GenericModel();
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->insuranceProviderService = $insuranceProviderService;
        $this->carPlanService = $carPlanService;
        $this->carPlanAddOnService = $carPlanAddOnService;
        $this->carPlanCoverageService = $carPlanCoverageService;
        $this->carPlanAddOnOptionService = $carPlanAddOnOptionService;
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
            return view('generic.view', compact('model', 'dropdownSource', 'customTitles'));
        }
        return view('generic.view', compact('model', 'dropdownSource', 'customTitles'));
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
        return view('generic.add', compact('model', 'dropdownSource', 'customTitles'));
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
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $modelType = json_decode($request->get('modelType'), true);
        $validateArray = [];
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && $property != 'id' && !strpos($modelSkipPropertiesList['create'], $property)) {
                $validateArray[$property] = 'required';
            }
        }
        $this->validate($request, $validateArray);
        $record = $this->crudService->saveModelByType($modelType, $request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (!isset($record->id)) {
                return redirect('/generic/' . strtolower($modelType))->with('success',  $modelType. ' has been stored');
            } else {
                return redirect('/generic/' . strtolower($modelType) . '/' . $record->id)->with('success', $modelType. ' has been stored');
            }
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
        if (!$record) abort(404);
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
        $serviceType = $model->modelType. 'Service';
        if ($this->genericModel->modelType == InsuranceProvder::Name) {
            $plansList = $this->carPlanService->getProvderPlans($id);
            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'plansList', 'customTableList'
            ]));
        }
        if ($this->genericModel->modelType == InsuranceProvder::PlanName) {
            $coverageList = $this->carPlanCoverageService->getPlanCoverage($id);
            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'coverageList', 'customTableList'
            ]));
        }
        if ($this->genericModel->modelType == InsuranceProvder::Coverage) {
            $plansList = $this->carPlanAddOnService->getPlanAddon($record->plan_id);

            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'plansList', 'customTableList'
            ]));
        }
        if ($this->genericModel->modelType == InsuranceProvder::PlanAddon) {
            $plansList = $this->carPlanAddOnOptionService->getPlanAddonOption($record->addon_id);

            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'plansList', 'customTableList'
            ]));
        }
        return view('generic.show', compact(['record', 'model', 'customTitles', 'customTableList']));
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
        return view('generic.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists']));
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
        $modelType = json_decode($request->all()['modelType'], true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $validateArray = [];
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && !strpos($modelSkipPropertiesList, $property)) {
                $validateArray[$property] = 'required';
            }
        }
        $this->validate($request, $validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        return redirect('/generic/' . strtolower(str_replace('"', '', $request->modelType)) . '/' . $id)->with('success', json_decode($request->modelType, true) . ' has been updated');
    }



    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();
        if (strpos($url, 'insuranceprovider')) $this->genericModel->modelType = 'InsuranceProvider';
        if (strpos($url, 'carplan')) $this->genericModel->modelType = 'CarPlan';
        if (strpos($url, 'carplancoverage')) $this->genericModel->modelType = 'CarPlanCoverage';
        if (strpos($url, 'carplanaddon')) $this->genericModel->modelType = 'CarPlanAddOn';
        if (strpos($url, 'carplanaddonoption')) $this->genericModel->modelType = 'CarPlanAddOnOption';
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;
        if ($modelType == null) $modelType = $request->get('modelType');
        $serviceType = lcfirst(ucwords($modelType)) . 'Service';
        $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
        $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
        $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
    }
}
