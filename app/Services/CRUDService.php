<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\Kyc;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\Ken;
use App\Facades\Marshall;
use App\Jobs\CammyJob;
use App\Jobs\CarLost\CarLostStatusRejected;
use App\Jobs\IntroEmailJob;
use App\Jobs\SyncSIBContactJob;
use App\Models\CarLostQuoteLog;
use App\Models\GenericModel;
use App\Models\PaymentAction;
use App\Models\QuoteStatusLog;
use App\Models\User;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CRUDService extends BaseService
{
    use GenericQueriesAllLobs, TeamHierarchyTrait;

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
    protected $tierService;
    protected $quadrantService;
    protected $ruleService;

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
        ApplicationStorageService $applicationstorageService,
        TierService $tierService,
        QuadrantService $quadrantService,
        RuleService $ruleService,
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
        $this->tierService = $tierService;
        $this->quadrantService = $quadrantService;
        $this->ruleService = $ruleService;
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
        $allowedLeadTypes = ['Home', 'Health', 'Life', 'CorpLine', 'Group Medical', 'Travel', 'Car', 'Pet'];
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
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y %H:%i:%s") as ModifiedAt'),
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

    public function getLeadHistoryLogs($quoteTypeId, $recordId)
    {
        return QuoteStatusLog::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $recordId)
            ->orderBy('created_at', 'DESC')
            ->with(['currentQuoteStatus', 'createdBy', 'previousQuoteStatus'])
            ->get();
    }

    public function updateQuoteStatus(Request $request)
    {
        return DB::transaction(function () use ($request) {
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
                $quoteDetailEntity->next_followup_date = date('Y-m-d H:i:s', strtotime($request->nextFollowUpDate));
            }
            if (isset($request->lost_approval_status) && $request->lost_approval_status != '' && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                $quoteDetailEntity->lost_approval_status = $request->lost_approval_status;
            }
            if (isset($request->lost_approval_reason) && $request->lost_approval_reason != '' && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                $quoteDetailEntity->lost_approval_reason = $request->lost_approval_reason;
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '' && ($request->leadStatus == QuoteStatusEnum::FollowupCall || $request->leadStatus == QuoteStatusEnum::Interested || $request->leadStatus == QuoteStatusEnum::NoAnswer)) {
                $quoteDetailEntity->next_followup_date = date('Y-m-d H:i:s', strtotime($request->next_followup_date));
            }

            $quoteDetailEntity->save();

            $entity = $this->{strtolower($request->modelType).'QuoteService'}->getEntityPlain($request->leadId);

            $previousQuoteStatus = $entity->quote_status_id;
            //if model is health ,team is ebp ,previous status is quoted and wants to update qualified then restrict advisor
            if (strtolower($request->modelType) == strtolower(quoteTypeCode::Health) && $entity->health_team_type == HealthTeamType::EBP && $previousQuoteStatus == QuoteStatusEnum::Quoted && $request->leadStatus == QuoteStatusEnum::Qualified) {
                $entity->quote_status_id = QuoteStatusEnum::Quoted;
            } else {
                $entity->quote_status_id = $request->leadStatus;
            }
            if ($request->leadStatus == QuoteStatusEnum::Qualified && auth()->user()->isHealthWcuAdvisor()) {
                $entity->wcu_id = null;
            }
            if (isset($request->tier_id) && $request->tier_id != '' && strtolower($request->modelType) == strtolower(quoteTypeCode::Car)) {
                $entity->tier_id = $request->tier_id;
            }

            if (in_array(strtolower($request->modelType), [strtolower(quoteTypeCode::Health), strtolower(quoteTypeCode::Home), strtolower(quoteTypeCode::Business)])) {
                // $entity->activities()->where('status', 0)->update(['status' => 1]);
                $entity->quote_status_date = now();
                if ($entity->stale_at) {
                    $entity->stale_at = null;
                }
            }

            $entity->save();

            if (
                strtolower($request->modelType) == strtolower(quoteTypeCode::Car)
                && $request->leadStatus == QuoteStatusEnum::CarSold || $request->leadStatus == QuoteStatusEnum::Uncontactable
            ) {
                if (! empty($request->car_lost_quote_log_id) && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                    //perform approval or rejection
                    $carLostQuoteLog = CarLostQuoteLog::where([
                        'car_quote_request_id' => $entity->id,
                        'id' => $request->car_lost_quote_log_id,
                    ])->firstOrFail();

                    $lostQuoteLogData = [
                        'status' => $request->lost_approval_status,
                        'quote_status_id' => $request->leadStatus,
                        'reason_id' => ($request->lost_approval_status == GenericRequestEnum::APPROVED) ? $request->approve_reason_id : $request->reject_reason_id,
                        'notes' => $request->lost_notes,
                        'action_by_id' => auth()->user()->id,
                    ];

                    $carLostQuoteLog->update($lostQuoteLogData);

                    if ($request->hasFile('mo_proof_document')) {
                        $fileName = $request->mo_proof_document->getClientOriginalName();

                        $azureFileName = get_guid().'_'.$fileName;
                        $azureFilePath = $request->file('mo_proof_document')
                            ->storeAs('car_proof_docs', $azureFileName, 'azureIM');

                        $carLostQuoteLog->documents()->create([
                            'name' => $fileName,
                            'path' => $azureFilePath,
                            'mime_type' => $request->mo_proof_document->getClientMimeType(),
                            'created_by_id' => auth()->user()->id,
                        ]);
                    }

                    if ($request->lost_approval_status == GenericRequestEnum::REJECTED) {
                        //send rejection email
                        CarLostStatusRejected::dispatch($entity, $carLostQuoteLog);
                    }
                } elseif (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager])) {
                    //store request of car sold/uncontactable with proof
                    $carLostQuoteLog = $entity->carLostQuoteLogs()->create([
                        'advisor_id' => auth()->user()->id,
                        'quote_status_id' => $request->leadStatus,
                        'status' => GenericRequestEnum::PENDING,
                    ]);

                    $fileName = $request->proof_document->getClientOriginalName();

                    $azureFileName = get_guid().'_'.$fileName;
                    $azureFilePath = $request->file('proof_document')
                        ->storeAs('car_proof_docs', $azureFileName, 'azureIM');

                    $carLostQuoteLog->documents()->create([
                        'name' => $fileName,
                        'path' => $azureFilePath,
                        'mime_type' => $request->proof_document->getClientMimeType(),
                        'created_by_id' => auth()->user()->id,
                    ]);
                }
            }

            if (
                strtolower($request->modelType) == strtolower(quoteTypeCode::Health)
                && in_array($entity->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])
            ) {
                if ($entity->quote_status_id == QuoteStatusEnum::FollowedUp && $entity->advisor_id) {
                    CammyJob::dispatch($entity, 'intro');
                }
                if ($request->leadStatus == QuoteStatusEnum::Qualified && $entity->advisor_id) {
                    IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $entity->uuid, 'send-rm-intro-email', null, false);
                } else {
                    SyncSIBContactJob::dispatch($entity);
                }

                if (
                    $previousQuoteStatus == QuoteStatusEnum::FollowedUp && $request->leadStatus != QuoteStatusEnum::FollowedUp
                    || $previousQuoteStatus == QuoteStatusEnum::ApplicationPending && $request->leadStatus != QuoteStatusEnum::ApplicationPending
                    || $request->leadStatus == QuoteStatusEnum::TransactionApproved
                ) {
                    CammyJob::dispatch($entity, 'unsub');
                }
            }

            $activityResponse = false;
            $previousStatusIdChanged = false;
            if (in_array(strtolower($request->modelType), [strtolower(quoteTypeCode::Health), strtolower(quoteTypeCode::Home), strtolower(quoteTypeCode::Business)])) {
                $quoteTypeId = [strtolower(quoteTypeCode::Home) => QuoteTypeId::Home, strtolower(quoteTypeCode::Health) => QuoteTypeId::Health, strtolower(quoteTypeCode::Business) => QuoteTypeId::Business];
                if ($entity->quotes_status_id != $previousQuoteStatus) {
                    $previousStatusIdChanged = true;
                }
                $activityResponse = (new CentralService())->saveAndAssignActivitesToAdvisor($entity, $quoteTypeId[strtolower($request->modelType)], $previousStatusIdChanged);
            }

            // ========= assign renewal batch to HEALTH LOB leads upon transaction approved =========

            if (strtolower($request->modelType) == strtolower(quoteTypeCode::Health) && $request->leadStatus == QuoteStatusEnum::TransactionApproved
                && $entity->source == LeadSourceEnum::IMCRM) {
                $this->healthQuoteService->assignRenewalBatch($entity);
                $this->updatePaymentStatus($entity);
            }

            // ========= END =========

            if (strtolower($request->modelType) == strtolower(quoteTypeCode::Car)
            && $request->leadStatus == QuoteStatusEnum::TransactionApproved) {
                $this->updatePaymentStatus($entity);
            }

            QuoteStatusLog::create([
                'quote_type_id' => QuoteTypeId::Car,
                'quote_request_id' => $entity->id,
                'current_quote_status_id' => $request->leadStatus,
                'previous_quote_status_id' => $previousQuoteStatus,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'notes' => $request->notes,
                'created_by' => Auth::user()->id,
            ]);

            return ['entity' => $entity, 'activityResponse' => $activityResponse];
        });
    }

    public function getAdvisorsByModelType($modelType)
    {
        $query = User::join('model_has_roles as mr', 'mr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->select('users.id', DB::raw("CONCAT(users.name,' - ',r.name) AS name"));
        if (strtolower($modelType) == strtolower(quoteTypeCode::Car)) {
            $query->whereIn('r.name', [RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager]);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Health)) {

            if ((auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor])) &&
                auth()->user()->hasAnyPermission(PermissionsEnum::HEALTH_QUOTES_ACCESS,
                    PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)
            ) {
                $authUserTeamsId = $this->getUserTeams(auth()->id())->pluck('id')->toArray();
                $query->whereIn('ut.team_id', $authUserTeamsId);
                $query->whereIn('r.name', [RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager]);
            } else {
                $query->whereIn('r.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor]);
            }
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
        $response = $this->{in_array($lowerCaseModelType, $this->quoteTypes) ? $lowerCaseModelType.'QuoteService' : $lowerCaseModelType.'Service'}
            ->{in_array($lowerCaseModelType, $this->quoteTypes) ? 'update'.ucwords($modelType).'Quote' : 'update'.ucwords($modelType)}($request, $id);

        if ((in_array($lowerCaseModelType, $this->quoteTypes) ? 'update'.ucwords($modelType).'Quote' : 'update'.ucwords($modelType)) == 'update'.ucwords($modelType).'Quote') {
            return $response;
        }
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

    public function getOcbCustomerEmailTemplate($quotePlansCount)
    {
        if ($quotePlansCount == 1) {
            $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE';
        } elseif ($quotePlansCount > 1) {
            $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE';
        } else {
            $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_ZERO_PLAN_TEMPLATE';
        }

        return $this->applicationstorageService->getValueByKey($key);
    }

    public function getGenderOptions()
    {
        $genderOptions = [
            GenericRequestEnum::MALE_SINGLE_VALUE => GenericRequestEnum::MALE_SINGLE,
            GenericRequestEnum::FEMALE_SINGLE_VALUE => GenericRequestEnum::FEMALE_SINGLE,
            GenericRequestEnum::FEMALE_MARRIED_VALUE => GenericRequestEnum::FEMALE_MARRIED,
        ];

        return $genderOptions;
    }

    public function toggleSelection($data, $quoteTypeId)
    {
        $toggleData = [
            'quoteUid' => $data->quote_uuid,
            'quoteTypeId' => $quoteTypeId,
            'epOptionId' => $data->id,
        ];

        $response = Ken::request('/toggle-embedded-product', 'post', $toggleData);

        return $response;
    }

    public function capturePayment($quoteModel, $paymentSplit, $quoteTypeId, $amount)
    {
        if ($paymentSplit) {
            if ($amount > 0) {
                PaymentAction::updateOrInsert(
                    ['payment_code' => $paymentSplit->code, 'sr_no' => $paymentSplit->sr_no],
                    [
                        'is_fulfilled' => 0,
                        'action_type' => 'CAPTURE',
                        'amount' => $amount,
                        'created_by' => auth()->user()->email,
                        'is_manager_approved' => 1,
                    ]);
                $data = [
                    'uuid' => $quoteModel->uuid,
                    'type_id' => $quoteTypeId,
                    'code' => $paymentSplit->code.'-'.$paymentSplit->sr_no,
                ];
                $processResponse = $this->processCapturePayment($data);

                return response($processResponse, 200);

            } else {
                return response(['Payment not exist'], 403);
            }
        }

        return response(['Transaction does not exist'], 403);
    }
    public function processCapturePayment($data)
    {
        $planData = [
            'quoteUID' => $data['uuid'],
            'quoteTypeId' => $data['type_id'],
            'payments' => [
                [
                    'codeRef' => $data['code'],
                ],
            ],
        ];

        $response = Marshall::request('/payment/checkout/capture', 'post', $planData);

        return $response;
    }

    /*
     * This function is just for checking AML Status.
     */
    public function checkAmlQuoteStatus($statusId)
    {
        if ($statusId == QuoteStatusEnum::AMLScreeningCleared) {
            return 'No';
        } elseif ($statusId == QuoteStatusEnum::AMLScreeningFailed) {
            return 'Yes';
        }

        return '';
    }

    public function scoreBreakdown($quote, $type)
    {
        $scoreList = [];
        $customerScore = 0;
        if ($quote->payments->first() && isset($quote->customer)) {
            $paymentTopScore = 0;
            $paymentMethod = '';
            $paymentAuthorized = 0;

            foreach ($quote->payments as $payment) {
                $currentScore = in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_THREE_RATING) ? 3 : (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_TWO_RATING) ? 2 : 2);
                if ($currentScore > $paymentTopScore) {
                    $paymentTopScore = $currentScore;
                    $paymentMethod = $payment->payment_methods_code;
                }
                if ($payment->premium_authorized != null) {
                    $paymentAuthorized += $payment->premium_authorized;
                }
            }

            if (isset($quote->customer->customerDetail)) {
                $customerDetail = $quote->customer->customerDetail;
                $jobScore = in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_TWO_RATING) ? 2 : 1);
                $scoreList[] = ['score' => $jobScore, 'text' => 'Profession - Professional Job Title', 'value' => str_replace('-', ' ', $customerDetail->job_title)];
                $customerScore += $jobScore;

                // Nationality
                if (isset($quote->customer->nationality)) {
                    $nationalityScore = in_array(strtolower($quote->customer->nationality->country_name), Kyc::COUNTRY_NATIONALITY_FOUR_RATING) ? 4 : 1;
                    $scoreList[] = ['score' => $nationalityScore, 'text' => 'Nationality', 'value' => $quote->customer->nationality->country_name];
                    $customerScore += $nationalityScore;
                }
                // Product type
                $customerScore += 1; // For products all product have 1
                $scoreList[] = ['score' => 1, 'text' => 'Product -Insurance Type', 'value' => $type];

                // Payment amount Transaction value / Premium (AED)
                $paymentScore = ($paymentAuthorized >= 100001) ? 3 : (($paymentAuthorized >= 55001 && $paymentAuthorized <= 100000) ? 2 : 1);
                $scoreList[] = ['score' => $paymentScore, 'text' => 'Transaction value / Premium (AED)', 'value' => $paymentAuthorized];
                $customerScore += $paymentScore;
                // payment mode
                $customerScore += $paymentTopScore;
                $scoreList[] = ['score' => $paymentTopScore, 'text' => 'Mode of Payment', 'value' => $paymentMethod];

                $residentScore = in_array(strtolower($customerDetail->residential_status), Kyc::RESIDENT_STATUS_THREE_RATING) ? 3 : 1;
                $scoreList[] = ['score' => $residentScore, 'text' => 'Resident Status', 'value' => preg_replace('/[A-Z]/', ' '.'$0', $customerDetail->residential_status)];
                $customerScore += $residentScore;

                $scoreList[] = ['score' => 1, 'text' => 'Transaction Volume', 'value' => 1];
                $customerScore += 1; // payment volume for future use

                $deliveryModeScore = in_array(strtolower($customerDetail->mode_of_delivery), Kyc::MODE_OF_DELIVERY_THREE_RATING) ? 3 : 1;
                $scoreList[] = ['score' => $deliveryModeScore, 'text' => 'Mode Of Delivery', 'value' => Kyc::MODE_OF_DELIVERY[$customerDetail->mode_of_delivery]];
                $customerScore += $deliveryModeScore;

                $contactScore = in_array(strtolower($customerDetail->mode_of_contact), Kyc::MODE_OF_CONTACT_THREE_RATING) ? 3 : 1;
                $scoreList[] = ['score' => $contactScore, 'text' => 'Mode Of Contact', 'value' => preg_replace('/[A-Z]/', ' '.'$0', $customerDetail->mode_of_contact)];
                $customerScore += $contactScore;

                $empScore = in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_TWO_RATING) ? 2 : 1);
                $scoreList[] = ['score' => $empScore, 'text' => 'Employment Sector', 'value' => preg_replace('/[A-Z]/', ' '.'$0', $customerDetail->employment_sector)];
                $customerScore += $empScore;

                $tenScore = in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_TWO_RATING) ? 2 : 1);
                $scoreList[] = ['score' => $tenScore, 'text' => 'Customer Tenure with IM', 'value' => $customerDetail->customer_tenure];
                $customerScore += $tenScore;
            }

            return $scoreList;
        }
    }

    public function calculateScore($quote)
    {
        if ($quote->payments->first() && isset($quote->customer)) {
            $paymentTopScore = 0;
            $paymentAuthorized = 0;
            $customerScore = 0;
            foreach ($quote->payments as $payment) {
                $currentScore = in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_THREE_RATING) ? 3 : (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_TWO_RATING) ? 2 : 2);
                if ($currentScore > $paymentTopScore) {
                    $paymentTopScore = $currentScore;
                }
                if ($payment->premium_authorized != null) {
                    $paymentAuthorized += $payment->premium_authorized;
                }
            }
            if (isset($quote->customer->nationality)) {
                $customerScore = in_array(strtolower($quote->customer->nationality->country_name), Kyc::COUNTRY_NATIONALITY_FOUR_RATING) ? 4 : 1;
            }
            $customerScore += ($paymentAuthorized >= 100001) ? 3 : (($paymentAuthorized >= 55001 && $paymentAuthorized <= 100000) ? 2 : 1);
            $customerScore += 1; // For products all product have 1
            $customerScore += 1; // payment volume for future use
            $customerScore += $paymentTopScore;

            if (isset($quote->customer->customerDetail)) {
                $customerDetail = $quote->customer->customerDetail;
                if (isset($customerDetail)) {
                    $customerScore += in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_TWO_RATING) ? 2 : 1);
                    $customerScore += in_array(strtolower($customerDetail->residential_status), Kyc::RESIDENT_STATUS_THREE_RATING) ? 3 : 1;
                    $customerScore += in_array(strtolower($customerDetail->mode_of_delivery), Kyc::MODE_OF_DELIVERY_THREE_RATING) ? 3 : 1;
                    $customerScore += in_array(strtolower($customerDetail->mode_of_contact), Kyc::MODE_OF_CONTACT_THREE_RATING) ? 3 : 1;

                    $customerScore += in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_TWO_RATING) ? 2 : 1);
                    $customerScore += in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_TWO_RATING) ? 2 : 1);

                    $quote->risk_score = $customerScore;
                    $quote->save();
                }
            }
        }
    }

    public function getInquiryLogs($modelType, $uuid)
    {
        $model = 'App\\Models\\'.$modelType.'Quote';
        $quote = $model::where('uuid', $uuid)->with('duplicateInquiryLog')
            ->whereHas('duplicateInquiryLog')
            ->first();

        return optional($quote)->duplicateInquiryLog;
    }

    public function hashCollapsibleStatuses($quoteTypeId, $quoteId)
    {
        return QuoteStatusLog::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteId)
            ->where(function ($query) {
                $query->where('current_quote_status_id', QuoteStatusEnum::PolicyIssued)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::PolicyIssued)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::TransactionApproved)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::TransactionApproved)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PolicySentToCustomer)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::PolicySentToCustomer)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PolicyBooked)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::PolicyBooked);
            })
            ->first() !== null;
    }
}
