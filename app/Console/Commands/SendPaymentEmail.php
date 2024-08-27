<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Jobs\PaymentNotificationEmailJob;
use App\Models\User;
use App\Traits\TeamHierarchyTrait;
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
        $getUsers = $this->getUsers();
        $userIds = $getUsers->pluck('id')->unique()->toArray();
        info('Payment Notification Email Job Dispatch');

        foreach ($userIds as $userId) {
            $userData = User::find($userId);

            if (! isset($userData)) {
                info('User Not Found In Session');

                continue;
            }

            $role = null;
            $table = null;
            $quoteTypeId = null;

            switch (true) {
                case $userData->hasRole(RolesEnum::CarManager):
                    $role = 'Car';
                    $table = 'car_quote_request';
                    break;
                case $userData->hasRole(RolesEnum::BusinessManager) || $userData->hasRole(RolesEnum::CorplineManager) :
                    $role = 'Business';
                    $table = 'business_quote_request';
                    break;
                case $userData->hasRole(RolesEnum::HealthManager):
                    $role = 'Health';
                    $table = 'health_quote_request';
                    break;
                case $userData->hasRole(RolesEnum::TravelManager):
                    $role = 'Travel';
                    $table = 'travel_quote_request';
                    break;
                case $userData->hasRole(RolesEnum::HomeManager):
                    $role = 'Home';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Home;
                    break;
                case $userData->hasRole(RolesEnum::PetManager):
                    $role = 'Pet';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Pet;
                    break;
                case $userData->hasRole(RolesEnum::YachtManager):
                    $role = 'Yacht';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Yacht;
                    break;
                case $userData->hasRole(RolesEnum::LifeManager):
                    $role = 'Life';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Life;
                    break;
                case $userData->hasRole(RolesEnum::BikeManager):
                    $role = 'Bike';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Bike;
                    break;
                case $userData->hasRole(RolesEnum::CycleManager):
                    $role = 'Cycle';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Cycle;
                    break;
                case $userData->hasRole(RolesEnum::JetskiManager):
                    $role = 'Jetski';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Jetski;
                    break;
                default:
                    info('User has no valid role');
                    break;
            }

            $this->getPaymentNotificationData($role, $userData, $table, $quoteTypeId);
        }
    }

    public function getUsers(): array|Collection
    {
        $roles = [
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
            RolesEnum::CorplineManager,
        ];

        //        return User::whereHas('roles', function ($query) use ($roles) {
        //            $query->whereIn('name', $roles);
        //        })->get(['id', 'email', 'name']);
        $userIds = [1129, 116, 1000, 1080, 968];

        return User::whereIn('id', $userIds)->whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        })->get(['id', 'email', 'name']);
    }

    public function getPaymentNotificationData($role, $userData, $table, $quoteTypeId = null)
    {
        $totalLead = getAuthorisePaymentCount($userData->id);
        $teamName = $userData->getUserTeams($userData->id);

        $query = DB::table($table)
            ->select(
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM('.$table.'.premium) as total_premium'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 8 DAY), NOW()) as expiry_days')

            )
            ->leftJoin('payments as py', 'py.code', '=', $table.'.code')
            ->join('users', 'users.id', $table.'.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->where($table.'.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->whereIn('teams.name', $teamName)
            ->when($quoteTypeId !== null, function ($query) use ($quoteTypeId, $table) {
                return $query->where($table.'.quote_type_id', $quoteTypeId);
            })
            ->groupBy('expiry_days')
            ->having('expiry_days', '=', 1)
            ->orderBy('total_leads', 'desc');
        $user = $query->get();
        if ($user || $userData) {
            info("PaymentNotification Job Dispatch For {$role}");
            info('Payment Email Send to User: '.$userData->email);
            PaymentNotificationEmailJob::dispatch($user, $userData, $totalLead);
        }
    }

}
