<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\RolesEnum;
use App\Jobs\PaymentNotificationEmailJob;
use App\Models\Sessions;
use App\Models\User;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SendPaymentEmail extends Command
{
    use TeamHierarchyTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendPaymentEmail:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Authorised Payments Notifications To Manager';

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
        $today = Carbon::now()->toDateString();
        $sessions = $this->getSessions();
        foreach ($sessions as $session) {
            $user = User::find($session->user_id);
            if (isset($user) && $user->hasRole(RolesEnum::CarManager)) {
                $teamId = $user->getUserTeams($user->id);
                $query = DB::table('car_quote_request');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(car_quote_request.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'car_quote_request.code')
                    ->join('users', 'users.id', 'car_quote_request.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('car_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamId)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $results = $query->get();
                info('QUERY', [$results]);

            } else {
                info('NOT FOUND');
            }

        }

        //        PaymentNotificationEmailJob::dispatch();
    }

    public function getSessions(): array|Collection
    {
        return Sessions::with('user:id,status,name,email')
            ->groupBy('user_id')
            ->get();
    }
}
