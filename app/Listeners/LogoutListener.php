<?php

namespace App\Listeners;

use App\Enums\UserStatusEnum;
use App\Models\User;
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
        if ($event->user) {
            info("User with ID: {$event->user->id} and email : {$event->user->email } logged out.");
            User::where('id', $event->user->id)->update(['status' => UserStatusEnum::UNAVAILABLE, 'logout_at' => now()]);
            info("User with ID: {$event->user->id} and email : {$event->user->email } status changed to unavailable.");
        } else {
            info('Logout event fired but no user found.');

            return;
        }
    }
}
