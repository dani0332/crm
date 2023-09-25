<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Events\UserStatusChanged;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Models\ApplicationStorage;
use App\Models\Sessions;
use App\Models\Team;
use App\Models\User;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UpdateUserStatus extends Command
{
    use TeamHierarchyTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateUserStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the user status based on last activity';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('UpdateHealthStatus Command Started');
        [$userInactiveThreshold, $inactiveThreshold] = $this->getInactiveThreshold();

        info('Inactive Threshold right now is : '.$userInactiveThreshold.' and last activity time matched will be : '.$inactiveThreshold);

        $sessions = $this->getSessions();

        foreach ($sessions as $session) {
            [$userId, $lastActivity, $currentUserStatus] = $this->extractUserInformation($session);

            if ($currentUserStatus == UserStatusEnum::LEAVE || $currentUserStatus == UserStatusEnum::SICK) {
                info('going to skip user : '.$session->user->name.' due to current status');

                continue;
            }

            if ($lastActivity < $inactiveThreshold) {

                info('Inside activity check for user : '.$session->user->name);
                $unAvailableTime = now()->subHours(2);
                info('unavailable time is : '.$unAvailableTime);
                $offlineTime = now()->subMinutes(5);

                $newStatus = $currentUserStatus;

                if ($lastActivity < $unAvailableTime) {
                    $newStatus = UserStatusEnum::UNAVAILABLE;
                } elseif ($lastActivity < $offlineTime) {
                    $newStatus = UserStatusEnum::OFFLINE;
                }

                if ($newStatus != $currentUserStatus) {
                    info('updating user as '.$newStatus.' as the last activity was : '.$lastActivity);
                    User::where('id', $userId)->update(['status' => $newStatus]);
                    event(new UserStatusChanged($userId, $newStatus));
                    if ($newStatus == UserStatusEnum::UNAVAILABLE) {
                        $carId = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Car)->first()->pluck('id');
                        $healthId = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Health)->first()->pluck('id');
                        if ($this->userHaveProduct($userId, $carId)) {
                            ReAssignCarLeadsJob::dispatch(new CarAllocationService(), $userId);
                        }
                        if ($this->userHaveProduct($userId, $healthId)) {
                            ReAssignHealthLeadsJob::dispatch(new HealthAllocationService(), $userId);
                        }

                    }
                }
            } elseif ($lastActivity >= $inactiveThreshold && $currentUserStatus != UserStatusEnum::ONLINE) {
                info('going to send active notification for user : '.$session->user->name);
                event(new UserStatusChanged($userId, UserStatusEnum::ONLINE));
                User::where('id', $userId)->update(['status' => UserStatusEnum::ONLINE]);
            }
        }
        info('UpdateHealthStatus Command Completed');

        return 0;
    }

    public function getSessions(): array|Collection
    {
        return Sessions::with('user:id,status,name')
            ->whereHas('user', function ($query) {
                $query->whereNotIn('status', [UserStatusEnum::LEAVE, UserStatusEnum::SICK]);
            })
            ->select('user_id', DB::raw('MAX(last_activity) AS last_activity'))
            ->orderBy('last_activity')
            ->groupBy('user_id')
            ->get();
    }

    public function getInactiveThreshold(): array
    {
        $userInactiveThreshold = ApplicationStorage::where('key_name', ApplicationStorageEnums::USER_INACTIVE_THRESHOLD)->first();
        if ($userInactiveThreshold) {
            $userInactiveThreshold = (int) $userInactiveThreshold->value;
        } else {
            // default is 30 seconds if app storage doesn't exist
            $userInactiveThreshold = 300;
        }
        $inactiveThreshold = now()->subSeconds($userInactiveThreshold);

        return [$userInactiveThreshold, $inactiveThreshold];
    }

    public function extractUserInformation(mixed $session): array
    {
        $userId = $session->user_id;
        $userName = $session->user->name;

        info('Running status job for user : '.$userName);
        $lastActivity = Carbon::createFromTimestamp($session->last_activity);

        info('Last activity for user : '.$session->user->name.' was at : '.$lastActivity);
        info('user table status right now is : '.$session->user->status);

        $currentUserStatus = $session->user->status ?? UserStatusEnum::UNAVAILABLE;
        info('Current Status for user : '.$session->user->name.' is : '.$currentUserStatus);

        return [$userId, $lastActivity, $currentUserStatus];
    }
}
