<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Repositories\ActivityRepository;
use App\Traits\GetUserTreeTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Response;
use Inertia\ResponseFactory;

class ActivityController extends Controller
{
    use GetUserTreeTrait;

    /**
     * @return Response|ResponseFactory
     */
    public function index(Request $request)
    {
        // Validate date inputs upfront with proper error handling
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        // Validate all fields containing "date" in their name
        foreach ($request->all() as $key => $value) {
            if (is_string($key) && str_contains(strtolower($key), 'date') && ! in_array($key, ['date_from', 'date_to'])) {
                $validator->addRules([$key => 'nullable|date']);
            }
        }

        $advisors = [];
        $activities = [];
        $totalActivities = 0;

        // Only fetch data if validation passes
        if (! $validator->fails()) {
            $advisorsIds = DB::table('user_manager')->where('manager_id', Auth::user()->id)->get()->pluck('user_id')->toArray();
            $advisors = DB::table('users')->whereIn('id', $advisorsIds)->get();
            $activities = ActivityRepository::getData();
            $totalActivities = ActivityRepository::countActivities();
        }

        $cannotUseAssignee = ! Auth::user()->can(PermissionsEnum::ActivitiesAssignedToView);

        return inertia('Activities/Index', [
            'activities' => $activities,
            'advisors' => $advisors,
            'cannotUseAssignee' => $cannotUseAssignee,
            'totalActivities' => $totalActivities,
            'errors' => $validator->errors()->toArray(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Activities/Form', []);
    }

    /**
     * @return RedirectResponse
     */
    public function store(ActivityRequest $request)
    {
        ActivityRepository::create($request->validated());

        return back()->with('message', 'Activity created successfully');
    }

    /**
     * @return RedirectResponse
     */
    public function update($id, ActivityRequest $request)
    {
        ActivityRepository::update($id, $request->validated());

        return back()->with('message', 'Activity updated successfully');
    }

    /**
     * @return RedirectResponse
     */
    public function updateStatus($id)
    {
        $activity = ActivityRepository::where('id', $id)->firstOrFail();
        $activity->update(['status' => 1]);

        return back()->with('message', 'Activity status updated successfully');
    }

    /**
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $activity = ActivityRepository::findOrFail($id);
        if ($activity->user_id) {
            $activity->delete();

            return back()->with('message', 'Activity status updated successfully');
        } else {
            return back()->with('message', 'System Generated Activity cannot be deleted');
        }

    }
}
