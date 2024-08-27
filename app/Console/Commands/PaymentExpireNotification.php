<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Events\PaymentExpireNotifications;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentExpireNotification extends Command
{
    use GenericQueriesAllLobs;
    use TeamHierarchyTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PaymentExpireNotification:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Payments Expire Notifications To Advisor';

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
        $this->processNotifications('car_quote_request', 'car');
        $this->processNotifications('health_quote_request', 'health');
        $this->processNotifications('business_quote_request', 'business');
        $this->processNotifications('business_quote_request', 'business', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical), 'medical/amt');
        $this->processNotifications('personal_quotes', 'bike', QuoteTypeId::Bike, 'personal-quotes/bike');
        $this->processNotifications('personal_quotes', 'cycle', QuoteTypeId::Cycle, 'personal-quotes/cycle');
        $this->processNotifications('personal_quotes', 'yacht', QuoteTypeId::Yacht, 'personal-quotes/yacht');
        $this->processNotifications('personal_quotes', 'pet', QuoteTypeId::Pet, 'personal-quotes/pet');
        $this->processNotifications('personal_quotes', 'jetski', QuoteTypeId::Jetski, 'personal-quotes/jetski');
        $this->processNotifications('personal_quotes', 'home', QuoteTypeId::Home, 'quotes/home');
        $this->processNotifications('personal_quotes', 'life', QuoteTypeId::Life, 'quotes/life');
        $this->processNotifications('travel_quote_request', 'travel', null, 'quotes/travel');
    }

    private function processNotifications(
        string $tableName,
        string $type,
        ?int $quoteTypeId = null,
        ?string $customPath = null
    ) {
        $query = DB::table("$tableName as rq")
            ->leftJoin('payments as py', 'py.code', '=', 'rq.code')
            ->select(
                'rq.id',
                'rq.code as uuid',
                'rq.advisor_id as advisor_id',
                'rq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 8 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('rq.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->having('expiry_days', '=', 1);

        if ($quoteTypeId !== null && Schema::hasColumn($tableName, 'quote_type_id')) {
            $query->where('rq.quote_type_id', '=', $quoteTypeId);
        }

        $notifications = $query->get();

        if ($notifications->isEmpty()) {
            return;
        }

        info(ucfirst($type).' Quotes Expire Notification Job Start');

        foreach ($notifications as $notification) {
            $model = $this->getModelObject(strtolower($type));
            $model = $model::find($notification->id);
            $path = $customPath ?? "quotes/$type/$model->uuid";
            $url = url('/')."/$path";
            $quoteUuid = $notification->uuid;
            event(new PaymentExpireNotifications($model, $url, $quoteUuid));
        }
    }

}
