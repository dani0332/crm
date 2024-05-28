<?php

namespace App\Repositories;

use App\Models\Activities;
use App\Traits\GetUserTreeTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityRepository extends BaseRepository
{
    use GetUserTreeTrait;

    public function model()
    {
        return Activities::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
       
        $assigneeIds = [];
        if(Auth::user()->isManagerOrDeputy()){
            $assigneeIds = DB::table('user_manager')->where('manager_id', Auth::user()->id)->get()->pluck('user_id')->toArray();
        }else {
            $assigneeIds = $this->walkTree(Auth::user()->id);
            array_push($assigneeIds, Auth::user()->id);
        }
        // dd($assigneeIds);
        // $subOrdinateIds = $this->walkTree(Auth::user()->id);
        // array_push($subOrdinateIds, Auth::user()->id);

        return $this->with(['assignee'])
            ->whereIn('assignee_id', $assigneeIds)
            ->filter()
            ->orderBy('status')
            ->simplePaginate()
            ->withQueryString();
    }

    /**
     * @return total records
     */
    public function fetchCountActivities()
    {

        $assigneeIds = [];
        if(Auth::user()->isManagerOrDeputy()){
            $assigneeIds = DB::table('user_manager')->where('manager_id', Auth::user()->id)->get()->pluck('user_id')->toArray();
        }else {
            $assigneeIds = $this->walkTree(Auth::user()->id);
            array_push($assigneeIds, Auth::user()->id);
        }

        // $subOrdinateIds = $this->walkTree(Auth::user()->id);
        // array_push($subOrdinateIds, Auth::user()->id);

        return $this->with(['assignee'])
            ->filter()
            // ->whereIn('assignee_id', $subOrdinateIds)
            ->whereIn('assignee_id', $assigneeIds)
            ->count();

    }
    /**
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $activityData = [
            'uuid' => generateUuid(),
            'assignee_id' => $data['assignee_id'],
            'due_date' => $data['due_date'],
            'description' => $data['description'],
            'title' => $data['title'],
        ];

        if (isset($data['quote_id'])) {
            $quote = PersonalQuoteRepository::where('id', $data['quote_id'])->firstOrFail();
            $activityData['client_name'] = $quote->first_name.' '.$quote->last_name;
            $activityData['quote_request_id'] = $quote->id;
            $activityData['quote_type_id'] = $quote->quote_type_id;
            $activityData['quote_uuid'] = $quote->uuid;
        }

        return ActivityRepository::create($activityData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($id, $data)
    {
        $activity = $this->findOrFail($id);
        $activity->update(Arr::only($data, ['title', 'description', 'due_date', 'assignee_id']));

        return $activity;
    }
}
