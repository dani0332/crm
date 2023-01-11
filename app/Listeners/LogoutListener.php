<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogoutListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \IlluminateAuthEventsLogout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        info("User with ID: {$event->user->id} and name : {$event->user->name} logged out.");
    }
}
