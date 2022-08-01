<?php

namespace App\Http\Controllers;

use App\Enums\HealthTeamType;
use App\Enums\quoteTypeCode;
use App\Models\Activities;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\HelperService;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivitesController extends Controller
{
    protected $activitesService;
    protected $crudService;
    protected $helperService;

    public function __construct(ActivitiesService $activitesService, CRUDService $crudService, HelperService $helperService)
    {
        $this->activitesService = $activitesService;
        $this->crudService = $crudService;
        $this->helperService = $helperService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $advisors = [];
        $subOrdinates = $this->helperService->walkTree(Auth::user()->id);
        foreach ($subOrdinates as $subOrdinate) {
            $user = User::where('id', $subOrdinate)->first();
            if ($user->hasAnyRole(['CAR_ADVISOR', 'HEALTH_ADVISOR', 'TRAVEL_ADVISOR', 'HOME_ADVISOR', 'LIFE_ADVISOR', 'PET_ADVISOR',
                'BUSINESS_ADVISOR', 'CORPLINE_ADVISOR', 'RM_ADVISOR', 'GM_ADVISOR', 'EBP_ADVISOR', ])) {
                array_push($advisors, $user);
            }
        }
        if ($request->ajax()) {
            $activites = $this->activitesService->getGridData($request);

            return DataTables::of($activites)
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
    public function store(Request $request)
    {
        $record = '';
        if (isset($request->entityId)) {
            $record = $this->crudService->getEntity($request->modelType, $request->entityUId);
        }
        $this->activitesService->createActivity($request, $record);
        if (isset($request->isActivityView)) {
            return redirect()->to('/activities/')->with('success', ' Activity has been Created');
        } else {
            return redirect()->to('/quotes/'.strtolower($request->parentType).'/'.$request->entityUId)->with('success', ' Activity has been Created');
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
        $record = $this->activitesService->getActivityByUUID($id);
        $record->assignee_name = User::where('id', $record->assignee_id)->first()->name;

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
        $record = $this->activitesService->getActivityByUUID($id);
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
    public function update(Request $request, $id)
    {
        $record = $this->activitesService->getActivityByUUID($id);
        if (isset($request->assignee_id) && $request->assignee_id != '') {
            $record->assignee_id = $request->assignee_id;
        }
        $record->title = $request->title;
        $record->description = $request->description;
        $record->due_date = $request->due_date;
        $record->save();
        if (isset($request->fromLeadView) && $request->fromLeadView == 1) {
            return redirect('/quotes/'.$this->getQuoteTypeNameFromId($request->quoteType).'/'.$request->quote_uuid)->with('success', 'Activity updated successfully');
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
        Activities::where('id', $id)->delete();
        if (isset($request->isLeadView) && $request->isLeadView == 1) {
            return redirect('/quotes/'.$request->quoteType.'/'.$request->quote_uuid)->with('success', 'Activity deleted successfully');
        }

        return redirect('/activities')->with('success', 'Activity deleted successfully');
    }

    public function updateStatus(Request $request)
    {
        $record = $this->activitesService->getActivityById($request->activity_id);
        $record->status = $record->status == 0 ? 1 : 0;
        $record->save();
    }

    public function getEditView(Request $request)
    {
        $record = $this->activitesService->getActivityById($request->activity_id);
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
        }

        return $quotetypename;
    }
}
