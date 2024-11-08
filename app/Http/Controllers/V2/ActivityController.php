<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Repositories\ActivityRepository;
use App\Traits\GetUserTreeTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    use GetUserTreeTrait;

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $advisors = [];
        $advisorsIds = DB::table('user_manager')->where('manager_id', Auth::user()->id)->get()->pluck('user_id')->toArray();
        $advisors = DB::table('users')->whereIn('id', $advisorsIds)->get();
        $activities = ActivityRepository::getData();
        $totalActivities = ActivityRepository::countActivities();
        $cannotUseAssignee = auth()->user()->cannot(PermissionsEnum::ActivitiesAssignedToView);

        return inertia('Activities/Index', [
            'activities' => $activities,
            'advisors' => $advisors,
            'cannotUseAssignee' => $cannotUseAssignee,
            'totalActivities' => $totalActivities,
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ActivityRequest $request)
    {
        ActivityRepository::create($request->validated());

        return back()->with('message', 'Activity created successfully');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id, ActivityRequest $request)
    {
        ActivityRepository::update($id, $request->validated());

        return back()->with('message', 'Activity updated successfully');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus($id)
    {
        $activity = ActivityRepository::where('id', $id)->firstOrFail();
        $activity->update(['status' => 1]);

        return back()->with('message', 'Activity status updated successfully');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
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
