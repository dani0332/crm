<?php

namespace App\Observers;

use App\Enums\QuoteTypeId;
use App\Models\RenewalBatch;

class RenewalBatchObserver
{
    protected $attributes = null;

    public function __construct()
    {
        $this->attributes = request()->toArray();
    }
    /**
     * Handle the RenewalBatch "created" event.
     *
     * @return void
     */
    public function created(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * Handle the RenewalBatch "saved" event.
     *
     * @return void
     */
    public function saved(RenewalBatch $renewalBatch)
    {
        $this->postOps($renewalBatch);
    }

    /**
     * Handle the RenewalBatch "deleted" event.
     *
     * @return void
     */
    public function deleted(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * Handle the RenewalBatch "restored" event.
     *
     * @return void
     */
    public function restored(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * Handle the RenewalBatch "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(RenewalBatch $renewalBatch)
    {
        //
    }

    /**
     * perform necesarry operations on model creation adn deletion function
     *
     * @return void
     */
    public function postOps(RenewalBatch $renewalBatch)
    {
        $renewalBatchObj = RenewalBatch::find($renewalBatch->id);
        // quote type id is for motor batch
        if ($renewalBatchObj->quote_type_id === QuoteTypeId::Car) {
            // delete previous associations in case of update event
            if ($renewalBatch->wasRecentlyCreated === false) {
                $renewalBatch->slabs()->detach();
                $renewalBatch->segmentAdvisors()->detach();
                $renewalBatch->deadlines()->detach();
            }
            // create renewal batch slabs
            if ($this->attributes['slab']) {
                foreach ($this->attributes['slab'] as $key => $teams) {
                    foreach ($teams as $team => $valueTypes) {
                        $pivotColumnsData = [
                            'team_id' => null,
                            'max' => null,
                            'min' => null,
                        ];

                        $pivotColumnsData['team_id'] = $team;

                        foreach ($valueTypes as $type => $value) {
                            $pivotColumnsData[strtolower($type)] = $value;
                        }

                        $renewalBatch->slabs()->attach($key, $pivotColumnsData);
                    }
                }
            }

            // create renewal batch segmentwise advisors
            // segment volume
            if ($this->attributes['segment_volume']) {
                foreach ($this->attributes['segment_volume'] as $key => $value) {
                    $pivotColumnsData = [
                        'segment_type' => RenewalBatch::SEGMENT_TYPE_VOLUME,
                    ];

                    $renewalBatch->segmentAdvisors()->attach($value, $pivotColumnsData);
                }
            }
            // segment value
            if ($this->attributes['segment_value']) {
                foreach ($this->attributes['segment_value'] as $key => $value) {
                    $pivotColumnsData = [
                        'segment_type' => RenewalBatch::SEGMENT_TYPE_VALUE,
                    ];

                    $renewalBatch->segmentAdvisors()->attach($value, $pivotColumnsData);
                }
            }

            // renewal batch deadlines
            if ($this->attributes['quote_status_id']) {
                foreach ($this->attributes['quote_status_id'] as $quoteStatusId) {
                    $pivotColumnsData = [
                        'deadline_date' => $this->attributes['deadline_date'][$quoteStatusId],
                    ];
                    $renewalBatch->deadlines()->attach($quoteStatusId, $pivotColumnsData);
                }
            }
        }
    }
}
