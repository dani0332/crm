<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuoteRequestDetail;
use App\Models\GenericModel;
use App\Models\QuoteStatusLog;
use App\Models\User;
use App\Services\TeamService;
use App\Services\HealthQuoteService;
use App\Services\InsuranceProviderService;
use App\Services\CarPlanService;
use App\Services\CarPlanCoverageService;
use App\Services\CarPlanAddonService;
use App\Services\CarPlanAddOnOptionService;
use App\Services\ApplicationStorageService;
use Illuminate\Http\Request;
use DB;
use \Carbon\Carbon;

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
    protected $insuranceproviderService;
    protected $carplancoverageService;
    protected $carplanService;
    protected $carplanaddonService;
    protected $carplanaddonoptionService;
    protected $applicationstorageService;
    public function __construct(
        HealthQuoteService $healthQuoteService,
        TeamService $teamsService,
        CarQuoteService $carQuoteService,
        LeadStatusService $leadstatusService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService,
        InsuranceProviderService $insuranceproviderService,
        CarPlanService $carplanService,
        CarPlanCoverageService $carplancoverageService,
        CarPlanAddonService $carplanaddonService,
        CarPlanAddOnOptionService $carplanaddonoptionService,
        ApplicationStorageService $applicationstorageService
    ) {
        $this->healthQuoteService = $healthQuoteService;
        $this->carQuoteService = $carQuoteService;
        $this->teamsService = $teamsService;
        $this->leadstatusService = $leadstatusService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->insuranceproviderService = $insuranceproviderService;
        $this->carplanService = $carplanService;
        $this->carplancoverageService = $carplancoverageService;
        $this->carplanaddonService = $carplanaddonService;
        $this->carplanaddonoptionService = $carplanaddonoptionService;
        $this->applicationstorageService = $applicationstorageService;
        $this->quoteTypes = ['home', 'health', 'life', 'business', 'travel', 'car'];
    }
    public function getGridData(GenericModel $model, Request $request)
    {
        $lowerCaseModelType = strtolower($model->modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getGridData($model, $request);
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
        return $this->{strtolower($leadType) . 'QuoteService'}->{'get' . ucwords($leadType) . 'LeadsForAdvisor'}($request);
    }

    public function getOverDueFollowups($request, $leadType)
    {
        return $this->{strtolower($leadType) . 'QuoteService'}->{'get' . ucwords($leadType) . 'OverDueFollowups'}($request);
    }

    public function getCustomTitleByModelType($modelType, $propertyName)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getCustomTitleByProperty($propertyName);
    }
    public function updateQuoteStatus(Request $request)
    {
        $entity = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($request->leadId);
        $quoteDetailEntity = $this->{strtolower($request->modelType) . 'QuoteService'}->getDetailEntity($request->leadId);
        $entity->quote_status_id = $request->leadStatus;
        if (isset($request->lostReason) && $request->lostReason != '') {
            $quoteDetailEntity->lost_reason_id = $request->lostReason;
        }
        if (isset($request->trans_code) && $request->trans_code != '') {
            $quoteDetailEntity->transapp_code = $request->trans_code;
        }
        if (isset($request->notes) && $request->notes != '') {
            $quoteDetailEntity->notes = $request->notes;
        }
        if (isset($request->nextFollowUpDate) && $request->nextFollowUpDate != '') {
            $quoteDetailEntity->next_followup_date = $request->nextFollowUpDate;
        }

        $quoteDetailEntity->save();
        $entity->save();
        QuoteStatusLog::create([
            'quote_type_id' => QuoteTypeId::Car,
            'quote_request_id' => $entity->id,
            'current_quote_status_id' => $request->leadStatus,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        return $entity;
    }

    public function getAdvisorsByModelType($modelType)
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        if (strtolower($modelType) == strtolower(quoteTypeCode::Car)) {
            $query->whereIn('r.name', [strtoupper($modelType) . '_ADVISOR', 'advisor']);
        } else if (strtolower($modelType) ==  strtolower(quoteTypeCode::Health)) {
            $query->whereIn('r.name', [strtoupper($modelType) . '_WCU_ADVISOR', 'RM_ADVISOR', 'EBP_ADVISOR']);
        } else if (strtolower($modelType) ==  strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', ['CORPLINE_ADVISOR']);
        } else {
            $query->where('r.name', strtoupper($modelType) . '_ADVISOR');
        }
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getRenewalAdvisorsByModelType($modelType)
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        if (strtolower($modelType) == strtolower(quoteTypeCode::Car)) {
            $query->whereIn('r.name', [strtoupper($modelType) . '_RENEWAL_ADVISOR', 'advisor']);
        } else if (strtolower($modelType) ==  strtolower(quoteTypeCode::Health)) {
            $query->whereIn('r.name', [strtoupper($modelType) . '_WCU_ADVISOR', 'RM_ADVISOR', 'EBP_ADVISOR']);
        } else if (strtolower($modelType) ==  strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', ['CORPLINE_ADVISOR']);
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
            ->whereIn('r.name', ['RM_ADVISOR', 'BUSINESS_ADVISOR', 'AMT_ADVISOR'])
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

    public function fillRenewalData($model)
    {
        $lowerCaseModelType = strtolower($model->modelType);
        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->fillRenewalProperties($model);
    }

    public function getSelectedLostReason($modelType, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getSelectedLostReason($id);
    }
}
