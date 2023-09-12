<?php

namespace App\Repositories;

use App\Models\RenewalBatch;
use Carbon\Carbon;

class RenewalBatchRepository extends BaseRepository
{
    public function model()
    {
        return RenewalBatch::class;
    }

    /**
     * get upcoming batch
     *
     * @return mixed
     */
    public function fetchGetUpcomingBatch($quoteStatusId = null)
    {
        $nextMonday = Carbon::now()->next('Monday');

        $query = $this->whereHas('deadline', function ($q) use ($quoteStatusId, $nextMonday) {

            $q->whereBetween('deadline_date', [Carbon::now(), $nextMonday])->orderBy('deadline_date');

            if ($quoteStatusId != null) {
                $q->where('quote_status_id', $quoteStatusId);
            }
        })->with(['deadline' => function ($q) use ($quoteStatusId, $nextMonday) {

            $q->whereBetween('deadline_date', [Carbon::now(), $nextMonday])->orderBy('deadline_date');

            if ($quoteStatusId != null) {
                $q->where('quote_status_id', $quoteStatusId);
            }

        }]);

        return $query->first();
    }
}
