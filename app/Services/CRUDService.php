<?php

namespace App\Services;

use App\Models\GenericModel;
use App\Models\HealthQuote;
use App\Models\LifeQuote;
use App\Models\Team;
use App\Services\TeamService;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;

class CRUDService extends BaseService
{
    protected $healthQuoteService;
    protected $teamService;
    protected $request;
    public function __construct()
    {
        $this->healthQuoteService = new HealthQuoteService();
        $this->teamService = new TeamService();
    }

	public function getGridData(GenericModel $model){
        $data = '';
        switch ($model->modelType) {
            case 'Life':
                $data = LifeQuote::select('*');
                break;
            case 'Health':
                $data = HealthQuote::select('*')->orderBy('created_at','desc');
                break;
            case 'Teams':
                $data = Team::select('*')->get();
                break;
            default:
                break;
        }
        return $data;
    }


    public function getCustomTitleByModelType($modelType, $propertyName){
        $title = '';
        switch ($modelType) {
            case 'Life':
                break;
            case 'Health':
                $title = $this->healthQuoteService->getCustomTitleByProperty($propertyName);
                break;
            case 'Teams':
                $title = $this->teamService->getCustomTitleByProperty($propertyName);
                break;
            default:
                break;
        }
        return $title;
    }

    public function saveModelByType($modelType, Request $request){
        switch ($modelType) {
            case 'Life':
                LifeQuoteService::saveLifeQuote($request);
                break;
            case 'Health':
                $this->healthQuoteService->saveHealthQuote($request);
                break;
            case 'Teams':
                $this->teamService->saveTeam($request);
                break;
            default:
                break;
        }
    }

    public function updateModelByType($modelType, Request $request, $id){
        switch ($modelType) {
            case 'Life':
                LifeQuoteService::saveLifeQuote($request);
                break;
            case 'Health':
                $this->healthQuoteService->updateHealthQuote($request, $id);
                break;
            case 'Teams':
                $this->teamService->updateTeam($request, $id);
                break;
            default:
                break;
        }
    }

    public function getEntity($modelType, $id)
    {
        $data = '';
        switch ($modelType) {
            case 'Life':
                $data = LifeQuote::find($id);
                break;
            case 'Health':
                $data = HealthQuote::find($id);
                break;
            case 'Teams':
                $data = Team::find($id);
                break;
            default:
                break;
        }
        return $data;
    }


}
