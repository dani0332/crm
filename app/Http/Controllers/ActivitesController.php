<?php

namespace App\Http\Controllers;

use App\Enums\HealthTeamType;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Http\Requests\ActivitiesRequest;
use App\Models\Activities;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivitesController extends Controller
{
    use GetUserTreeTrait;

    protected $activitiesService;
    protected $crudService;

    public function __construct(ActivitiesService $activitiesService, CRUDService $crudService)
    {
        $this->activitiesService = $activitiesService;
        $this->crudService = $crudService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $advisors = [];
        $advisors = User::whereIn('id', $this->walkTree(Auth::user()->id))->get();
        if ($request->ajax()) {
            $activities = $this->activitiesService->getGridData($request);

            return DataTables::of($activities)
                ->addIndexColumn()
                ->make(true);
        }

        return view('activities.view', compact('advisors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ActivitiesRequest $request)
    {
        $record = '';
        $quoteType = $request->parentType;
        if (isset($request->entityId)) {
            $record = $this->crudService->getEntity($request->modelType, $request->entityUId);
        }
        $this->activitiesService->createActivity($request, $record);
        if (isset($request->is_car_revival)) {
            $quoteType = quoteTypeCode::Car_Revival;
        }

        if (isset($request->isActivityView)) {
            return redirect()->to('/activities/')->with('success', ' Activity has been Created');
        } else {
            return redirect()->to('/quotes/'.strtolower($quoteType).'/'.$request->entityUId)->with('success', ' Activity has been Created');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = $this->activitiesService->getActivityByUUID($id);

        if ($record) {
            $record->assignee_name = User::where('id', $record->assignee_id)->first()->name;
        } else {
            abort(404);
        }

        return view('activities.show', compact('record'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = $this->activitiesService->getActivityByUUID($id);
        $record->assignee_name = User::where('id', $record->assignee_id)->first()->name;
        $quotetypename = $this->getQuoteTypeNameFromId($record->quote_type_id);
        $advisors = $this->getAdvisorsForActivity($quotetypename, $record->health_team_type);

        return view('activities.edit', compact('record', 'advisors'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ActivitiesRequest $request, $id)
    {
        $record = $this->activitiesService->getActivityByUUID($id);
        if (isset($request->assignee_id) && $request->assignee_id != '') {
            $record->assignee_id = $request->assignee_id;
        }
        $record->title = $request->title;
        $record->description = $request->description;
        $record->due_date = Carbon::parse($request->due_date)->format('Y-m-d H:i:s');
        $record->save();
        if (isset($request->fromLeadView) && $request->fromLeadView == 1) {
            return redirect('/quotes/'.$this->getQuoteTypeNameFromId($request->quoteType).'/'.$request->quote_uuid)->with('success', 'Activity updated successfully');
        }

        $types = [
            QuoteTypeId::Health,
            QuoteTypeId::Home,
            QuoteTypeId::Travel,
            QuoteTypeId::Business,
            QuoteTypeId::Life,
            QuoteTypeId::Car,
        ];
        if ((isset($request->quoteType) && in_array($request->quoteType, $types)) || isset($request->isInertia)) {
            return redirect()->back();
        }

        return redirect('/activities')->with('success', 'Activity updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // ActivityNotificationLogs::where('activity_id', $id)->delete();
        Activities::where('id', $id)->delete();
        if (isset($request->isLeadView) && $request->isLeadView == 1) {
            return redirect('/quotes/'.$request->quoteType.'/'.$request->quote_uuid)->with('success', 'Activity deleted successfully');
        }

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back();
        }

        return redirect('/activities')->with('success', 'Activity deleted successfully');
    }

    public function updateStatus(Request $request)
    {
        $record = $this->activitiesService->getActivityById($request->activity_id);
        if (isset($record)) {
            $record->status = $record->status == 0 ? 1 : 0;
            $record->save();
        }
    }

    public function getEditView(Request $request)
    {
        $record = $this->activitiesService->getActivityById($request->activity_id);
        $advisors = [];
        if (isset($request->quote_uuid)) {
            $quoteRecord = $this->crudService->getEntityByUUID($request->quote_uuid, $request->quoteType);
            $advisors = $this->getAdvisorsForActivity($request->quoteType, isset($quoteRecord->health_team_type) ? $quoteRecord->health_team_type : '');
        }

        return view('activities.edit', compact('record', 'advisors'));
    }

    public function getAdvisorsForActivity($quoteType, $healthTeamType)
    {
        $advisors = [];
        if (strtolower($quoteType) == strtolower(quoteTypeCode::Health) && ($healthTeamType == HealthTeamType::EBP ||
            $healthTeamType == HealthTeamType::RM_NB || $healthTeamType == HealthTeamType::RM_SPEED)) {
            $advisors = $this->crudService->getEBPAndRMAdvisors();
        } elseif (strtolower($quoteType) == 'business') {
            $advisors = $this->crudService->getRMAndBusinessAdvisors();
        } else {
            $advisors = $this->crudService->getAdvisorsByModelType($quoteType);
        }

        return $advisors;
    }

    public function getQuoteTypeNameFromId($quoteTypeId)
    {
        $quotetypename = '';
        switch ($quoteTypeId) {
            case 1:
                $quotetypename = 'car';
                break;
            case 2:
                $quotetypename = 'home';
                break;
            case 3:
                $quotetypename = 'health';
                break;
            case 4:
                $quotetypename = 'life';
                break;
            case 5:
                $quotetypename = 'business';
                break;
            case 6:
                $quotetypename = 'bike';
                break;
            case 7:
                $quotetypename = 'yacht';
                break;
            case 8:
                $quotetypename = 'travel';
                break;
            case 9:
                $quotetypename = 'pet';
                break;
            default:
                break;
        }

        return $quotetypename;
    }
}
