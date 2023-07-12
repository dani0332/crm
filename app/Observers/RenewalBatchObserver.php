<?php

namespace App\Observers;

use App\Http\Requests\RenewalBatchRequest;
use App\Models\RenewalBatch;
use Illuminate\Http\Request;

class RenewalBatchObserver
{
    protected $attributes = null;

    public function __construct(RenewalBatchRequest $request)
    {
        $this->attributes = $request->validated();
    }
    /**
     * Handle the RenewalBatch "created" event.
     *
     * @param  \App\Models\RenewalBatch  $renewalBatch
     * @return void
     */
    public function created(RenewalBatch $renewalBatch)
    {
        $this->postOps($renewalBatch);
    }

    /**
     * Handle the RenewalBatch "updated" event.
     *
     * @param  \App\Models\RenewalBatch  $renewalBatch
     * @return void
     */
    public function updated(RenewalBatch $renewalBatch)
    {
        $this->postOps($renewalBatch, 'update');
    }

    /**
     * Handle the RenewalBatch "deleted" event.
     *
     * @param  \App\Models\RenewalBatch  $renewalBatch
     * @return void
     */
    public function deleted(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * Handle the RenewalBatch "restored" event.
     *
     * @param  \App\Models\RenewalBatch  $renewalBatch
     * @return void
     */
    public function restored(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * Handle the RenewalBatch "force deleted" event.
     *
     * @param  \App\Models\RenewalBatch  $renewalBatch
     * @return void
     */
    public function forceDeleted(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * perform necesarry operations on model creation adn deletion function
     *
     * @param RenewalBatch $renewalBatch
     * @return void
     */
    public function postOps(RenewalBatch $renewalBatch, $event=null)
    {
        // delete previous associations in case of update event
        if ($event === 'update') {
            $renewalBatch->slabs()->detach();
            $renewalBatch->segmentAdvisors()->detach();
        }
        // create renewal batch slabs
        foreach ($this->attributes['slab'] as $key => $teams) {
            foreach ($teams as $team => $valueTypes) {
                $pivotColumnsData = array(
                    'team_id' => null,
                    'max' => null,
                    'min' => null,
                );

                $pivotColumnsData['team_id'] = $team;

                foreach ($valueTypes as $type =>  $value) {
                    $pivotColumnsData[strtolower($type)] = $value;
                }

                $renewalBatch->slabs()->attach($key, $pivotColumnsData);
            }
        }

        // create renewal batch segmentwise advisors
        // segment volume
        foreach ($this->attributes['segment_volume'] as $key => $value) {
            $pivotColumnsData = [
                'segment_type' => RenewalBatch::SEGMENT_TYPE_VOLUME,
            ];
            $renewalBatch->segmentAdvisors()->attach($value, $pivotColumnsData);
        }
        //segment value
        foreach ($this->attributes['segment_value'] as $key => $value) {
            $pivotColumnsData = [
                'segment_type' => RenewalBatch::SEGMENT_TYPE_VALUE,
            ];

            $renewalBatch->segmentAdvisors()->attach($value, $pivotColumnsData);
        }
    }
}
