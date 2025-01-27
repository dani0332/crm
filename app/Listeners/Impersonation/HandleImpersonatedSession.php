<?php

namespace App\Listeners\Impersonation;

use Illuminate\Support\Facades\DB;
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

        DB::table('sessions')->where('id', session()->getId())->update([
            'impersonated_at' => now(),
        ]);
    }
}
