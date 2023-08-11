<?php

namespace App\Repositories;

use App\Models\Activities;
use Illuminate\Support\Arr;

class ActivityRepository extends BaseRepository
{
    public function model()
    {
        return Activities::class;
    }

    /**
     * @return mixed
    */
    public function fetchGetData()
    {
        return $this->leftJoin('users', 'users.id', 'activities.assignee_id')->select(
            'activities.id as id',
            'client_name', 'client_email', 'quote_request_id', 'quote_uuid', 'quote_type_id', 'due_date', 'name', 'title', 'status', 'assignee_id', 'uuid'
        )->filter()->whereIn('assignee_id', [874])->simplePaginate()->withQueryString();
    }

    /**
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quote = PersonalQuoteRepository::where('id', $data['quote_id'])->firstOrFail();

        $activityData = [
            'uuid' => generateUuid(),
            'client_name' => $quote->first_name.' '.$quote->last_name,
            'quote_request_id' => $quote->id,
            'quote_type_id' => $quote->quote_type_id,
            'quote_uuid' => $quote->uuid,
            'assignee_id' => $data['assignee_id'],
            'due_date' => $data['due_date'],
            'description' => $data['description'],
            'title' => $data['title'],
        ];

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
