<?php

namespace App\Listeners\Impersonation;

use App\Models\Sessions;
use Lab404\Impersonate\Events\TakeImpersonation;

class HandleImpersonatedSession
{
    public function handle(TakeImpersonation $event): void
    {
        info('Impersonation started', [
            'impersonator' => $event->impersonator->id,
            'impersonated' => $event->impersonated->id,
            'session_id' => session()->getId(),
        ]);

        session()->save();

        Sessions::where('id', session()->getId())->update([
            'impersonated_at' => now(),
        ]);
    }
}
