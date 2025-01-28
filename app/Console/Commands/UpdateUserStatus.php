<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\ActivityAlertEmailJob;
use App\Jobs\ReAssignBikeLeadsJob;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Jobs\ReAssignLeads;
use App\Models\ApplicationStorage;
use App\Models\Sessions;
use App\Models\Team;
use App\Models\User;
use App\Models\UserStatusAuditLog;
use App\Services\BikeAllocationService;
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
        info('----------- UpdateUserStatus Command Started -----------');
        [$userInactiveThreshold, $inactiveThreshold] = $this->getInactiveThreshold();

        info('Inactive Threshold right now is : '.$userInactiveThreshold.' and last activity time matched will be : '.$inactiveThreshold);

        $sessions = $this->getSessions();

        foreach ($sessions as $session) {
            [$userId, $lastActivity, $currentUserStatus] = $this->extractUserInformation($session);

            if ($currentUserStatus == UserStatusEnum::LEAVE || $currentUserStatus == UserStatusEnum::SICK) {
                continue;
            }

            if ($lastActivity < $inactiveThreshold) {
                $unAvailableTime = now()->subMinutes(getAppStorageValueByKey(ApplicationStorageEnums::USER_UNAVAILABLE_TIME_THRESHOLD, 120));
                $subtime = now()->subMinutes(90);

                $offlineTime = now()->subSeconds($userInactiveThreshold);

                $newStatus = $currentUserStatus;

                if ($lastActivity < $unAvailableTime) {
                    $newStatus = UserStatusEnum::UNAVAILABLE;
                } elseif ($lastActivity < $offlineTime) {
                    $newStatus = UserStatusEnum::OFFLINE;
                }

                if (($newStatus != $currentUserStatus && $currentUserStatus != UserStatusEnum::MANUAL_OFFLINE) || ($newStatus != $currentUserStatus && $currentUserStatus == UserStatusEnum::MANUAL_OFFLINE && $newStatus != UserStatusEnum::OFFLINE)) {
                    info('System will now change status from : '.$currentUserStatus.' to : '.$newStatus.' for user : '.$session->user->name);
                    User::where('id', $userId)->update(['status' => $newStatus]);
                    if ($newStatus == UserStatusEnum::UNAVAILABLE) {
                        $carId = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Car)->first()?->id;
                        $healthId = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Health)->first()?->id;
                        $bikeId = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Bike)->first()?->id;
                        if ($this->userHaveProduct($userId, $carId)) {
                            info('System triggered car reassignment job for user : '.$session->user->name);
                            ReAssignCarLeadsJob::dispatch(new CarAllocationService, $userId);
                        }
                        if ($this->userHaveProduct($userId, $healthId)) {
                            info('System triggered health reassignment job for user : '.$session->user->name);
                            ReAssignHealthLeadsJob::dispatch(new HealthAllocationService, $userId);
                        }
                        if ($this->userHaveProduct($userId, $bikeId)) {
                            info('System triggered bike reassignment job for user : '.$session->user->name);
                            ReAssignBikeLeadsJob::dispatch(new BikeAllocationService, $userId);
                        }

                        // Disabled Leads Auto Re Assignment for below Types as this is not needed at the moment
                        // foreach ([QuoteTypes::CORPLINE, QuoteTypes::LIFE, QuoteTypes::HOME, QuoteTypes::PET, QuoteTypes::YACHT, QuoteTypes::CYCLE] as $quoteType) {
                        //     $team = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', $quoteType->value)->first();
                        //     if ($this->userHaveProduct($userId, $team->id)) {
                        //         info("user belongs to {$quoteType->value} so dispatching {$quoteType->value} reassignment job");
                        //         ReAssignLeads::dispatch($quoteType, $userId);
                        //     }
                        // }
                    }
                } else {
                    if ($newStatus == UserStatusEnum::OFFLINE && $subtime > $lastActivity) {
                        $currentDateTime = Carbon::now();
                        $startDateTime = Carbon::parse('08:59:00'); // 6:30 PM
                        $endDateTime = Carbon::parse('18:30:00'); // 8:59 AM of the next day
                        if ($currentDateTime->isWeekday() && $currentDateTime->between($startDateTime, $endDateTime)) {
                            $statusLog = UserStatusAuditLog::where('user_id', $userId)->where('status', UserStatusEnum::OFFLINE)->orderBy('id', 'desc')->first();
                            if (isset($statusLog->is_mail_sent) && $statusLog->is_mail_sent == 0) {
                                $user = User::where('id', $userId)->first();
                                if ($user->is_active == 1) {
                                    ActivityAlertEmailJob::dispatch($user);
                                    $statusLog->is_mail_sent = 1;
                                    $statusLog->save();
                                }
                            }
                        }
                    }
                }
            } elseif ($lastActivity >= $inactiveThreshold && $currentUserStatus != UserStatusEnum::ONLINE && $currentUserStatus != UserStatusEnum::MANUAL_OFFLINE) {
                info('System will now change the status to Active from status : '.$currentUserStatus.' for user : '.$session->user->name);
                User::where('id', $userId)->update(['status' => UserStatusEnum::ONLINE]);
            }
        }

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
            ->whereNull('impersonated_at')
            ->get();
    }

    public function getInactiveThreshold(): array
    {
        $userInactiveThreshold = ApplicationStorage::where('key_name', ApplicationStorageEnums::USER_INACTIVE_THRESHOLD)->first();
        if ($userInactiveThreshold) {
            $userInactiveThreshold = (int) $userInactiveThreshold->value;
        } else {
            // default is 30 seconds if app storage doesn't exist
            $userInactiveThreshold = 30;
        }
        $inactiveThreshold = now()->subSeconds($userInactiveThreshold);

        return [$userInactiveThreshold, $inactiveThreshold];
    }

    public function extractUserInformation(mixed $session): array
    {
        $userId = $session->user_id;

        $lastActivity = Carbon::createFromTimestamp($session->last_activity);

        $currentUserStatus = $session->user->status ?? UserStatusEnum::UNAVAILABLE;

        return [$userId, $lastActivity, $currentUserStatus];
    }
}
