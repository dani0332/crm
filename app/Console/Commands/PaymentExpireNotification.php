<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Events\PaymentExpireNotifications;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $notificationEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION)->first();
        if ($notificationEnable && $notificationEnable->value == 0) {
            info('Payment Expire Notification is Disabled');

            return false;
        }

        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();

        $query = DB::table('payments as py')
            ->select(
                'py.id',
                'py.code as uuid',
                'py.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $authorizedDays->value DAY), NOW()) as expiry_days")
            )
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->having('expiry_days', '=', 2)
            ->orderBy('py.id');

        $query->chunk(100, function ($results) {
            foreach ($results as $notification) {
                $path = '';
                $model = PersonalQuote::where('code', '=', $notification->uuid)
                    ->select('quote_type_id', 'uuid', 'advisor_id')
                    ->whereNotNull('advisor_id')
                    ->first();
                if (isset($model->quote_type_id)) {
                    $quoteType = QuoteType::select('code')->find($model->quote_type_id);
                    $quoteTypeCode = strtolower($quoteType->code);
                    if (checkPersonalQuotes($quoteType->code)) {
                        $path = "personal-quotes/$quoteTypeCode/$model->uuid";
                    } else {
                        $path = "quotes/$quoteTypeCode/$model->uuid";
                    }
                }
                $url = url('/')."/$path";
                $quoteUuid = $notification->uuid;
                if (isset($model->uuid) && isset($model->advisor_id)) {
                    event(new PaymentExpireNotifications($model, $url, $quoteUuid));
                }
            }
        });
        info('Payment Expire Notifications Job Ended');
    }

}
