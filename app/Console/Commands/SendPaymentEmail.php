<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
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
        $emailEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL)->first();
        if ($emailEnable && $emailEnable->value == 0) {
            info('ENABLE_PAYMENT_NOTIFICATION_EMAIL is Disable');

            return false;
        }
        $getUsers = $this->getUsers();
        $userIds = $getUsers->pluck('id')->unique()->toArray();

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! isset($user)) {
                info('User Not Found');

                continue;
            }

            $rolesData = [
                RolesEnum::CarManager => ['role' => 'Car', 'table' => 'car_quote_request'],
                RolesEnum::BusinessManager, RolesEnum::CorplineManager => ['role' => 'Business', 'table' => 'business_quote_request'],
                RolesEnum::HealthManager => ['role' => 'Health', 'table' => 'health_quote_request'],
                RolesEnum::TravelManager => ['role' => 'Travel', 'table' => 'travel_quote_request'],
                RolesEnum::HomeManager => ['role' => 'Home', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Home],
                RolesEnum::PetManager => ['role' => 'Pet', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Pet],
                RolesEnum::YachtManager => ['role' => 'Yacht', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Yacht],
                RolesEnum::LifeManager => ['role' => 'Life', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Life],
                RolesEnum::BikeManager => ['role' => 'Bike', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Bike],
                RolesEnum::CycleManager => ['role' => 'Cycle', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Cycle],
                RolesEnum::JetskiManager => ['role' => 'Jetski', 'table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Jetski],
            ];

            $notificationsData = [];
            foreach ($rolesData as $roleKey => $roleData) {
                if ($user->hasRole($roleKey)) {
                    $role = $roleData['role'];
                    $table = $roleData['table'];
                    $quoteTypeId = $roleData['quoteTypeId'] ?? null;

                    $data = $this->getPaymentNotificationData($role, $user, $table, $quoteTypeId);
                    if ($data) {
                        $notificationsData = array_merge($notificationsData, $data);
                    }
                }
            }

            if (! empty($notificationsData)) {
                $lead = [
                    'leads_expire' => array_sum(array_column($notificationsData, 'total_leads')),
                    'total_premium' => array_sum(array_column($notificationsData, 'total_premium')),
                    'total_leads' => $notificationsData[0]['total_authorized_leads'] ?? 0,
                ];
                info("Dispatching PaymentNotification Job For User {$user->email}");
                PaymentNotificationEmailJob::dispatch($lead, $user);
            }
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
                DB::raw('COUNT(DISTINCT '.$table.'.code) as total_leads'),
                DB::raw('SUM('.$table.'.premium) as total_premium'),
                DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $authorizedDays->value DAY), NOW()) as expiry_days")
            )
            ->leftJoin($table, 'py.code', '=', $table.'.code')
            ->join('users', 'users.id', '=', $table.'.advisor_id')
            ->join('user_team', 'user_team.user_id', '=', 'users.id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->where('py.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->whereIn('teams.name', $teamName)
            ->when($quoteTypeId !== null, function ($query) use ($quoteTypeId, $table) {
                return $query->where($table.'.quote_type_id', $quoteTypeId);
            })
            ->where($table.'.source', '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT)
            ->groupBy('expiry_days')
            ->having('expiry_days', '=', 1)
            ->orderBy('py.id');

        $data = [];
        $query->chunk(500, function ($leads) use (&$data, $role, $totalLead) {
            foreach ($leads as $lead) {
                $data[] = [
                    'role' => $role,
                    'total_leads' => $lead->total_leads,
                    'total_premium' => $lead->total_premium,
                    'expiry_days' => $lead->expiry_days,
                    'total_authorized_leads' => $totalLead,
                ];
            }
        });

        return $data;
    }
}
