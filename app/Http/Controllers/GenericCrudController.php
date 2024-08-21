<?php

namespace App\Http\Controllers;

use App\Enums\GenericModelTypeEnum;
use App\Enums\InsuranceProvderConstants;
use App\Enums\PermissionsEnum;
use App\Models\GenericModel;
use App\Services\ApplicationStorageService;
use App\Services\CarPlanAddOnOptionService;
use App\Services\CarPlanAddOnService;
use App\Services\CarPlanCoverageService;
use App\Services\CarPlanService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\InsuranceProviderService;
use App\Services\LeadStatusService;
use App\Services\QuadrantService;
use App\Services\RuleService;
use App\Services\TeamService;
use App\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GenericCrudController extends Controller
{
    protected $genericModel;
    protected $insuranceProviderService;
    protected $carPlanService;
    protected $dropdownSourceService;
    protected $carPlanAddOnService;
    protected $carPlanCoverageService;
    protected $carPlanAddOnOptionService;
    protected $applicationStorageService;
    protected $teamsService;
    protected $leadStatusService;
    protected $tierService;
    protected $quadrantService;
    protected $ruleService;
    protected $crudService;

    public function __construct(
        InsuranceProviderService $insuranceProviderService,
        CRUDService $crudService,
        CarPlanService $carPlanService,
        DropdownSourceService $dropdownSourceService,
        Request $request,
        CarPlanAddOnService $carPlanAddOnService,
        CarPlanCoverageService $carPlanCoverageService,
        CarPlanAddOnOptionService $carPlanAddOnOptionService,
        ApplicationStorageService $applicationStorageService,
        TeamService $teamsService,
        LeadStatusService $leadStatusService,
        TierService $tierService,
        QuadrantService $quadrantService,
        RuleService $ruleService
    ) {
        $this->middleware(
            ['permission:'.PermissionsEnum::TIER_CONFIG_LIST.'|'.PermissionsEnum::QUAD_CONFIG_LIST.'|'.PermissionsEnum::RULE_CONFIG_LIST],
            ['only' => ['index', 'create', 'store', 'edit', 'update', 'show']]
        );

        $this->genericModel = new GenericModel;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->insuranceProviderService = $insuranceProviderService;
        $this->carPlanService = $carPlanService;
        $this->carPlanAddOnService = $carPlanAddOnService;
        $this->carPlanCoverageService = $carPlanCoverageService;
        $this->carPlanAddOnOptionService = $carPlanAddOnOptionService;
        $this->applicationStorageService = $applicationStorageService;
        $this->teamsService = $teamsService;
        $this->leadStatusService = $leadStatusService;
        $this->tierService = $tierService;
        $this->quadrantService = $quadrantService;
        $this->ruleService = $ruleService;
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
        $tiers = $gridData->simplePaginate(10)->withQueryString();
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

        return inertia('Admin/AllocationConfig/Tiers/Index', [
            'model' => $model,
            'tiers' => $tiers,
            'dropdownSource' => $dropdownSource,
            'customTitles' => $customTitles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $id = '';
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
        if ($request->has('id')) {
            $id = $request->id;
        }

        return view('generic.add', compact('model', 'dropdownSource', 'customTitles', 'id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelPropertiesList = json_decode($request->get('model'), true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $modelType = json_decode($request->get('modelType'), true);
        $validateArray = [];
        foreach ($modelPropertiesList as $property => $value) {
            if (strpos($value, 'required') && $property != 'id' && ! strpos($modelSkipPropertiesList['create'], $property)) {
                if (strpos($value, 'min')) {
                    $min = explode(':', $value)[1];
                    $validateArray[$property] = 'required|numeric|min:'.$min;
                } elseif (strpos($value, 'required_if')) {
                    $without = explode(':', $value)[1];
                    $validateArray[$property] = 'required_if:'.$without;
                } else {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $this->validate($request, $validateArray);
        $record = $this->crudService->saveModelByType($modelType, $request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (isset($request->return_to_view)) {
                return redirect('/generic/'.strtolower($modelType).'/create')->with('success', $modelType.' has been stored');
            }

            return redirect('/generic/'.strtolower($modelType).'/'.$record->id)->with('success', $modelType.' has been stored');
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
        if (! $record) {
            abort(404);
        }
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
        $serviceType = $model->modelType.'Service';
        if ($this->genericModel->modelType == InsuranceProvderConstants::NAME) {
            $plansList = $this->carPlanService->getProvderPlans($id);

            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'plansList', 'customTableList',
            ]));
        }
        if ($this->genericModel->modelType == InsuranceProvderConstants::PLANNAME) {
            $coverageList = $this->carPlanCoverageService->getPlanCoverage($id);
            $plansList = $this->carPlanAddOnService->getPlanAddon($id);

            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'coverageList', 'customTableList', 'plansList',
            ]));
        }
        if ($this->genericModel->modelType == InsuranceProvderConstants::PLANADDON) {
            $plansList = $this->carPlanAddOnOptionService->getPlanAddonOption($record->addon_id);

            return view('generic.show', compact([
                'record', 'model', 'customTitles', 'plansList', 'customTableList',
            ]));
        }

        return view('generic.show', compact(['record', 'model', 'customTitles', 'customTableList']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response klm[jo]
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
                $data = $this->dropdownSourceService->getCustomDropdownList($property, $record->id);
                $customLists[$property] = $data;
            }
        }

        return view('generic.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists']));
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
        $modelType = json_decode($request->all()['modelType'], true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $validateArray = [];
        foreach ($modelPropertiesList as $property => $value) {
            if (str_contains($value, 'min')) {
                $min = explode(':', $value)[1];
                $validateArray[$property] = 'required|numeric|min:'.$min;
            }
            if (str_contains($value, 'max')) {
                $max = explode(':', $value)[1];
                $validateArray[$property] = 'required|numeric|max:'.$max;
            }
            if (strpos($value, 'required_if')) {
                $without = explode(':', $value)[1];
                $validateArray[$property] = 'required_if:'.$without;
            }
            if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && ! strpos($modelSkipPropertiesList, $property)) {
                if (! str_contains($value, 'max') && ! str_contains($value, 'min')) {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        if ($request->has('is_active')) {
            return redirect('/generic/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
        } else {
            return redirect('/generic/'.strtolower(str_replace('"', '', $request->modelType)))->with('success', json_decode($request->modelType, true).' has been updated');
        }
    }

    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();
        $modelType = '';
        switch ($url) {
            case str_contains($url, GenericModelTypeEnum::TEAMS):
                $modelType = 'Team';
                break;
            case str_contains($url, GenericModelTypeEnum::TIER):
                $modelType = 'Tier';
                break;
            case str_contains($url, GenericModelTypeEnum::QUADRANT):
                $modelType = 'Quadrant';
                break;
            case str_contains($url, GenericModelTypeEnum::RULE):
                $modelType = 'Rule';
                break;
            default:
                break;
        }
        $this->genericModel->modelType = $modelType;
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;
        if ($modelType == null) {
            $modelType = $request->get('modelType');
        }
        $serviceType = lcfirst(ucwords($modelType)).'Service';
        $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
        $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
        $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
        if (method_exists($this->{$serviceType}, 'fillSortingProperties')) {
            $this->genericModel->sortProperties = $this->{$serviceType}->fillSortingProperties();
        }
    }
}
