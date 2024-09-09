<?php

namespace App\Http\Controllers\API;

use App\Enums\ActivityTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityApiRequest;
use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    public $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    public function createActivity(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ActivityApiRequest::rules(),
            ActivityApiRequest::messages()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        return $this->apiService->createActivity($request);
    }
    public function getActivity(Request $request)
    {
        return $this->apiService->getActivity($request);
    }
    public function getPendingActivityCount()
    {
        if (! Auth::check()) {
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
