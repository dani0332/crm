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
use App\Services\PetQuoteService;
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
    protected $petQuoteService;
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
        PetQuoteService $petQuoteService,
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
        $this->petQuoteService = $petQuoteService;
        $this->insuranceproviderService = $insuranceproviderService;
        $this->carplanService = $carplanService;
        $this->carplancoverageService = $carplancoverageService;
        $this->carplanaddonService = $carplanaddonService;
        $this->carplanaddonoptionService = $carplanaddonoptionService;
        $this->applicationstorageService = $applicationstorageService;
        $this->quoteTypes = ['home', 'health', 'life', 'business', 'travel', 'car','pet'];
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

    public function getEntityByUUID($uuid, $leadType)
    {
        return $this->{strtolower($leadType) . 'QuoteService'}->getEntity($uuid, $leadType);
    }

    public function getCustomTitleByModelType($modelType, $propertyName)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getCustomTitleByProperty($propertyName);
    }

    public function getAllowedDuplicateLOB($modelType, $leadCode)
    {
        $allowedLeadTypes = ['Home', 'Health', 'Life', 'Corpline', 'Group Medical', 'Travel', 'Car', 'Pet'];
        if(strtolower($modelType) == 'business') {
            $modelType = 'Corpline';
        }
        $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) use ($modelType) {
            if (strtolower($item) != strtolower($modelType)) {
                return $item;
            }
        });
        foreach ($allowedLeadTypes as $leadType) {
            if($leadType == 'Corpline' || $leadType = 'Group Medical'){
                $leadType = 'Business';
            }
            $duplicateRecord =  $this->{strtolower($leadType) . 'QuoteService'}->getDuplicateEntityByCode($leadCode);
            if($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) use ($leadType) {
                    if ($item != $leadType) {
                        return $item;
                    }
                });
            }
        }
        return $allowedLeadTypes;
    }

    public function createDuplicate(Request $request)
    {
        $lob_teams = $request->lob_team;
        $parentRecord = $this->{strtolower($request->parentType) . 'QuoteService'}->getEntityPlain($request->entityId);
        if(!empty($lob_teams)) {
            foreach ($lob_teams as $lob_team) {
                $this->{strtolower($lob_team) . 'QuoteService'}->createDuplicate($parentRecord);
            }
        }
    }

    public function getLeadAuditHistory($leadType, $leadId)
    {
        return $this->{strtolower($leadType) . 'QuoteService'}->getLeadAuditHistory($leadId);
    }

    public function updateQuoteStatus(Request $request)
    {
       
        $quoteDetailEntity = $this->{strtolower($request->modelType) . 'QuoteService'}->getDetailEntity($request->leadId);
       
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

        $entity = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($request->leadId);
        $previousQuoteStatus = $entity->quote_status_id;
        $entity->quote_status_id = $request->leadStatus;
        
        $entity->save();
        QuoteStatusLog::create(array(
            'quote_type_id' => QuoteTypeId::Car,
            'quote_request_id' => $entity->id,
            'current_quote_status_id' => $request->leadStatus,
            'previous_quote_status_id' => $previousQuoteStatus,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ));
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
            $query->whereIn('r.name', [strtoupper($modelType) . '_WCU_ADVISOR', 'RM_ADVISOR', 'EBP_ADVISOR','HEALTH_RENEWAL_ADVISOR']);
        } else if (strtolower($modelType) ==  strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', ['CORPLINE_ADVISOR','CORPLINE_RENEWAL_ADVISOR']);
        }else if (strtolower($modelType) == strtolower(quoteTypeCode::Life) || strtolower($modelType) == strtolower(quoteTypeCode::Home) || strtolower($modelType) == strtolower(quoteTypeCode::Travel)) {
            $query->whereIn('r.name', [strtoupper($modelType) . '_RENEWAL_ADVISOR', 'advisor']);
        } else {
            $query->where('r.name', strtoupper($modelType) . '_ADVISOR');
        }
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getNewBusinessAdvisorsByModelType($modelType)
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
            $query->where('r.name', strtoupper($modelType) . '_NEW_BUSINESS_ADVISOR');
        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getEBPAndRMAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_WCU_ADVISOR'])
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

    public function fillNewBusinessData($model)
    {
        $lowerCaseModelType = strtolower($model->modelType);
        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->fillNewBusinessProperties($model);
    }

    public function getSelectedLostReason($modelType, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType . 'QuoteService' : $lowerCaseModelType . 'Service'}
            ->getSelectedLostReason($id);
    }
}
