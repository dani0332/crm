<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Repositories\ActivityRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Activities;
use App\Services\ActivitiesService;
use App\Models\User;
use App\Traits\GetUserTreeTrait;


class ActivityController extends Controller
{
    
    use GetUserTreeTrait;
    protected $activitiesService;
   

    public function __construct(ActivitiesService $activitiesService)
    {
        $this->activitiesService = $activitiesService;
        
    }

     /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        //$activities = ActivityRepository::simplePaginate()->withQueryString();
        
        $advisors = [];
        $advisors = User::whereIn('id', $this->walkTree(Auth::user()->id))->get();
        $activities = ActivityRepository::getData();
        // echo "<pre>"; print_r($advisors); exit;
        return inertia('Activities/Index', [
            'activities' => $activities,
            'advisors' => $advisors
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
        $activity->delete();

        return back()->with('message', 'Activity status updated successfully');
    }
}
