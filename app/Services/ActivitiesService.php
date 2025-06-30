<?php

namespace App\Services;

use App\Enums\ActivityTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Events\CallBackNotifications;
use App\Models\Activities;
use App\Models\PersonalQuote;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $activity = new Activities;
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
        $activity->user_id = auth()->user()->id;
        $activity->reminders_sent = 0;
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
            quoteTypeCode::CompanyCar => QuoteTypeId::CompanyCar,
            quoteTypeCode::SAVINGS => QuoteTypeId::Savings,
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

        return Activities::leftJoin('users', 'users.id', 'activities.assignee_id')->whereIn('assignee_id', $subOrdinateIds);
    }

    /**
     * Create an activity via API for a given entity and quote type.
     *
     * @param  string  $entityUId
     * @param  int|null  $quoteTypeId
     * @param  string  $activityType
     * @param  string  $title
     * @param  string  $description
     * @param  string  $dueDate
     * @return \Illuminate\Http\JsonResponse
     */
    public function createActivityApi($entityUId, $quoteTypeId, $activityType, $title, $description, $dueDate)
    {
        $modelType = $this->getModelType($quoteTypeId);
        $record = $this->getRecord($entityUId, $modelType);
        $existingActivity = $this->getExistingActivity($entityUId);
        $systemUser = $this->getSystemUser();

        if ($existingActivity) {
            $reassignResult = $this->handleExistingActivity($existingActivity, $systemUser, $record);
            if ($reassignResult) {
                return $reassignResult;
            }

            return response()->json([
                'message' => 'An existing activity was found. Please Mark Done the current activity before creating a new one.',
            ], 409);
        }

        $this->createApiActivity(
            $entityUId,
            $activityType,
            $title,
            $description,
            $dueDate,
            $record,
            $modelType,
            $systemUser?->id
        );

        $url = $this->buildActivityUrl($modelType, $record);
        if ($record->advisor_id) {
            $this->triggerNotification($activityType, $record, $url);
        }

        return response()->json(['message' => 'Activity has been Created'], 200);
    }

    /**
     * Get the model type from quoteTypeId.
     */
    private function getModelType($quoteTypeId)
    {
        return $quoteTypeId
            ? QuoteType::select('code')->find($quoteTypeId)
            : null;
    }

    /**
     * Get the record for the entity.
     */
    private function getRecord($entityUId, $modelType)
    {
        if ($entityUId && $modelType && ! checkPersonalQuotes($modelType->code)) {
            return app(CRUDService::class)->getEntity($modelType->code, $entityUId);
        }

        return PersonalQuote::where('uuid', $entityUId)->first();
    }

    /**
     * Get any existing open activity for the entity.
     */
    private function getExistingActivity($entityUId)
    {
        return Activities::where('quote_uuid', $entityUId)
            ->where('status', 0)
            ->where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->first();
    }

    /**
     * Get the system user.
     */
    private function getSystemUser()
    {
        return User::where('email', 'system@insurancemarket.ae')->first();
    }

    /**
     * Handle logic for an existing activity (reassignment or error response).
     * Returns a response if handled, or null to continue.
     */
    private function handleExistingActivity($existingActivity, $systemUser, $record)
    {
        if ($existingActivity->assignee_id == $systemUser?->id) {
            if (! empty($record->advisor_id)) {
                $existingActivity->assignee_id = $record->advisor_id;
                $existingActivity->save();

                return response()->json([
                    'message' => 'Activity has been Reassigned to Advisor '.$record->advisor_id,
                ], 200);
            }

            return response()->json([
                'message' => 'No advisor has been assigned to this lead.',
            ], 200);
        }

        return null;
    }

    /**
     * Build the URL for the activity notification.
     */
    private function buildActivityUrl($modelType, $record)
    {
        $quoteTypeCode = strtolower($modelType->code);
        if ($modelType->code == QuoteTypeCode::Business) {
            $path = "quotes/business/{$record->uuid}";
        } elseif (checkPersonalQuotes($modelType->code)) {
            $path = "personal-quotes/{$quoteTypeCode}/{$record->uuid}";
        } else {
            $path = "quotes/{$quoteTypeCode}/{$record->uuid}";
        }

        return url('/')."/$path";
    }

    /**
     * Trigger the appropriate notification event.
     */
    private function triggerNotification($activityType, $record, $url)
    {
        $isCallback = strtoupper($activityType) === ActivityTypeEnum::CALL_BACK;
        $notificationTitle = $isCallback
            ? 'InstantAlfred Callback Request'
            : 'InstantAlfred WhatsApp Request';
        $notificationMessage = $isCallback
            ? 'Urgent callback request for '
            : 'Urgent Whatsapp request for ';
        LoggerService::info(
            'InstantAlfred '.($isCallback ? 'CallBack' : 'Whatsapp').
            ' Notification Trigger to Advisor '.$record->advisor_id.' And Lead Code is '.$record->code
        );
        event(new CallBackNotifications(
            $record->uuid,
            $record->advisor_id,
            $url,
            $record->code,
            $notificationTitle,
            $notificationMessage
        ));
    }

    public function getActivity($entityUId)
    {
        if (empty($entityUId)) {
            return response()->json(['message' => 'Entity UUID Not Found'], 404);
        }

        $activity = Activities::where('quote_uuid', $entityUId)
            ->Where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->latest()->first();

        if (! $activity) {
            return response()->json(['message' => 'Activity Not Found'], 404);
        }

        return response()->json([
            'message' => 'Activity Found',
            'activity' => $activity,
        ], 200);
    }

    public function createApiActivity($entityUId, $activityType, $title, $description, $dueDate, $record, $modelType, $systemUserId)
    {
        $activity = new Activities;
        $activity->uuid = $this->helperService->generateUUID();
        if (isset($record) && $record != '') {
            $activity->client_name = $record->first_name.' '.$record->last_name;
            $activity->quote_request_id = isset($record->id) ? $record->id : null;
            $activity->quote_type_id = $this->getQuoteTypeId(strtolower($modelType->code));
            $activity->quote_uuid = isset($entityUId) ? $entityUId : $record->uuid;
        }
        $dueDate = isset($dueDate) ? Carbon::parse($dueDate)->format('Y-m-d H:i:s') : null;
        $activity->due_date = isset($dueDate) ? $dueDate : Carbon::now();
        $activity->assignee_id = isset($record->advisor_id) ? $record->advisor_id : $systemUserId;
        $activity->description = isset($description) ? $description : null;
        $activity->title = $title;
        $activity->quote_status_id = $record?->quote_status_id ?? null;
        $activity->source = LeadSourceEnum::INSTANT_ALFRED;
        $activity->activity_type = $activityType ? strtoupper($activityType) : null;
        $activity->reminders_sent = 1;
        $activity->save();

        return $activity;
    }
    public function getPendingActivityCount()
    {
        if (! auth()->check()) {
            return [
                'pendingCallback' => 0,
                'pendingWhatsapp' => 0,
            ];
        }

        $userId = auth()->user()->id;

        return Cache::remember("pending-activity-count-{$userId}", now()->addMinutes(5), function () use ($userId) {
            $query = DB::table('activities')
                ->selectRaw('
                SUM(CASE WHEN activity_type = ? THEN reminders_sent ELSE 0 END) as pendingCallback,
                SUM(CASE WHEN activity_type = ? THEN reminders_sent ELSE 0 END) as pendingWhatsapp
            ', [ActivityTypeEnum::CALL_BACK, ActivityTypeEnum::WHATS_APP])
                ->where('activities.status', 0)
                ->where('activities.assignee_id', $userId)
                ->first();

            return [
                'pendingCallback' => $query->pendingCallback ?? 0,
                'pendingWhatsapp' => $query->pendingWhatsapp ?? 0,
            ];
        });
    }
}
