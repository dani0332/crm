<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ClaimRequestDetail;

class ClaimRequestDetailObserver
{
    public function updating(ClaimRequestDetail $claimRequestDetail): void
    {

    }
 
    public function updated(ClaimRequestDetail $claimRequestDetail): void
    {

    }

    /**
     * Handle the ClaimRequestDetail "creating" event.
     */
    public function creating(ClaimRequestDetail $claimRequestDetail): void
    {

    }

    /**
     * Handle the ClaimRequestDetail "created" event.
     */
    public function created(ClaimRequestDetail $claimRequestDetail): void
    {

    }

}
