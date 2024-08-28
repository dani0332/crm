<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Jobs\PaymentNotificationEmailJob;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Repositories\PaymentRepository;
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
        $emailEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION)->first();
        if ($emailEnable && $emailEnable->value == 0) {
            info('Payment Email is Disable');

            return false;
        }
        $getUsers = $this->getUsers();
        $userIds = $getUsers->pluck('id')->unique()->toArray();
        info('Payment Notification Email Job Dispatch');

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            if (! isset($user)) {
                info('User Not Found In Session');

                continue;
            }

            $role = null;
            $table = null;
            $quoteTypeId = null;

            switch ($user) {
                case $user->hasRole(RolesEnum::CarManager):
                    $role = 'Car';
                    $table = 'car_quote_request';
                    break;
                case $user->hasRole(RolesEnum::BusinessManager) || $user->hasRole(RolesEnum::CorplineManager) :
                    $role = 'Business';
                    $table = 'business_quote_request';
                    break;
                case $user->hasRole(RolesEnum::HealthManager):
                    $role = 'Health';
                    $table = 'health_quote_request';
                    break;
                case $user->hasRole(RolesEnum::TravelManager):
                    $role = 'Travel';
                    $table = 'travel_quote_request';
                    break;
                case $user->hasRole(RolesEnum::HomeManager):
                    $role = 'Home';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Home;
                    break;
                case $user->hasRole(RolesEnum::PetManager):
                    $role = 'Pet';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Pet;
                    break;
                case $user->hasRole(RolesEnum::YachtManager):
                    $role = 'Yacht';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Yacht;
                    break;
                case $user->hasRole(RolesEnum::LifeManager):
                    $role = 'Life';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Life;
                    break;
                case $user->hasRole(RolesEnum::BikeManager):
                    $role = 'Bike';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Bike;
                    break;
                case $user->hasRole(RolesEnum::CycleManager):
                    $role = 'Cycle';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Cycle;
                    break;
                case $user->hasRole(RolesEnum::JetskiManager):
                    $role = 'Jetski';
                    $table = 'personal_quotes';
                    $quoteTypeId = QuoteTypeId::Jetski;
                    break;
                default:
                    info('User has no valid role');
                    break;
            }

            $this->getPaymentNotificationData($role, $user, $table, $quoteTypeId);
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

        return User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        })->get(['id', 'email', 'name']);
    }

    public function getPaymentNotificationData($role, $user, $table, $quoteTypeId = null)
    {
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $totalLead = app(PaymentRepository::class)->getAuthorisePaymentCount($user->id);
        $teamName = $user->getUserTeams($user->id);

        $query = DB::table('payments as py')
            ->select(
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM('.$table.'.premium) as total_premium'),
                DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $authorizedDays->value DAY), NOW()) as expiry_days")
            )
            ->leftJoin($table, 'py.code', '=', $table.'.code')
            ->join('users', 'users.id', '=', $table.'.advisor_id')
            ->join('user_team', 'user_team.user_id', '=', 'users.id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->where($table.'.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->whereIn('teams.name', $teamName)
            ->when($quoteTypeId !== null, function ($query) use ($quoteTypeId, $table) {
                return $query->where($table.'.quote_type_id', $quoteTypeId);
            })
            ->groupBy('expiry_days')
            ->having('expiry_days', '=', 1)
            ->orderBy('py.id');

        $query->chunk(500, function ($leads) use ($role, $user, $totalLead) {
            foreach ($leads as $lead) {
                info("PaymentNotification Job Dispatch For User {$role} and User Email {$user->email}");
                PaymentNotificationEmailJob::dispatch($lead, $user, $totalLead);
            }
        });
    }

}
