<?php

namespace App\Listeners;

use App\Events\NationalityPoolCreated;
use App\Models\NationalityPool;
use Carbon\Carbon;

class HandleNationalityPoolCreated
{
    /**
     * Handle the event.
     */
    public function handle(NationalityPoolCreated $event): void
    {
        // Update effective to of previous record
        $lastRecord = NationalityPool::orderByDesc('effective_from')
            ->limit(1)
            ->offset(1)
            ->first();

        if ($lastRecord) {
            $lastRecord->update([
                'effective_to' => Carbon::parse($event->pool->effective_from)->subDay()->format('Y-m-d'),
            ]);
        }
    }
}
