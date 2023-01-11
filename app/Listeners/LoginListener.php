<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use IlluminateAuthEventsLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LoginListener
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
     * @param  \IlluminateAuthEventsLogin  $event
     * @return void
     */
    public function handle(Login $event)
    {
        info("User with ID: {$event->user->id} and name : {$event->user->name} logged in.");
    }
}
