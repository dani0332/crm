<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\Kyc;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Ken;
use App\Facades\Marshall;
use App\Jobs\CammyJob;
use App\Jobs\CarLost\CarLostStatusRejected;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\CarLostQuoteLog;
use App\Models\Entity;
use App\Models\GenericModel;
use App\Models\Lookup;
use App\Models\PaymentAction;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Models\SendUpdateLog;
use App\Models\User;
use App\Repositories\CustomerMembersRepository;
use App\Traits\CentralTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class CRUDService extends BaseService
{
    use CentralTrait, TeamHierarchyTrait;

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
            // if model is health ,team is ebp ,previous status is quoted and wants to update qualified then restrict advisor
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
                $entity->quote_status_date = now();
                if ($entity->stale_at) {
                    $entity->stale_at = null;
                }
            }

            $entity->save();

            if (
                strtolower($request->modelType) == strtolower(quoteTypeCode::Car)
                && $request->leadStatus == QuoteStatusEnum::CarSold || $request->leadStatus == QuoteStatusEnum::Uncontactable
                || $request->leadStatus == QuoteStatusEnum::EarlyRenewal
            ) {
                if (! empty($request->car_lost_quote_log_id) && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                    // perform approval or rejection
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
                        // send rejection email
                        CarLostStatusRejected::dispatch($entity, $carLostQuoteLog);
                    }
                } elseif (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor])) {
                    // store request of car sold/uncontactable with proof
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

                if (
                    $previousQuoteStatus == QuoteStatusEnum::FollowedUp && $request->leadStatus != QuoteStatusEnum::FollowedUp
                    || $previousQuoteStatus == QuoteStatusEnum::ApplicationPending && $request->leadStatus != QuoteStatusEnum::ApplicationPending
                    || $request->leadStatus == QuoteStatusEnum::TransactionApproved
                ) {
                    CammyJob::dispatch($entity, 'unsub');
                }
            }
            $quoteTypeId = constant(QuoteTypeId::class.'::'.$request->modelType);

            $activityResponse = false;
            $previousStatusIdChanged = false;
            if (in_array(strtolower($request->modelType), [strtolower(quoteTypeCode::Health), strtolower(quoteTypeCode::Home), strtolower(quoteTypeCode::Business)])) {
                $quoteTypeId = [strtolower(quoteTypeCode::Home) => QuoteTypeId::Home, strtolower(quoteTypeCode::Health) => QuoteTypeId::Health, strtolower(quoteTypeCode::Business) => QuoteTypeId::Business];
                if ($entity->quotes_status_id != $previousQuoteStatus) {
                    $previousStatusIdChanged = true;
                }
                $activityResponse = (new CentralService)->saveAndAssignActivitesToAdvisor($entity, $quoteTypeId[strtolower($request->modelType)], $previousStatusIdChanged);
            }

            // ========= assign renewal batch to HEALTH LOB leads upon transaction approved =========

            $ecommerceSource = ApplicationStorage::where('key_name', ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE)->value('value');
            if (
                strtolower($request->modelType) == strtolower(quoteTypeCode::Health) && $request->leadStatus == QuoteStatusEnum::TransactionApproved
                && ($entity->source == LeadSourceEnum::IMCRM || strpos($entity->source, $ecommerceSource) !== false)
            ) {
                $this->healthQuoteService->assignRenewalBatch($entity->id);
                $this->updatePaymentStatus($entity);
            }

            // ========= END =========

            if (
                strtolower($request->modelType) == strtolower(quoteTypeCode::Car)
                && $request->leadStatus == QuoteStatusEnum::TransactionApproved
            ) {
                $this->updatePaymentStatus($entity);
            }

            QuoteStatusLog::create([
                'quote_type_id' => collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType)),
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
            $query->whereIn('r.name', [RolesEnum::CarAdvisor]);
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Health)) {
            if ((auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor])) &&
                auth()->user()->hasAnyPermission(
                    PermissionsEnum::HEALTH_QUOTES_ACCESS,
                    PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS
                )
            ) {
                $authUserTeamsId = $this->getUserTeams(auth()->id())->pluck('id')->toArray();
                $query->whereIn('ut.team_id', $authUserTeamsId);
                $query->whereIn('r.name', [RolesEnum::CarAdvisor]);
            } else {
                $query->whereIn('r.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthRenewalAdvisor]);
            }
        } elseif (strtolower($modelType) == strtolower(quoteTypeCode::Business)) {
            $query->whereIn('r.name', [RolesEnum::CorpLineAdvisor, RolesEnum::CorpLineRenewalAdvisor, RolesEnum::GMRenewalAdvisor]);
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
            ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_RENEWAL_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"));

        return $query->orderBy('r.name')->distinct()->get();
    }

    public function getRMAndBusinessAdvisors()
    {
        $query = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'BUSINESS_ADVISOR', 'AMT_ADVISOR', 'HEALTH_RENEWAL_ADVISOR'])
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

    public function getOcbCustomerEmailTemplate($quotePlansCount, $type = quoteTypeCode::Car)
    {
        $key = '';
        if ($type == quoteTypeCode::Car) {
            if ($quotePlansCount == 1) {
                $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE';
            } elseif ($quotePlansCount > 1) {
                $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE';
            } else {
                $key = 'SIB_CAR_QUOTE_ONE_CLICK_BUY_ZERO_PLAN_TEMPLATE';
            }
        } elseif ($type == quoteTypeCode::Bike) {
            $key = 'SIB_BIKE_QUOTE_PLAN_TEMPLATE';
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
                    ]
                );

                $data = [
                    'uuid' => $quoteModel->uuid,
                    'type_id' => $quoteTypeId,
                    'code' => $paymentSplit->code.'-'.$paymentSplit->sr_no,
                ];

                // Payload update for Send Update to Payment Gateway
                if (get_class($quoteModel) == SendUpdateLog::class) {
                    $data['type_id'] = GenericRequestEnum::SEND_UPDATE_QUOTE_TYPE_MARSHAL;
                }

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
        if ($type == 'business') {
            return $this->scoreEntityBreakdown($quote);
        } else {
            $scoreList = [];
            $customerScore = 0;
            if ($quote->payments->first() && isset($quote->customer)) {
                $paymentTopScore = 0;
                $paymentMethod = '';
                $paymentAuthorized = 0;

                foreach ($quote->payments as $payment) {
                    $currentScore = in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_THREE_RATING) ? 3 :
                        (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_TWO_RATING) ? 2 :
                            (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_ONE_RATING) ? 1 : 1));
                    if ($currentScore > $paymentTopScore) {
                        $paymentTopScore = $currentScore;
                        if ($payment->payment_methods_code === PaymentMethodsEnum::Cash) {
                            $paymentMethod = 'Cash';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::BankTransfer) {
                            $paymentMethod = 'Bank Transfer';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::CreditCard) {
                            $paymentMethod = 'Credit Card';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::Cheque) {
                            $paymentMethod = 'Cheque';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::PostDatedCheque) {
                            $paymentMethod = 'PostDatedCheque';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::InsurerPayment) {
                            $paymentMethod = 'Insurer Payment';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::PartialPayment) {
                            $paymentMethod = 'Partial Payment';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::MultiplePayment) {
                            $paymentMethod = 'Multiple Payment';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::CreditApproval) {
                            $paymentMethod = 'Credit Approval';
                        } elseif ($payment->payment_methods_code === PaymentMethodsEnum::ProformaPaymentRequest) {
                            $paymentMethod = 'Proforma Payment Request';
                        } else {
                            $paymentMethod = 'Insure Now Pay Later';
                        }
                    }
                    if ($payment->premium_authorized != null) {
                        $paymentAuthorized += $payment->premium_authorized;
                    }
                }
                $quoteType = QuoteType::where('code', ucfirst($type))->first();
                $amlStatus = (AMLService::checkAMLStatusFailed($quoteType->id, $quote->id));
                $amlLogsValue = ['score' => 1, 'value' => 'No'];
                if ($amlStatus == true) {
                    $amlLogsValue = ['score' => 3, 'value' => 'Yes'];
                }
                if (isset($quote->customer->customerDetail)) {
                    $customerDetail = $quote->customer->customerDetail;
                    $jobType = Lookup::where(['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => $customerDetail->job_title])->first();
                    $jobTypeValue = $customerDetail->job_title;
                    if (isset($jobType->text)) {
                        $jobTypeValue = $jobType->text;
                    }
                    $jobScore = in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->job_title), Kyc::PROFESSION_TWO_RATING) ? 2 : 1);
                    $scoreList[] = ['score' => $jobScore, 'text' => 'Profession - Professional Job Title', 'value' => $jobTypeValue];
                    $customerScore += $jobScore;

                    $residentScore = in_array(strtolower($customerDetail->residential_status), Kyc::RESIDENT_STATUS_THREE_RATING) ? 3 : 1;
                    $residentType = Lookup::where(['key' => LookupsEnum::RESIDENT_STATUS, 'code' => $customerDetail->residential_status])->first();
                    $residentTypeValue = $customerDetail->residential_status;
                    if (isset($residentType->text)) {
                        $residentTypeValue = $residentType->text;
                    }
                    $scoreList[] = ['score' => $residentScore, 'text' => 'Resident Status', 'value' => $residentTypeValue];
                    $customerScore += $residentScore;

                    if ($customerDetail->in_sanction_list == 1) {
                        $text = 'Yes';
                        $score = 3;
                    } else {
                        $text = 'No';
                        $score = 1;
                    }
                    $scoreList[] = ['score' => $score, 'text' => 'Is the Natural Person listed in any Sanction/OOL/SIP list?', 'value' => $text];
                    $customerScore += $score;

                    $adverseMedia = $this->getAMLcompliance($quote->id, 'in_adverse_media', $amlLogsValue);
                    $scoreList[] = ['score' => $adverseMedia['score'], 'text' => 'Is the Natural Person listed in any adverse media?', 'value' => $adverseMedia['value']];
                    $customerScore += $adverseMedia['score'];

                    // any PEP List/ Adverse Media not dynamic yet
                    $ownerPep = $this->getAMLcompliance($quote->id, 'is_owner_pep', $amlLogsValue);
                    $scoreList[] = ['score' => $ownerPep['score'], 'text' => 'Is the Natural Person listed in PEP/FPEP/HIO?', 'value' => $ownerPep['value']];
                    $customerScore += $ownerPep['score'];

                    $tenScore = in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->customer_tenure), Kyc::TENURE_TWO_RATING) ? 2 : 1);
                    $tenureValue = $tenScore == 1 ? '3 years and above' : ($tenScore == 2 ? 'Less than two years' : 'Less than 6 months');
                    $scoreList[] = ['score' => $tenScore, 'text' => 'Tenure of Relationship in years', 'value' => $tenureValue];
                    $customerScore += $tenScore;
                    $empScore = in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->employment_sector), Kyc::EMPLOYMENT_SECTOR_TWO_RATING) ? 2 : 1);
                    $empType = Lookup::where(['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => $customerDetail->employment_sector])->first();
                    $empTypeValue = $customerDetail->employment_sector;
                    if (isset($empType->text)) {
                        $empTypeValue = $empType->text;
                    }
                    $scoreList[] = ['score' => $empScore, 'text' => 'Employment Sector', 'value' => $empTypeValue];
                    $customerScore += $empScore;

                    if ($customerDetail->is_partner == 1) {
                        $text = 'Yes';
                        $score = 3;
                    } else {
                        $text = 'No';
                        $score = 1;
                    }
                    $scoreList[] = ['score' => $score, 'text' => 'Is the Natural Person an Owner/Shareholder/Partner in any Organization?', 'value' => $text];
                    $customerScore += $score;

                    // Nationality
                    if (isset($quote->customer->nationality)) {
                        $nationalityScore = in_array(strtolower($quote->customer->nationality->country_name), Kyc::COUNTRY_NATIONALITY_FOUR_RATING) ? 4 : 1;
                        $scoreList[] = ['score' => $nationalityScore, 'text' => 'Nationality', 'value' => $quote->customer->nationality->country_name];
                        $customerScore += $nationalityScore;
                    }

                    if ($customerDetail->dual_nationality == 1) {
                        $text = 'Yes';
                        $score = 3;
                    } else {
                        $text = 'No';
                        $score = 1;
                    }
                    $scoreList[] = ['score' => $score, 'text' => 'Does the Natural Person hold “Dual Nationality”?', 'value' => $text];
                    $customerScore += $score;

                    if ($customerDetail->deal_sanction_list == 1) {
                        $text = 'Yes';
                        $score = 3;
                    } else {
                        $text = 'No';
                        $score = 1;
                    }
                    $scoreList[] = ['score' => $score, 'text' => 'Does the Natural Person intend to provide professional services in any sanctions-listed country/ies?', 'value' => $text];
                    $customerScore += $score;

                    if ($customerDetail->is_operation_high_risk == 1) {
                        $text = 'Yes';
                        $score = 3;
                    } else {
                        $text = 'No';
                        $score = 1;
                    }

                    $scoreList[] = ['score' => $score, 'text' => 'Is the Natural Person controlling/involved in any business listed in High-Risk Countries?', 'value' => $text];
                    $customerScore += $score;

                    // Product type
                    $customerScore += 1; // For products all product have 1
                    $scoreList[] = ['score' => 1, 'text' => 'Types of Products', 'value' => $type];
                    if (isset($customerDetail->premium_tenure)) {
                        $transactionVolumesScore = in_array(strtolower($customerDetail->premium_tenure), Kyc::PREMIUM_TENURE_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->premium_tenure), Kyc::PREMIUM_TENURE_TWO_RATING) ? 2 : 1);
                        $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Premium Tenure', 'value' => Kyc::PREMIUM_TENURE[$customerDetail->premium_tenure]];
                        $customerScore += $transactionVolumesScore;
                    }
                    // Payment amount Transaction value / Premium (AED)
                    $paymentScore = ($paymentAuthorized >= 1000000) ? 3 : (($paymentAuthorized >= 250001 && $paymentAuthorized <= 1000000) ? 2 : 1);
                    $paymentAuthorizedValue = $paymentScore == 3 ? 'Above AED 1,000,000' : ($paymentScore == 2 ? 'AED 250,001 to AED 1,000,000' : 'Upto AED 250,000');
                    $scoreList[] = ['score' => $paymentScore, 'text' => 'Transaction Value', 'value' => $paymentAuthorizedValue];
                    $customerScore += $paymentScore;
                    if (isset($customerDetail->transaction_pattern)) {
                        $transactionVolumesScore = in_array(strtolower($customerDetail->transaction_pattern), Kyc::TRANSACTION_PATTERN_THREE_RATING) ? 3 : (in_array(strtolower($customerDetail->transaction_pattern), Kyc::TRANSACTION_PATTERN_ZERO_RATING) ? 0 : 1);
                        $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Transaction Pattern changes', 'value' => Kyc::TRANSACTION_PATTERN[$customerDetail->transaction_pattern]];
                        $customerScore += $transactionVolumesScore;
                    }
                    // payment mode
                    $customerScore += $paymentTopScore;
                    $scoreList[] = ['score' => $paymentTopScore, 'text' => 'Payment Mode', 'value' => $paymentMethod];
                    if (isset($customerDetail->mode_of_delivery)) {
                        $deliveryModeScore = in_array(strtolower($customerDetail->mode_of_delivery), Kyc::MODE_OF_DELIVERY_THREE_RATING) ? 3 : 1;
                        $scoreList[] = ['score' => $deliveryModeScore, 'text' => 'Delivery Channel', 'value' => Kyc::MODE_OF_DELIVERY[$customerDetail->mode_of_delivery]];
                        $customerScore += $deliveryModeScore;
                    }

                    $contactScore = '';
                    $modTypeValue = '';
                    if (isset($customerDetail->mode_of_contact)) {
                        $contactScore = in_array(strtolower($customerDetail->mode_of_contact), Kyc::MODE_OF_CONTACT_THREE_RATING) ? 3 : 1;
                        $modType = Lookup::where(['key' => LookupsEnum::MODE_OF_CONTACT, 'code' => $customerDetail->mode_of_contact])->first();
                        $modTypeValue = $customerDetail->mode_of_contact;
                        if (isset($modType->text)) {
                            $modTypeValue = $modType->text;
                        }
                    }
                    $scoreList[] = ['score' => $contactScore, 'text' => 'Mode Of Contact', 'value' => $modTypeValue];
                    $customerScore += $contactScore;
                }

                return ['total' => $customerScore, 'score_list' => $scoreList];
            }
        }
    }

    public function scoreEntityBreakdown($quote)
    {
        $scoreList = [];
        $entityScore = 0;
        if (! isset($quote->quoteRequestEntityMapping)) {
            return;
        }
        $quoteId = $quote->id;

        $amlStatus = (AMLService::checkAMLStatusFailed(5, $quote->id));
        $amlLogsValue = ['score' => 1, 'value' => 'No'];
        if ($amlStatus == true) {
            $amlLogsValue = ['score' => 3, 'value' => 'Yes'];
        }

        $entity = Entity::where('id', $quote->quoteRequestEntityMapping->entity->id)->first();

        $paymentTopScore = 0;
        if (isset($entity->legal_structure) && $entity->legal_structure != '') {
            $legalStructureScore = in_array(strtolower($entity->legal_structure), Kyc::ENTITY_LEGAL_STRUCTURE_THREE_RATING) ? 3 : (in_array(strtolower($entity->legal_structure), Kyc::ENTITY_LEGAL_STRUCTURE_TWO_RATING) ? 2 : 1);
            $legalType = Lookup::where(['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => $entity->legal_structure])->first();
            $legalStatusValue = $entity->legal_structure;
            if (isset($legalType->text)) {
                $legalStatusValue = $legalType->text;
            }
            $scoreList[] = ['score' => $legalStructureScore, 'text' => 'Legal Status Of The Entity', 'value' => $legalStatusValue];
            $entityScore += $legalStructureScore;
        }

        // s
        if (isset($entity->industry_type_code) && $entity->industry_type_code != '') {
            $industryTypeCode = in_array(strtolower($entity->industry_type_code), Kyc::ENTITY_INDUSTRY_TYPE_ONE_RATING) ? 1 : (in_array(strtolower($entity->industry_type_code), Kyc::ENTITY_INDUSTRY_TYPE_TWO_RATING) ? 2 : 3);
            $industryType = Lookup::where(['key' => LookupsEnum::COMPANY_TYPE, 'code' => $entity->industry_type_code])->first();
            $industryTypeValue = $entity->industry_type_code;
            if (isset($industryType->text)) {
                $industryTypeValue = $industryType->text;
            }
            $scoreList[] = ['score' => $industryTypeCode, 'text' => 'Nature Of Business', 'value' => $industryTypeValue];
            $entityScore += $industryTypeCode;
        }

        // sanctions
        if ($entity->in_sanction_list == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }
        $scoreList[] = ['score' => $score, 'text' => 'Does the Company name or Subsidiary/Affiliate entities feature in any sanction list?', 'value' => $text];
        $entityScore += $score;

        // Advers media not dynamic yet
        $adverseMedia = $this->getAMLcompliance($quoteId, 'in_adverse_media', $amlLogsValue);
        $scoreList[] = ['score' => $adverseMedia['score'], 'text' => 'Does the Company name or subsidiary / Affiliate entities feature in any adverse media?', 'value' => $adverseMedia['value']];
        $entityScore += $adverseMedia['score'];

        // any PEP List/ Adverse Media not dynamic yet
        $ownerPep = $this->getAMLcompliance($quoteId, 'is_owner_pep', $amlLogsValue);
        $scoreList[] = ['score' => $ownerPep['score'], 'text' => 'Does the owner/ Shareholder/Partner of the company feature in any PEP List/ Adverse Media?', 'value' => $ownerPep['value']];
        $entityScore += $ownerPep['score'];

        // Tenure of Relationship in years
        $customerTenureScore = ($entity->customer_tenure == 3 || $entity->customer_tenure > 3) ? 1 : (($entity->customer_tenure <= 2 && $entity->customer_tenure > 1) ? 2 : 1);
        $tenureValue = $customerTenureScore == 1 ? '3 years and above' : ($customerTenureScore == 2 ? 'Less than two years' : 'Less than 6 months');
        $scoreList[] = ['score' => $customerTenureScore, 'text' => 'Tenure of Relationship in years', 'value' => $tenureValue];
        $entityScore += $customerTenureScore;

        $controlling = $this->getAMLcompliance($quoteId, 'is_controlling_pep', $amlLogsValue);
        $scoreList[] = ['score' => $controlling['score'], 'text' => 'Is the controlling person a PEP/HIO/FPEP/Government Organization?', 'value' => $controlling['value']];
        $entityScore += $controlling['score'];

        // sanction Match
        if ($entity->is_sanction_match == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }
        $scoreList[] = ['score' => $score, 'text' => 'Is There A Sanction Match On The Owner/Partners/Bod, Senior Management, Group Company, Holding Company Or Related Company Names?', 'value' => $text];
        $entityScore += $score;

        if (isset($entity->corporationCountry)) {
            $corporationScore = in_array(strtolower($entity->corporationCountry->country_name), Kyc::COUNTRY_NATIONALITY_FOUR_RATING) ? 4 : 1;
            $scoreList[] = ['score' => $corporationScore, 'text' => 'Country Of Incorporation', 'value' => $entity->corporationCountry->country_name];
            $entityScore += $corporationScore;
        }
        // FATF
        if ($entity->in_fatf == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }
        $scoreList[] = ['score' => $score, 'text' => 'Does the company have any subsidiary, affiliate, branch, or group/holding company in FATF-listed high-risk monitored jurisdiction?', 'value' => $text];
        $entityScore += $score;

        $ubos = CustomerMembersRepository::getBy($quote->id, QuoteTypes::BUSINESS->name, CustomerTypeEnum::Entity);
        $ubScore = 1;
        foreach ($ubos as $ub) {
            $currrentUbScore = in_array(strtolower($ub->nationality->country_name), Kyc::COUNTRY_NATIONALITY_FOUR_RATING) ? 3 : 1;
            if ($ubScore < $currrentUbScore) {
                $currrentUbScore = $currrentUbScore;
            }
        }
        if ($entity->is_owner_high_risk == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }

        $scoreList[] = ['score' => $score, 'text' => 'Does the owner/ Shareholder/ Partner/Director of the company from High-Risk countries?', 'value' => $text];
        $entityScore += $score;

        // New Field

        if ($entity->deal_sanction_list == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }
        $scoreList[] = ['score' => $score, 'text' => 'Does the customer intend to deal with any country listed in the Sanctions List?', 'value' => $text];
        $entityScore += $score;

        //

        if ($entity->is_operation_high_risk == 1) {
            $text = 'Yes';
            $score = 3;
        } else {
            $text = 'No';
            $score = 1;
        }
        $scoreList[] = ['score' => $score, 'text' => 'Do the customer or subsidiary/ affiliate entities have operations in any High-Risk Countries?', 'value' => $text];
        $entityScore += $score;

        // products
        $scoreList[] = ['score' => 1, 'text' => 'Types of Products', 'value' => 'Business'];
        $entityScore += 1;

        if (isset($entity->transaction_volume) && $entity->transaction_volume != '') {
            $transactionVolumesScore = in_array(strtolower($entity->transaction_volume), Kyc::ENTITY_TRANSACTION_VOLUME_THREE_RATING) ? 3 : (in_array(strtolower($entity->transaction_volume), Kyc::ENTITY_TRANSACTION_VOLUME_TWO_RATING) ? 2 : 1);
            $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Transaction Volume', 'value' => Kyc::TRANSACTION_VOLUME[$entity->transaction_volume]];
            $entityScore += $transactionVolumesScore;
        }

        $paymentAuthorized = 0;
        $paymentMethod = '';
        foreach ($quote->payments as $payment) {
            $currentScore = in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_THREE_RATING) ? 3 :
                (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_TWO_RATING) ? 2 :
                    (in_array(strtolower($payment->payment_methods_code), Kyc::PAYMENT_MODE_ONE_RATING) ? 1 : 1));
            if ($currentScore > $paymentTopScore) {
                $paymentTopScore = $currentScore;
                if ($payment->payment_methods_code === PaymentMethodsEnum::Cash) {
                    $paymentMethod = 'Cash';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::BankTransfer) {
                    $paymentMethod = 'Bank Transfer';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::CreditCard) {
                    $paymentMethod = 'Credit Card';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::Cheque) {
                    $paymentMethod = 'Cheque';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::PostDatedCheque) {
                    $paymentMethod = 'PostDatedCheque';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::InsurerPayment) {
                    $paymentMethod = 'Insurer Payment';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::PartialPayment) {
                    $paymentMethod = 'Partial Payment';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::MultiplePayment) {
                    $paymentMethod = 'Multiple Payment';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::CreditApproval) {
                    $paymentMethod = 'Credit Approval';
                } elseif ($payment->payment_methods_code === PaymentMethodsEnum::ProformaPaymentRequest) {
                    $paymentMethod = 'Proforma Payment Request';
                } else {
                    $paymentMethod = 'Insure Now Pay Later';
                }
            }
            if ($payment->premium_authorized != null) {
                $paymentAuthorized += $payment->premium_authorized;
            }
        }

        $transactionValueScore = ($paymentAuthorized >= 1000001) ? 3 : (($paymentAuthorized >= 250000 && $paymentAuthorized <= 1000000) ? 2 : 1);
        $paymentAuthorizedValue = $transactionValueScore == 3 ? 'Above AED 1,000,000' : ($transactionValueScore == 2 ? 'AED 250,001 to AED 1,000,000' : 'Upto AED 250,000');
        $scoreList[] = ['score' => $transactionValueScore, 'text' => 'Transaction Value', 'value' => $paymentAuthorizedValue];
        $entityScore += $transactionValueScore;
        if (isset($entity->transaction_activities)) {
            $transactionVolumesScore = in_array(strtolower($entity->transaction_activities), Kyc::TRANSACTION_ACTIVITIES_THREE_RATING) ? 3 : (in_array(strtolower($entity->transaction_activities), Kyc::TRANSACTION_ACTIVITIES_TWO_RATING) ? 2 : 1);
            $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Transaction Activities', 'value' => Kyc::TRANSACTION_ACTIVITIES[$entity->transaction_activities]];
            $entityScore += $transactionVolumesScore;
        }

        if (isset($entity->transaction_pattern)) {
            $transactionVolumesScore = in_array(strtolower($entity->transaction_pattern), Kyc::TRANSACTION_PATTERN_THREE_RATING) ? 3 : (in_array(strtolower($entity->transaction_pattern), Kyc::TRANSACTION_PATTERN_ZERO_RATING) ? 0 : 1);
            $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Transaction Pattern changes', 'value' => Kyc::TRANSACTION_PATTERN[$entity->transaction_pattern]];
            $entityScore += $transactionVolumesScore;
        }
        $scoreList[] = ['score' => $paymentTopScore, 'text' => 'Payment Mode', 'value' => $paymentMethod];
        $entityScore += $paymentTopScore;
        if (isset($entity->mode_of_contact)) {
            $transactionVolumesScore = in_array(strtolower($entity->mode_of_contact), Kyc::ENTITY_MODE_OF_CONTACT_THREE_RATING) ? 3 : 1;
            $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Mode of Contact', 'value' => $entity->mode_of_contact];
            $entityScore += $transactionVolumesScore;
        }
        if (isset($entity->mode_of_delivery)) {
            $transactionVolumesScore = in_array(strtolower($entity->mode_of_delivery), Kyc::ENTITY_MODE_OF_DELIVERY_THREE_RATING) ? 3 : 1;
            $scoreList[] = ['score' => $transactionVolumesScore, 'text' => 'Delivery Channel', 'value' => Kyc::MODE_OF_DELIVERY[$entity->mode_of_delivery]];
            $entityScore += $transactionVolumesScore;
        }

        return ['total' => $entityScore, 'score_list' => $scoreList];
    }

    public function getAMLCompliance($quoteId, $column, $amlLogsValue)
    {
        $amlProperty = AML::where('quote_request_id', $quoteId)->where($column, 1)->first();
        if (isset($amlProperty->id)) {
            return ['score' => 3, 'value' => 'Yes'];
        }
        $amlFalseProperty = AML::where('quote_request_id', $quoteId)->where($column, 0)->first();
        if (isset($amlFalseProperty->id)) {
            return ['score' => 1, 'value' => 'No'];
        }

        return $amlLogsValue;
    }

    public function calculateScore($quote, $type)
    {
        if (strtolower($type) == 'business') {
            $pdfName = 'Entity';
            $results = $this->scoreEntityBreakdown($quote);
        } else {
            $results = $this->scoreBreakdown($quote, $type);
            $pdfName = 'Individual';
        }
        if (isset($results['total'])) {
            $quote->risk_score = $results['total'];
            $quote->save();
            $quoteType = strtolower($type);
            $quoteModel = $this->getQuoteObjectBy($quoteType, $quote->uuid, 'uuid');
            $data = $results;
            $detail = $this->getQuoteDetailObject($quoteType, $quoteModel->id);
            $data['document_type_code'] = QuoteDocumentsEnum::SCRDOC;
            $data['pdf_name'] = 'Riskscore_'.$pdfName.'.pdf';
            $data['quote_uuid'] = $quote->uuid;
            $kycLogs = AML::where([
                'quote_request_id' => $quoteModel->id,
            ])->where('decision', '!=', AMLDecisionStatusEnum::RYU)->orderBy('created_at', 'desc')->get();
            $pdf = PDF::loadView('pdf.risk_score_document', compact('quoteModel', 'detail', 'kycLogs', 'quoteType', 'data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();

            app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quoteModel, true, false);
        }
    }

    public function hasAtleastOneStatusPolicyIssued($record): bool
    {
        if (
            isset($record->quote_status_id) && in_array($record->quote_status_id, [
                QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::PolicySentToCustomer,
                QuoteStatusEnum::PolicyBooked,
                QuoteStatusEnum::CancellationPending,
                QuoteStatusEnum::PolicyCancelled,
                QuoteStatusEnum::PolicyCancelledReissued,
            ]) ||
            $record?->insly_migrated || $record?->insly_id ||
            (is_object($record) && property_exists($record, 'quoteDetail') && $record->quoteDetail?->insly_id) ||
            $record?->source == LeadSourceEnum::RENEWAL_UPLOAD
        ) {
            return true;
        }

        return false;
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
