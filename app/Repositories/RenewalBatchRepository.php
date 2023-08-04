<?php

namespace App\Repositories;

use App\Models\RenewalBatch;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class RenewalBatchRepository extends BaseRepository
{

    public function model()
    {
        return RenewalBatch::class;
    }

    public function fetchCreate($request)
    {
        $data = [
            'name' => $request['name'],
            'quote_status_id' => $request['quote_status_id'],
            'deadline_date' => Carbon::parse($request['deadline_date'])->format('Y-m-d')
        ];

        return $this->create($data);
    }

    public function fetchUpdate($id, $request)
    {
        $renewalBatch = $this->where('id', $id)->firstOrFail();
        $request['deadline_date'] = Carbon::parse($request['deadline_date'])->format('Y-m-d');

        $renewalData = Arr::only($request, ['name', 'quote_status_id', 'deadline_date']);
        $renewalBatch->update($renewalData);

        return $renewalBatch;
    }

    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with('quoteStatus')->firstOrFail();
    }

    public function fetchGetData()
    {
        return $this->with(['quoteStatus'])
            ->filter()
            ->simplePaginate()
            ->withQueryString();
    }

    /**
     * get upcoming batch
     * @return mixed
     */
    public function fetchGetUpcomingBatch($quoteStatusId = null)
    {
        $nextMonday = Carbon::now()->next('Monday');

        $query = $this->whereBetween('deadline_date' , [Carbon::now(), $nextMonday])->orderBy('deadline_date');

        if($quoteStatusId != null) {
            $query->where('quote_status_id', $quoteStatusId);
        }

        return $query->first();
    }
}
