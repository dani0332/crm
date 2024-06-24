<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
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
        $userIds = $sessions->pluck('user_id')->unique()->toArray();
        foreach ($userIds as $userId) {
            $userData = User::find($userId);
            if (isset($userData) && $userData->hasRole(RolesEnum::CarManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('car_quote_request');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        'users.email as advisor_email',
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
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Car Lead');
                    PaymentNotificationEmailJob::dispatch($user);

                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::BusinessManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('business_quote_request');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(business_quote_request.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'business_quote_request.code')
                    ->join('users', 'users.id', 'business_quote_request.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('business_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Business Lead');
                    PaymentNotificationEmailJob::dispatch($user);

                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::HealthManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('health_quote_request');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(health_quote_request.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'health_quote_request.code')
                    ->join('users', 'users.id', 'health_quote_request.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('health_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Health Lead');
                    PaymentNotificationEmailJob::dispatch($user);

                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::TravelManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Travel)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Travel Lead');
                    PaymentNotificationEmailJob::dispatch($user);

                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::HomeManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Home)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Home Lead');
                    PaymentNotificationEmailJob::dispatch($user);

                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::PetManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Pet)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Pet Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::YachtManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Yacht)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Yacht Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::LifeManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Life)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Life Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::BikeManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Bike)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Bike Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::CycleManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Cycle)
                    ->whereDate('py.authorized_at', $today)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Cycle Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } elseif (isset($userData) && $userData->hasRole(RolesEnum::JetskiManager)) {
                $teamName = $userData->getUserTeams($userData->id);
                $query = DB::table('personal_quotes');

                $query
                    ->select(
                        'users.id as advisor_id',
                        'users.name as advisor_name',
                        DB::raw('COUNT(*) as total_leads'),
                        DB::raw('SUM(personal_quotes.premium) as total_premium'),
                        DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                    )
                    ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                    ->join('users', 'users.id', 'personal_quotes.advisor_id')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->where('personal_quotes.quote_type_id', QuoteTypeId::Jetski)
                    ->whereIn('teams.name', $teamName)
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('total_leads', 'desc');

                $user = $query->get();

                if ($user->isNotEmpty()) {
                    info('sendPaymentNotification Job Dispatch For Jetski Lead');
                    PaymentNotificationEmailJob::dispatch($user);
                }
            } else {
                info('User Not Found In Session');
            }
        }
    }

    public function getSessions(): array|Collection
    {
        return Sessions::with('user:id,status,name')
            ->whereHas('user', function ($query) {
                $query->whereHas('roles', function ($query) {
                    $query->whereIn('name', [
                        RolesEnum::CarManager,
                        RolesEnum::BusinessManager,
                        RolesEnum::BikeManager,
                        RolesEnum::LifeManager,
                        RolesEnum::HealthManager,
                        RolesEnum::JetskiManager,
                        RolesEnum::YachtManager,
                        RolesEnum::PetManager,
                        RolesEnum::HomeManager,
                        RolesEnum::TravelManager,
                        RolesEnum::CycleManager,
                    ]);
                });
            })
            ->groupBy('user_id')
            ->get();
    }

}
