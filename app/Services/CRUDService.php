<?php

namespace App\Services;

use App\Models\CarQuote;
use App\Models\GenericModel;
use App\Models\HealthQuote;
use App\Models\LeadStatus;
use App\Models\LifeQuote;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Services\TeamService;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;

class CRUDService extends BaseService
{
    protected $healthQuoteService;
    protected $carQuoteService;
    protected $teamService;
    protected $request;
    protected $leadStatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    public function __construct(HealthQuoteService $healthQuoteService, TeamService $teamService, CarQuoteService $carQuoteService,
    LeadStatusService $leadStatusService, TravelQuoteService $travelQuoteService, LifeQuoteService $lifeQuoteService, HomeQuoteService $homeQuoteService)
    {
        $this->healthQuoteService = $healthQuoteService;
        $this->teamService = $teamService;
        $this->carQuoteService = $carQuoteService;
        $this->leadStatusService = $leadStatusService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->travelQuoteService = $travelQuoteService;
        $this->homeQuoteService = $homeQuoteService;
    }

	public function getGridData(GenericModel $model, Request $request){
        $gridData = '';
        switch ($model->modelType) {
            case 'Car':
                $gridData = CarQuote::select('*');
                if ($request->ajax()) {
                    foreach ($model->searchProperties as $item) {
                        if(!empty($request[$item])){
                            $gridData = $gridData->where($item, '=', $request[$item]);
                        }
                    }
                }
                break;
            case 'Health':
                $gridData = $this->healthQuoteService->getGridData($model->searchProperties, $request);
                break;
            case 'Travel':
                $gridData = $this->travelQuoteService->getGridData($model->searchProperties, $request);
                break;
            case 'Life':
                $gridData = $this->lifeQuoteService->getGridData($model->searchProperties, $request);
                break;
            case 'Home':
                $gridData = $this->homeQuoteService->getGridData($model->searchProperties, $request);
                break;
            case 'Teams':
                $gridData = $this->teamService->getGridData();
                break;
            case 'LeadStatus':
                $gridData = LeadStatus::select('*');
                if ($request->ajax()) {
                    foreach ($model->searchProperties as $item) {
                        if(!empty($request[$item])){
                            $gridData = $gridData->where($item, '=', $request[$item]);
                        }
                    }
                }
                break;
            default:
                break;
        }
        return $gridData;
    }


    public function getCustomTitleByModelType($modelType, $propertyName){
        $title = '';
        switch ($modelType) {
            case 'Car':
                $title = $this->carQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Health':
                $title = $this->healthQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Travel':
                $title = $this->travelQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Life':
                $title = $this->lifeQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Home':
                $title = $this->homeQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Teams':
                $title = $this->teamService->getCustomTitleByProperty($propertyName);
                break;
            case 'LeadStatus':
                $title = $this->leadStatusService->getCustomTitleByProperty($propertyName);
                break;
            default:
                break;
        }
        return $title;
    }

    public function saveModelByType($modelType, Request $request){
        switch ($modelType) {
            case 'Car':
                $this->carQuoteService->saveCarQuote($request);
                break;
            case 'Health':
                $this->healthQuoteService->saveHealthQuote($request);
                break;
            case 'Travel':
                $this->travelQuoteService->saveTravelQuote($request);
                break;
            case 'Teams':
                $this->teamService->saveTeam($request);
                break;
            case 'Life':
                $this->lifeQuoteService->saveLifeQuote($request);
                break;
            case 'Home':
                $this->homeQuoteService->saveHomeQuote($request);
                break;
            case 'LeadStatus':
                $this->leadStatusService->saveLeadStatus($request);
                break;
            default:
                break;
        }
    }

    public function updateModelByType($modelType, Request $request, $id){
        switch ($modelType) {
            case 'Car':
                $this->carQuoteService->updateCarQuote($request, $id);
                break;
            case 'Health':
                $this->healthQuoteService->updateHealthQuote($request, $id);
                break;
            case 'Travel':
                $this->travelQuoteService->updateTravelQuote($request, $id);
                break;
            case 'Teams':
                $this->teamService->updateTeam($request, $id);
                break;
            case 'Life':
                $this->lifeQuoteService->updateLifeQuote($request, $id);
                break;
            case 'Home':
                $this->homeQuoteService->updateHomeQuote($request, $id);
                break;
            case 'LeadStatus':
                $this->leadStatusService->updateLeadStatus($request, $id);
                break;
            default:
                break;
        }
    }

    public function getEntity($modelType, $id)
    {
        $data = '';
        switch ($modelType) {
            case 'Car':
                $data = CarQuote::find($id);
                break;
            case 'Health':
                $data = $this->healthQuoteService->getEntity($id);
                break;
            case 'Travel':
                $data = $this->travelQuoteService->getEntity($id);
                break;
            case 'Life':
                $data = $this->lifeQuoteService->getEntity($id);
                break;
            case 'Home':
                $data = $this->homeQuoteService->getEntity($id);
                break;
            case 'Teams':
                $data = Team::find($id);
                break;
            case 'LeadStatus':
                $data = LeadStatus::find($id);
                break;
            default:
                break;
        }
        return $data;
    }


}
