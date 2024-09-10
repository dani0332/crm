<?php

namespace App\Http\Controllers\API;

use App\Enums\ActivityTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityApiRequest;
use App\Http\Requests\ActivityGetApiRequest;
use App\Services\ActivitiesService;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function createActivity(ActivityApiRequest $request)
    {
        return app(ActivitiesService::class)->createActivityApi($request->entityUId, $request->quoteTypeId, $request->activityType, $request->title, $request->description, $request->dueDate);
    }
    public function getActivity(ActivityGetApiRequest $request)
    {
        return app(ActivitiesService::class)->getActivity($request->entityUId);
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

        $query = DB::table('activity_notification_logs')
            ->join('activities', 'activities.id', '=', 'activity_notification_logs.activity_id')
            ->selectRaw('
            SUM(CASE WHEN notification_type = ? THEN 1 ELSE 0 END) as pendingCallback,
            SUM(CASE WHEN notification_type = ? THEN 1 ELSE 0 END) as pendingWhatsapp
        ', [ActivityTypeEnum::CALL_BACK, ActivityTypeEnum::WHATS_APP])
            ->where('activities.status', 0)
            ->where('activity_notification_logs.advisor_id', $userId)
            ->first();

        return [
            'pendingCallback' => $query->pendingCallback ?? 0,
            'pendingWhatsapp' => $query->pendingWhatsapp ?? 0,
        ];
    }

}
