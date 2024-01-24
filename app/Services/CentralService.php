<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\Activities;
use App\Models\ActivitySchedule;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Repositories\PersonalQuoteRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;

class CentralService
{
    use GenericQueriesAllLobs, TeamHierarchyTrait;

    public function duplicateAllowedLobsList($quoteType, $leadCode)
    {
        $allowedLeadTypes = [
            quoteTypeCode::Home,
            quoteTypeCode::Health,
            quoteTypeCode::Life,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical,
            quoteTypeCode::Travel,
            quoteTypeCode::Car,
            quoteTypeCode::Pet,
            quoteTypeCode::Cycle,
        ];

        if (strtolower($quoteType) == strtolower(quoteTypeCode::Business)) {
            $modelType = quoteTypeCode::CORPLINE;
        }
        $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
            return $item;
        });
        foreach ($allowedLeadTypes as $leadType) {
            $leadType = strtolower($leadType);

            if ($leadType == strtolower(quoteTypeCode::CORPLINE) || $leadType = strtolower(quoteTypeCode::GroupMedical)) {
                $leadType = quoteTypeCode::Business;
            }

            $repository = $this->getRepositoryObject(ucfirst($leadType));

            $duplicateRecord = $repository::where('code', $leadCode)->first();
            if ($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
                    return $item;
                });
            }
        }

        return $allowedLeadTypes;
    }

    public function saveDuplicateLeads($data)
    {
        $lobTeams = $data['lob_team'];
        $parentType = $data['parentType'];
        $entityId = $data['entityId'];

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = quoteTypeCode::Business;
        }

        $repository = $this->getRepositoryObject($parentType);
        $parentRecord = $repository::where('id', $entityId)->first();

        if (! empty($data['lob_team_sub_selection'])) {
            $parentRecord['enquiryType'] = $data['lob_team_sub_selection'];
        } else {
            $parentRecord['enquiryType'] = 'record_only';
        }

        if (! empty($lobTeams)) {
            $dataArr = [
                'firstName' => $parentRecord->first_name,
                'lastName' => $parentRecord->last_name,
                'email' => $parentRecord->email,
                'mobileNo' => $parentRecord->mobile_no,
                'referenceUrl' => config('constants.APP_URL'),
                'source' => config('constants.SOURCE_NAME'),
            ];

            $resp = [];
            foreach ($lobTeams as $lob) {
                if (strtolower($lob) == strtolower(quoteTypeCode::CORPLINE) || strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                    $lob = quoteTypeCode::Business;
                    $dataArr['businessTypeOfInsuranceId'] = $parentRecord->business_type_of_insurance_id ?? '';

                    if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                        $dataArr['businessTypeOfInsuranceId'] = QuoteTypeId::Business;
                    }
                }

                $repository = $this->getRepositoryObject(ucfirst($lob));

                if (! class_exists($repository)) {
                    return false;
                }

                $response = in_array(ucfirst($lob), newUi()) ?
                    ((method_exists($repository, 'fetchCreateDuplicate') && ! checkPersonalQuotes(ucfirst($lob))) ? $repository::createDuplicate($dataArr) : PersonalQuoteRepository::createDuplicate($dataArr, ucfirst($lob))) :
                    Capi::request('/api/v1-save-'.strtolower($lob).'-quote', 'post', $dataArr);

                if (empty($response) || (isset($response->message) && str_contains($response->message, 'Error'))) {
                    $resp['errors'][] = 'Something went wrong while duplicating '.$lob.' quotes';
                } elseif (isset($response->quoteUID) && isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
                    $record = $repository::where('uuid', $response->quoteUID)->first();
                    if ($record) {
                        $update = [
                            'parent_duplicate_quote_id' => $parentRecord->code,
                            'advisor_id' => auth()->user()->id,
                        ];
                        if (strtolower($lob) == strtolower(quoteTypeCode::Health)) {
                            $subTeam = null;
                            if (auth()->user()->subTeam) {
                                $subTeam = auth()->user()->subTeam->name;
                            }
                            $update['health_team_type'] = $subTeam;
                        }
                        $record->update($update);
                    }
                }
            }

            return $resp;
        }
    }

    public function assignLeadToAdvisor($request)
    {
        $leadsIds = $request->assigned_lead_id;
        $personalQuotes = [quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Pet, quoteTypeCode::Yacht];
        Log::info('Leads ids to assign: '.json_encode($leadsIds));

        if (str_starts_with($leadsIds, ',')) {
            $leadsIds = substr($leadsIds, 1);
        }

        $leadsIds = array_map('intval', explode(',', $leadsIds));
        $model = (in_array(ucfirst($request->modelType), $personalQuotes) && in_array(ucfirst($request->modelType), newUi())) ?
            ['parent' => PersonalQuote::class, 'child' => PersonalQuoteDetail::class] :
            ['parent' => (ucfirst($request->modelType).'Quote'), 'child' => (ucfirst($request->modelType).'QuoteRequestDetail')];

        if (! class_exists($model['parent'])) {
            vAbort('Something went wrong');
        }

        return DB::transaction(function () use ($leadsIds, $model, $request, $personalQuotes) {
            foreach ($leadsIds as $leadId) {
                $getQuoteLead = $model['parent']::findOrfail($leadId);
                $getQuoteLead->advisor_id = (int) $request->assigned_advisor_id;
                $getQuoteLead->save();

                $parentFieldName = (in_array(ucfirst($request->modelType), $personalQuotes) && in_array(ucfirst($request->modelType), newUi())) ?
                    'personal_quote_id' : strtolower($request->modelType).'_quote_request_id';

                $model['child']::updateOrCreate(
                    [$parentFieldName => $getQuoteLead->id],
                    ['advisor_assigned_by_id' => auth()->user()->id, 'advisor_assigned_date' => Carbon::now()]
                );
            }
        });
    }

    public function loadAvailablePlans($type, $id)
    {
        $type = ucfirst($type);
        switch ($type) {
            case quoteTypeCode::Car:
                return app(CarQuoteService::class)->getPlans($id);
            case quoteTypeCode::Travel:
                return app(TravelQuoteService::class)->sortedPlansList($id);
            case quoteTypeCode::Health:
                $listQuotePlans = [];
                $quotePlans = app(HealthQuoteService::class)->getQuotePlans($id);
                if (isset($quotePlans->message) && $quotePlans->message != '') {
                    $listQuotePlans = [];
                } else {
                    if (gettype($quotePlans) != 'string') {
                        $listQuotePlans[] = $quotePlans->quote->plans;
                    }
                }

                return $listQuotePlans;
            default:
                return [];
        }
    }

    public function savePlanDetails()
    {

    }

    public function saveAndAssignActivitesToAdvisor($quoteDetails, $quoteTypeId)
    {
        $roles = auth()->user()->roles->pluck('id')->toArray();

        $getActivitySchedule = ActivitySchedule::where([
            'quote_type_id' => $quoteTypeId,
            'quote_status_id' => $quoteDetails->quote_status_id,
        ])
        ->whereIn('role_id', $roles)
        ->whereIn('team_id', $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray())
        ->orderBy('sorting_order')
        ->first();

        if($getActivitySchedule && $quoteDetails->advisor_id) {

            $activity = Activities::create([
                'title' => $getActivitySchedule->name,
                'description' => $getActivitySchedule->description,
                'quote_request_id' => $quoteDetails->id,
                'quote_type_id' => $quoteTypeId,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'assignee_id' => $quoteDetails->advisor_id ?? auth()->user()->id,
                'uuid' => generateUuid(),
                'due_date' => addDaysExcludeWeekend($getActivitySchedule->due_days),
                'client_name' => $quoteDetails->first_name.' '.$quoteDetails->last_name,
                'client_email' => $quoteDetails->email,
                'quote_uuid' => $quoteDetails->uuid,
            ]);

            return $activity;
        }

        return false;
    }

}
