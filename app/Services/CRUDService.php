<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\GenericModel;
use App\Models\QuoteStatusLog;
use App\Models\User;
use App\Traits\CreateUpdateSIbContact;
use App\Traits\GenericQueriesAllLobs;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CRUDService extends BaseService
{
    use GenericQueriesAllLobs;
    use CreateUpdateSIbContact;

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
        $this->quoteTypes = ['home', 'health', 'life', 'business', 'travel', 'car', 'pet'];
    }

    public function getGridData(GenericModel $model, Request $request)
    {
        $lowerCaseModelType = strtolower($model->modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->getGridData($model, $request);
    }

    public function getLeads($CDBID, $email, $mobile_no, $leadType)
    {
        return $this->{$leadType.'QuoteService'}->getLeads($CDBID, $email, $mobile_no, $leadType);
    }

    public function getLeadAssignmentRecords($teamName)
    {
        return $this->{$teamName.'QuoteService'}->getLeadsForAssignment();
    }

    public function getAdvisorLeads($request, $leadType)
    {
        return $this->{strtolower($leadType).'QuoteService'}->{'get'.ucwords($leadType).'LeadsForAdvisor'}($request);
    }

    public function getOverDueFollowups($request, $leadType)
    {
        return $this->{strtolower($leadType).'QuoteService'}->{'get'.ucwords($leadType).'OverDueFollowups'}($request);
    }

    public function getEntityByUUID($uuid, $leadType)
    {
        return $this->{strtolower($leadType).'QuoteService'}->getEntity($uuid, $leadType);
    }

    public function getCustomTitleByModelType($modelType, $propertyName)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->getCustomTitleByProperty($propertyName);
    }

    public function getAllowedDuplicateLOB($modelType, $leadCode)
    {
        $allowedLeadTypes = ['Home', 'Health', 'Life', 'Corpline', 'Group Medical', 'Travel', 'Car', 'Pet'];
        if (strtolower($modelType) == 'business') {
            $modelType = 'Corpline';
        }
        $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
            return $item;
        });
        foreach ($allowedLeadTypes as $leadType) {
            $leadType = strtolower($leadType);
            if ($leadType == strtolower(quoteTypeCode::CORPLINE) || $leadType = strtolower(quoteTypeCode::GroupMedical)) {
                $leadType = 'Business';
            }
            $duplicateRecord = $this->{strtolower($leadType).'QuoteService'}->getDuplicateEntityByCode($leadCode);
            if ($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
                    return $item;
                });
            }
        }

        return $allowedLeadTypes;
    }

    public function createDuplicate(Request $request)
    {
        $lobTeams = $request->lob_team;
        $parentType = $request->parentType;

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = 'Business';
        }
        $parentRecord = $this->{strtolower($request->parentType).'QuoteService'}->getEntityPlain($request->entityId);
        if ($request->has('lob_team_sub_selection') && isset($request->lob_team_sub_selection)) {
            $parentRecord['enquiryType'] = $request->lob_team_sub_selection;
        } else {
            $parentRecord['enquiryType'] = 'record_only';
        }

        if (! empty($lobTeams)) {
            foreach ($lobTeams as $lobTeam) {
                $this->createDuplicateRecord($lobTeam, $parentRecord);
            }
        }
    }

    public function getLeadAuditHistory($leadType, $leadId)
    {
        $leadType = ucwords($leadType);
        $audits = DB::table('audits as a')
            ->select(
                'a.created_at as ModifiedAt',
                DB::raw('(SELECT name from users where id = a.user_id) as ModifiedBy'),
                DB::raw("(SELECT TEXT FROM quote_status WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))) AS NewStatus"),
                DB::raw("(SELECT NAME FROM users WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.advisor_id'))) AS NewAdvisor"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.notes')) AS NewNotes")
            )
            ->where(function ($query) {
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
            })
            ->where(function ($query) use ($leadId, $leadType) {
                $query->where('a.auditable_type', 'App\Models\\'.$leadType.'Quote')
                    ->where('a.auditable_id', $leadId);
            })
            ->orWhere(function ($query) use ($leadType, $leadId) {
                $entityDetail = $this->{strtolower($leadType).'QuoteService'}->getDetailEntity($leadId);
                if ($entityDetail) {
                    $query->where('a.auditable_id', $entityDetail->id)
                    ->where('a.auditable_type', 'App\Models\\'.$leadType.'QuoteRequestDetail');
                }
            })
            ->orderBy('a.created_at', 'DESC')->get();

        return $audits;
    }

    public function updateQuoteStatus(Request $request)
    {
        $quoteDetailEntity = $this->{strtolower($request->modelType).'QuoteService'}->getDetailEntity($request->leadId);

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
        if (isset($request->lost_approval_status) && $request->lost_approval_status != '' && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
            $quoteDetailEntity->lost_approval_status = $request->lost_approval_status;
        }
        if (isset($request->lost_approval_reason) && $request->lost_approval_reason != '' && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
            $quoteDetailEntity->lost_approval_reason = $request->lost_approval_reason;
        }

        $quoteDetailEntity->save();

        $entity = $this->{strtolower($request->modelType).'QuoteService'}->getEntityPlain($request->leadId);
        $previousQuoteStatus = $entity->quote_status_id;
        $entity->quote_status_id = $request->leadStatus;
        if ($request->leadStatus == QuoteStatusEnum::Qualified && Auth::user()->isHealthWcuAdvisor()) {
            $entity->wcu_id = null;
        }
        $entity->save();
        if (strtolower($request->modelType) == strtolower(quoteTypeCode::Health) && $request->leadStatus == QuoteStatusEnum::Quoted) {
            $this->sendSibRequest($entity);
        }
        QuoteStatusLog::create([
            'quote_type_id' => QuoteTypeId::Car,
            'quote_request_id' => $entity->id,
            'current_quote_status_id' => $request->leadStatus,
            'previous_quote_status_id' => $previousQuoteStatus,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $entity;
    }

    public function getAdvisorsByModelType($modelType)
    {
        $query = User::join('model_has_roles as mr', 'mr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('users.id', DB::raw("CONCAT(users.name,' - ',r.name) AS name"));
        if (strtolower($modelType) == strtolower(quoteTypeCode::Car)) {
            $query->whereIn('r.name', [RolesEnum::CarAdvisor, RolesEnum::Advisor]);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Health)) {
            $query->whereIn('r.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor, RolesEnum::HealthWCUAdvisor]);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', [RolesEnum::CorpLineAdvisor, RolesEnum::CorpLineRenewalAdvisor, RolesEnum::CorpLineNewBusinessAdvisor, RolesEnum::GMRenewalAdvisor, RolesEnum::GMNewBusinessAdvisor]);
        } else {
            $query->whereIn('r.name', [strtoupper($modelType).'_ADVISOR', strtoupper($modelType).'_RENEWAL_ADVISOR', strtoupper($modelType).'_NEW_BUSINESS_ADVISOR']);
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
            $query->whereIn('r.name', [strtoupper($modelType).'_RENEWAL_ADVISOR', 'advisor']);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Health)) {
            $query->whereIn('r.name', [strtoupper($modelType).'_WCU_ADVISOR', 'RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_RENEWAL_ADVISOR']);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', ['CORPLINE_ADVISOR', 'CORPLINE_RENEWAL_ADVISOR']);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Life) || strtolower($modelType) == strtolower(quoteTypeCode::Home) || strtolower($modelType) == strtolower(quoteTypeCode::Travel) || strtolower($modelType) == strtolower(quoteTypeCode::Pet)) {
            $query->whereIn('r.name', [strtoupper($modelType).'_RENEWAL_ADVISOR', 'advisor']);
        } else {
            $query->where('r.name', strtoupper($modelType).'_ADVISOR');
        }

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getNewBusinessAdvisorsByModelType($modelType)
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));
        $query->where('r.name', strtoupper($modelType).'_NEW_BUSINESS_ADVISOR');

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getEBPAndRMAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_WCU_ADVISOR', 'HEALTH_NEW_BUSINESS_ADVISOR', 'HEALTH_RENEWAL_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getRMAndBusinessAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'BUSINESS_ADVISOR', 'AMT_ADVISOR', 'HEALTH_NEW_BUSINESS_ADVISOR', 'HEALTH_RENEWAL_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function saveModelByType($modelType, Request $request)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->{in_array($lowerCaseModelType, $this->quoteTypes) ? 'save'.ucwords($modelType).'Quote' : 'save'.ucwords($modelType)}($request);
    }

    public function updateModelByType($modelType, Request $request, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->{in_array($lowerCaseModelType, $this->quoteTypes) ? 'update'.ucwords($modelType).'Quote' : 'update'.ucwords($modelType)}($request, $id);
    }

    public function getEntity($modelType, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->getEntity($id);
    }

    public function fillRenewalData($model)
    {
        $lowerCaseModelType = strtolower($model->modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->fillRenewalProperties($model);
    }

    public function fillNewBusinessData($model)
    {
        $lowerCaseModelType = strtolower($model->modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->fillNewBusinessProperties($model);
    }

    public function getSelectedLostReason($modelType, $id)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->getSelectedLostReason($id);
    }

    public function getLeadPlainEntityByUUID($modelType, $uuid)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->getEntityPlainByUUID($uuid);
    }

    public function validateRequest($modelType, $request)
    {
        $lowerCaseModelType = strtolower($modelType);

        return $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->validateRequest($request);
    }

    public function quoteModel($quoteType, $quoteUuId)
    {
        $model = '\\App\\Models\\'.ucwords($quoteType).'Quote';

        return $model::where('uuid', $quoteUuId)->first();
    }

    public function updateQuoteStatusbyModel($model, $status)
    {
        $model->quote_status_id = $status;
        $model->save();

        return $model;
    }
}
