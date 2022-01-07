<?php

namespace App\Services;

use App\Models\GenericModel;
use App\Models\User;
use App\Services\TeamService;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;
use DB;

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
    public function __construct(
        HealthQuoteService $healthQuoteService,
        TeamService $teamsService,
        CarQuoteService $carQuoteService,
        LeadStatusService $leadstatusService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService
    ) {
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
    public function getGridData(GenericModel $model, Request $request)
    {
        $lowerCaseModelType = strtolower($model->modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getGridData($model->searchProperties, $request);
    }

    public function getLeads($CDBID, $email, $mobile_no, $leadType)
    {
        return $this->{$leadType . 'QuoteService'}->getLeads($CDBID, $email, $mobile_no, $leadType);
    }

    public function getLeadAssignmentRecords($teamName)
    {
        return $this->{$teamName . 'QuoteService'}->getLeadsForAssignment();
    }

    public function getAdvisorLeads($request, $leadType)
    {
        return $this->{$leadType . 'QuoteService'}->{'get' . ucwords($leadType) . 'LeadsForAdvisor'}($request);
    }

    public function getCustomTitleByModelType($modelType, $propertyName)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getCustomTitleByProperty($propertyName);
    }

    public function getAdvisorsByModelType($modelType)
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        if (strtolower($modelType) == 'car') {
            $query->whereIn('r.name', [strtoupper($modelType) . '_ADVISOR', 'advisor']);
        } else if (strtolower($modelType) == 'health') {
            $query->whereIn('r.name', [strtoupper($modelType) . '_ADVISOR', 'WCU_ADVISOR']);
        } else {
            $query->where('r.name', strtoupper($modelType) . '_ADVISOR');
        }
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getEBPAndRMAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getRMAndBusinessAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'BUSINESS_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function saveModelByType($modelType, Request $request)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->{in_array($lowerCaseModelType, $this->quoteTypes) ? 'save' . ucwords($modelType) . 'Quote' : 'save' . ucwords($modelType)}($request);
    }

    public function updateModelByType($modelType, Request $request, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->{in_array($lowerCaseModelType, $this->quoteTypes) ? 'update' . ucwords($modelType) . 'Quote' : 'update' . ucwords($modelType)}($request, $id);
    }

    public function getEntity($modelType, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getEntity($id);
    }
}
