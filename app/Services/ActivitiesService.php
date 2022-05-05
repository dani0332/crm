<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\Activities;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Carbon\Carbon;

class ActivitiesService extends BaseService
{
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
        return Activities::where('quote_request_id', $id)->where('quote_type_id', $this->getQuoteTypeId($type))->orderBy('created_at', 'desc')->get();
    }

    public function getGridData(Request $request)
    {
        $activities = $this->getAllActivitesBasedOnUser();
        
        if($request->period == null){
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
                    $activities = $activities;
                    break;
            }
        }
        if (isset($request->assignee_id) && $request->assignee_id != '') {
            $activities = $activities->where('assignee_id', $request->assignee_id);
        }
        $rawActivities = $activities->get();
        foreach($rawActivities as $act)
        {
            $act->assignee_name = User::where('id', $act->assignee_id)->first()->name;
            
        }
        return $rawActivities->orderBy('created_at', 'desc');
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
        if(isset($record) && $record != '') {
            $activity->client_name = $record->first_name . ' ' . $record->last_name;	
            $activity->quote_request_id = $request->entityId;
            $activity->quote_type_id = $this->getQuoteTypeId($request->modelType);
            $activity->quote_uuid = $request->entityUId;
        }
        $activity->due_date = $request->due_date;
        $activity->assignee_id = isset($request->assignee_id) ? $request->assignee_id : Auth::user()->id;
        $activity->description = $request->description;
        $activity->title = $request->title;
        $activity->created_at = Carbon::now();
        $activity->updated_at = Carbon::now();
        $activity->save();
        return $activity;
    }

    public function getQuoteTypeId($modelType)
    {
        $quoteTypeId = null;
        switch($modelType)
        {
            case 'car':
                $quoteTypeId = QuoteTypeId::Car;
                break;
            case 'home':
                $quoteTypeId = QuoteTypeId::Home;
                break;
            case 'life':
                $quoteTypeId = QuoteTypeId::Life;
                break;
            case 'travel':
                $quoteTypeId = QuoteTypeId::Travel;
                break;
            case 'health':
                $quoteTypeId = QuoteTypeId::Health;
                break;
            case 'business':
                $quoteTypeId = QuoteTypeId::Business;
                break;
            default :
                break;
        }
        return $quoteTypeId;
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
                $activities = $activities;
                break;
        }
    }

    public function getAllActivitesBasedOnUser()
    {
        $subOrdinateIds = $this->helperService->walkTree(Auth::user()->id);
        array_push($subOrdinateIds, Auth::user()->id);
        $activites = Activities::whereIn('assignee_id', $subOrdinateIds);
        
        return $activites;
    }

}
