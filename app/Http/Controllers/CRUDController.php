<?php

namespace App\Http\Controllers;

use App\Models\GenericModel;
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
use DataTables;
class CRUDController extends Controller
{
    protected $genericModel;
    protected $healthQuoteService;
    protected $teamService;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    protected $leadStatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    public function __construct(Request $request, HealthQuoteService $healthService, TeamService $teamService, CRUDService $crudService, DropdownSourceService $dropdownSourceService,
    CarQuoteService $carQuoteService, LeadStatusService $leadStatusService, TravelQuoteService $travelQuoteService, LifeQuoteService $lifeQuoteService, HomeQuoteService $homeQuoteService)
    {
        $this->genericModel = new GenericModel();
        $this->healthQuoteService = $healthService;
        $this->teamService = $teamService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->leadStatusService = $leadStatusService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
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
        $gridData = $this->crudService->getGridData($this->genericModel, $request);

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
        $customTableList = [];
        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if(str_contains($value, 'customTable')){
                $customTableList[$property] = $this->dropdownSourceService->getOnlySelectedItemName($property, $id);
            }
        }
        return view('shared.show', compact(['record', 'model', 'customTitles', 'customTableList']));
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
        foreach($model->properties as $property => $value) {
            if(str_contains($value, 'title')){
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if(str_contains($value, 'select')){
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
            if(str_contains($value, 'customTable')){
                $data = $this->dropdownSourceService->getCustomDropdownList($property, $record->id);
                $customLists[$property] = $data;
            }
        }
        #dd($customLists);
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
        if(strpos($request->fullUrl(), 'travel')) $this->genericModel->modelType = 'Travel';
        if(strpos($request->fullUrl(), 'teams')) $this->genericModel->modelType = 'Teams';
        if(strpos($request->fullUrl(), 'car')) $this->genericModel->modelType = 'Car';
        if(strpos($request->fullUrl(), 'life')) $this->genericModel->modelType = 'Life';
        if(strpos($request->fullUrl(), 'home')) $this->genericModel->modelType = 'Home';
        if(strpos($request->fullUrl(), 'leadstatus')) $this->genericModel->modelType = 'LeadStatus';
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
            case 'Travel':
                $this->genericModel->properties = $this->travelQuoteService->fillModelProperties();
                $this->genericModel->skipProperties = $this->travelQuoteService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->travelQuoteService->fillModelSearchProperties();
                break;
            case 'Life':
                $this->genericModel->properties = $this->lifeQuoteService->fillModelProperties();
                $this->genericModel->skipProperties = $this->lifeQuoteService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->lifeQuoteService->fillModelSearchProperties();
                break;
            case 'Home':
                $this->genericModel->properties = $this->homeQuoteService->fillModelProperties();
                $this->genericModel->skipProperties = $this->homeQuoteService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->homeQuoteService->fillModelSearchProperties();
                break;
            case 'Teams':
                $this->genericModel->properties = $this->teamService->fillModelProperties();
                $this->genericModel->skipProperties = $this->teamService->fillModelSkipProperties();
                break;
            case 'LeadStatus':
                $this->genericModel->properties = $this->leadStatusService->fillModelProperties();
                $this->genericModel->skipProperties = $this->leadStatusService->fillModelSkipProperties();
                $this->genericModel->searchProperties = $this->leadStatusService->fillModelSearchProperties();
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

}
