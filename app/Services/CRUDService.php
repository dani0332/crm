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
    public function __construct(HealthQuoteService $healthQuoteService, TeamService $teamService, CarQuoteService $carQuoteService,
    LeadStatusService $leadStatusService, TravelQuoteService $travelQuoteService)
    {
        $this->healthQuoteService = $healthQuoteService;
        $this->teamService = $teamService;
        $this->carQuoteService = $carQuoteService;
        $this->leadStatusService = $leadStatusService;
        $this->travelQuoteService = $travelQuoteService;
    }

	public function getGridData(GenericModel $model){
        $data = '';
        switch ($model->modelType) {
            case 'Car':
                $data = CarQuote::select('*');
                break;
            case 'Health':
                $data = $this->healthQuoteService->getGridData();
                break;
            case 'Travel':
                $data = $this->travelQuoteService->getGridData();
                break;
            case 'Teams':
                $data = $this->teamService->getGridData();
                break;
            case 'LeadStatus':
                $data = LeadStatus::select('*')->get();
                break;
            default:
                break;
        }
        return $data;
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
                $data = HealthQuote::find($id);
                break;
            case 'Travel':
                $data = TravelQuote::find($id);
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
