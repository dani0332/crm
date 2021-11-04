<?php

namespace App\Services;

use App\Models\GenericModel;
use App\Models\User;
use App\Services\TeamService;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;

class CRUDService extends BaseService
{
    protected $healthQuoteService;
    protected $carQuoteService;
    protected $teamsService;
    protected $request;
    protected $leadstatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    protected $businessQuoteService;
    protected $quoteTypes;
    public function __construct(HealthQuoteService $healthQuoteService, TeamService $teamsService, CarQuoteService $carQuoteService,
    LeadStatusService $leadstatusService, TravelQuoteService $travelQuoteService, LifeQuoteService $lifeQuoteService, HomeQuoteService $homeQuoteService,
    BusinessQuoteService $businessQuoteService)
    {
        $this->healthQuoteService = $healthQuoteService;
        $this->carQuoteService = $carQuoteService;
        $this->teamsService = $teamsService;
        $this->leadstatusService = $leadstatusService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->quoteTypes = ['home', 'health', 'life', 'business', 'travel', 'car'];
    }
	public function getGridData(GenericModel $model, Request $request){
        return $this->{in_array(strtolower($model->modelType), $this->quoteTypes) ? strtolower($model->modelType).'QuoteService' : strtolower($model->modelType).'Service'}
        ->getGridData($model->searchProperties, $request);
    }


    public function getCustomTitleByModelType($modelType, $propertyName){
        return $this->{in_array(strtolower($modelType), $this->quoteTypes) ? strtolower($modelType).'QuoteService' : strtolower($modelType).'Service'}
        ->getCustomTitleByProperty($propertyName);
    }

    public function getAdvisorsByModelType($modelType){
        return User::select('users.*')
        ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
        ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
        ->whereIn('roles.name', [strtoupper($modelType).'_ADVISOR'])->orderBy('roles.name', 'asc')->get();
    }

    public function saveModelByType($modelType, Request $request){
        $this->{in_array(strtolower($modelType), $this->quoteTypes) ? strtolower($modelType).'QuoteService' : strtolower($modelType).'Service'}
        ->{in_array($modelType, $this->quoteTypes) ? 'save'.ucwords($modelType).'Quote' : 'save'.ucwords($modelType)}($request);
    }

    public function updateModelByType($modelType, Request $request, $id){
        $this->{in_array(strtolower($modelType), $this->quoteTypes) ? strtolower($modelType).'QuoteService' : strtolower($modelType).'Service'}
        ->{in_array(strtolower($modelType), $this->quoteTypes) ? 'update'.ucwords($modelType).'Quote' : 'update'.ucwords($modelType)}($request, $id);
    }

    public function getEntity($modelType, $id){
        return $this->{in_array(strtolower($modelType), $this->quoteTypes) ? strtolower($modelType).'QuoteService' : strtolower($modelType).'Service'}
        ->getEntity($id);
    }
}
