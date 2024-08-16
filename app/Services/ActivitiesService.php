<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\Activities;
use App\Models\QuoteStatus;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivitiesService extends BaseService
{
    use GetUserTreeTrait;

    protected $helperService;

    public function __construct(HelperService $helperService)
    {
        $this->helperService = $helperService;
    }

    public function getActivityById($id)
    {
        return Activities::where('id', $id)->first();
    }

    public function getActivityByUUID($uuid)
    {
        return Activities::where('uuid', $uuid)->first();
    }

    public function getActivityByLeadId($id, $type)
    {
        return Activities::with(['quoteStatus'])->where('quote_request_id', $id)->where('quote_type_id', $this->getQuoteTypeId($type))->orderBy('created_at', 'desc')->get();
    }

    public function getGridData(Request $request)
    {
        $activities = $this->getAllActivitiesBasedOnUser();

        if ($request->period == null) {
            $request->period = 'today';
        }
        if (isset($request->period) && $request->period != '') {
            switch ($request->period) {
                case 'custom':
                    $activities = $activities->whereBetween('due_date', [$request->startDate, $request->endDate]);
                    break;
                case 'overdue':
                    $activities = $activities->where('due_date', '<', Carbon::now()->toDateTimeString());
                    break;
                case 'tomorrow':
                    $activities = $activities->whereBetween('due_date', [Carbon::tomorrow()->startOfDay()->toDateTimeString(), Carbon::tomorrow()->endOfDay()->toDateTimeString()]);
                    break;
                case 'today':
                    $activities = $activities->whereBetween('due_date', [Carbon::today()->startOfDay()->toDateTimeString(), Carbon::today()->endOfDay()->toDateTimeString()]);
                    break;
                case 'yesterday':
                    $activities = $activities->whereBetween('due_date', [Carbon::yesterday()->startOfDay()->toDateTimeString(), Carbon::yesterday()->endOfDay()->toDateTimeString()]);
                    break;
                case 'this_week':
                    $activities = $activities->whereBetween('due_date', [Carbon::now()->startOfWeek()->startOfDay()->toDateTimeString(), Carbon::now()->endOfWeek()->endOfDay()->toDateTimeString()]);
                    break;
                case 'this_month':
                    $activities = $activities->whereBetween('due_date', [Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString(), Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString()]);
                    break;
                default:
                    break;
            }
        }
        if (isset($request->assignee_id) && $request->assignee_id != '') {
            $activities = $activities->where('assignee_id', $request->assignee_id);
        }
        if (isset($request->status) && $request->status != '') {
            $activities = $activities->where('status', $request->status);
        }
        $rawActivities = $activities->select(
            'activities.id as id',
            'client_name', 'client_email', 'quote_request_id', 'quote_uuid', 'quote_type_id', 'due_date', 'name', 'title', 'status', 'assignee_id', 'uuid'
        )->get()->sortBy('status');

        foreach ($rawActivities as $activity) {
            $dateFormat = config('constants.DATETIME_DISPLAY_FORMAT');
            $dueDate = Carbon::createFromFormat($dateFormat, $activity->due_date);
            $now = now()->format($dateFormat);
            $activity->is_overdue = $dueDate->lt($now);
        }

        return $rawActivities;
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            if ($isStartOfDay) {
                return Carbon::createFromFormat('Y-m-d', $date)->startOfDay()->toDateTimeString();
            } else {
                return Carbon::createFromFormat('Y-m-d', $date)->endOfDay()->toDateTimeString();
            }
        }
    }

    public function createActivity(Request $request, $record)
    {
        $activity = new Activities();
        $activity->uuid = $this->helperService->generateUUID();
        if (isset($record) && $record != '') {
            $activity->client_name = $record->first_name.' '.$record->last_name;
            $activity->quote_request_id = isset($request->entityId) ? $request->entityId : $request->leadId;
            $activity->quote_type_id = $this->getQuoteTypeId(strtolower($request->modelType));
            $activity->quote_uuid = isset($request->entityUId) ? $request->entityUId : $request->quote_uuid;
        }
        if (isset($request->leadStatus)) {
            $quoteStatus = QuoteStatus::select('text')->where('id', $request->leadStatus)->first();
            $request->title = $quoteStatus->text;
        }
        $nextFollowupDate = isset($request->next_followup_date) ? Carbon::parse($request->next_followup_date)->format('Y-m-d H:i:s') : null;
        $dueDate = isset($request->due_date) ? Carbon::parse($request->due_date)->format('Y-m-d H:i:s') : null;
        $request->assignee_id = isset($request->assigned_to_user_id) ? $request->assigned_to_user_id : $request->assignee_id;
        $activity->due_date = isset($request->due_date) ? $dueDate : $nextFollowupDate;
        $activity->assignee_id = isset($request->assignee_id) ? $request->assignee_id : auth()->user()->id;
        $activity->description = isset($request->description) ? $request->description : $request->notes;
        $activity->title = $request->title;
        $activity->created_at = Carbon::now();
        $activity->updated_at = Carbon::now();
        $activity->quote_status_id = $record?->quote_status_id ?? null;
        $activity->source = LeadSourceEnum::IMCRM;
        $activity->save();

        return $activity;
    }

    public function getQuoteTypeId($modelType)
    {
        $quoteTypeIds = [
            quoteTypeCode::Car => QuoteTypeId::Car,
            quoteTypeCode::Home => QuoteTypeId::Home,
            quoteTypeCode::Life => QuoteTypeId::Life,
            quoteTypeCode::Travel => QuoteTypeId::Travel,
            quoteTypeCode::Health => QuoteTypeId::Health,
            quoteTypeCode::Business => QuoteTypeId::Business,
            quoteTypeCode::Pet => QuoteTypeId::Pet,
            quoteTypeCode::Cycle => QuoteTypeId::Cycle,
            quoteTypeCode::Jetski => QuoteTypeId::Jetski,
            quoteTypeCode::Bike => QuoteTypeId::Bike,
            quoteTypeCode::Yacht => QuoteTypeId::Yacht,
            quoteTypeCode::GroupMedical => QuoteTypeId::Business,
        ];

        $modelType = ucwords($modelType);

        return $quoteTypeIds[$modelType] ?? null;
    }

    public function filterActivitiesByPeriod($activities, $period)
    {
        switch ($period) {
            case 'today':
                $activities = $activities->whereBetween('created_at', [Carbon::today()->startOfDay()->toDateTimeString(), Carbon::today()->endOfDay()->toDateTimeString()]);
                break;
            case 'yesterday':
                $activities = $activities->where('created_at', Carbon::yesterday())->orWhere('updated_at', Carbon::yesterday());
                break;
            case 'this_week':
                $activities = $activities->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->orWhereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'this_month':
                $activities = $activities->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->orWhereBetween('updated_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                break;
            default:
                break;
        }
    }

    public function getAllActivitiesBasedOnUser()
    {
        $subOrdinateIds = $this->walkTree(Auth::user()->id);
        array_push($subOrdinateIds, Auth::user()->id);
        $activities = Activities::leftJoin('users', 'users.id', 'activities.assignee_id')->whereIn('assignee_id', $subOrdinateIds);

        return $activities;
    }

    public function createApiActivity(Request $request, $record, $modelType)
    {
        $activity = new Activities();
        $activity->uuid = $this->helperService->generateUUID();
        if (isset($record) && $record != '') {
            $activity->client_name = $record->first_name.' '.$record->last_name;
            $activity->quote_request_id = isset($record->id) ? $record->id : $request->leadId;
            $activity->quote_type_id = $this->getQuoteTypeId(strtolower($modelType->code));
            $activity->quote_uuid = isset($request->entityUId) ? $request->entityUId : $request->quote_uuid;
        }
        if (isset($request->leadStatus)) {
            $quoteStatus = QuoteStatus::select('text')->where('id', $request->leadStatus)->first();
            $request->title = $quoteStatus->text;
        }
        $nextFollowupDate = isset($request->next_followup_date) ? Carbon::parse($request->next_followup_date)->format('Y-m-d H:i:s') : null;
        $dueDate = isset($request->due_date) ? Carbon::parse($request->due_date)->format('Y-m-d H:i:s') : null;
        $activity->due_date = isset($request->due_date) ? $dueDate : $nextFollowupDate;
        $activity->assignee_id = isset($record->advisor_id) ? $record->advisor_id : null;
        $activity->description = isset($request->description) ? $request->description : $request->notes;
        $activity->title = $request->title;
        $activity->created_at = Carbon::now();
        $activity->updated_at = Carbon::now();
        $activity->quote_status_id = $record?->quote_status_id ?? null;
        $activity->source = LeadSourceEnum::INSTANT_ALFRED;
        $activity->save();

        return $activity;
    }
}
