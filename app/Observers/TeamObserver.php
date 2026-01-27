<?php

namespace App\Observers;

use App\Models\Team;

class TeamObserver
{
    /**
     * Handle the Team "creating" event.
     * Set the code column to the same value as name when creating a team.
     */
    public function creating(Team $team): void
    {
        if (empty($team->code)) {
            $team->code = $team->name;
        }
    }

    /**
     * Handle the Team "updating" event.
     * Set the code column to the same value as name when updating a team.
     */
    public function updating(Team $team): void
    {
        if (empty($team->code)) {
            $team->code = $team->name;
        }
    }
}
