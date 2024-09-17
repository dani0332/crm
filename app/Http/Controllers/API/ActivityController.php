<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityApiRequest;
use App\Http\Requests\ActivityGetApiRequest;
use App\Services\ActivitiesService;

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

}
