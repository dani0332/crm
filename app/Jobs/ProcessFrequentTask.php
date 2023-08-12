<?php

namespace App\Jobs;

use App\Events\UserStatusChanged;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessFrequentTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $inactiveThreshold = now()->subSeconds(10);
        $sessions = DB::table('sessions')
            ->select('user_id', DB::raw('MAX(last_activity) AS last_activity'))
            ->groupBy('user_id')
            ->get();

        foreach ($sessions as $session) {
            info('inside session '.json_encode($session));
            $userId = $session->user_id;
            $lastActivity = Carbon::createFromTimestamp($session->last_activity);

            if (isset($userId)) {
                info('inside user');
                if ($lastActivity && $lastActivity < $inactiveThreshold) {
                    info('inside inactivity');
                    event(new UserStatusChanged($userId, 'inactive'));
                } else {
                    info('inside active');
                    event(new UserStatusChanged($userId, 'active'));
                }

            }
        }
    }
}
