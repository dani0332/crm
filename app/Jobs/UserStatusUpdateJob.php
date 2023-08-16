<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\UserStatusEnum;
use App\Events\UserStatusChanged;
use App\Models\ApplicationStorage;
use App\Models\Sessions;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UserStatusUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $userInactiveThreshold = ApplicationStorage::where('key_name', ApplicationStorageEnums::USER_INACTIVE_THRESHOLD)->first();
        if($userInactiveThreshold) {
            $userInactiveThreshold = (int) $userInactiveThreshold->value;
        } else {
            // default is 30 seconds if app storage doesn't exist
            $userInactiveThreshold = 30;
        }
        $inactiveThreshold = now()->subSeconds($userInactiveThreshold);
        info('Inactive Threshold right now is : '. $userInactiveThreshold. ' and last activity time matched will be : '. $inactiveThreshold);
        $sessions = Sessions::with('user:id,status,name')
                        ->whereHas('user', function ($query) {
                            $query->whereNotIn('status', [UserStatusEnum::LEAVE, UserStatusEnum::SICK]);
                        })
                        ->select('user_id', DB::raw('MAX(last_activity) AS last_activity'))
                        ->groupBy('user_id')
                        ->get();

        foreach ($sessions as $session) {
            $userId = $session->user_id;
            $userName = $session->user->name;
            info('Running status job for user : '. $userName);
            $lastActivity = Carbon::createFromTimestamp($session->last_activity);

            info('Last activity for user : '. $session->user->name. ' was at : '. $lastActivity);
            info('user table status right now is : '. $session->user->status);

            $currentUserStatus = $session->user->status ?? UserStatusEnum::UNAVAILABLE;
            info('Current Status for user : '. $session->user->name. ' is : '. $currentUserStatus);

            if ($lastActivity < $inactiveThreshold && $currentUserStatus == UserStatusEnum::ONLINE) {
                info('going to send inactive notification for user : '. $session->user->name);
                $unAvailableTime = now()->subHours(2);

                if($lastActivity < $unAvailableTime){
                    info('updating user as unavailable as the last activity was : '. $lastActivity );
                    User::where('id', $userId)->update(['status' => UserStatusEnum::UNAVAILABLE]);
                }else{
                    info('updating user as offline as the last activity was : '. $lastActivity );
                    User::where('id', $userId)->update(['status' => UserStatusEnum::OFFLINE]);
                }
                event(new UserStatusChanged($userId, 'inactive'));
            } elseif ($lastActivity >= $inactiveThreshold && $currentUserStatus != UserStatusEnum::ONLINE) {
                info('going to send active notification for user : '. $session->user->name);

                event(new UserStatusChanged($userId, 'active'));
                User::where('id', $userId)->update(['status' => UserStatusEnum::ONLINE]);
            }
        }
    }

}
