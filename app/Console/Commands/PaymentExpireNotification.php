<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Events\PaymentExpireNotifications;
use App\Models\ApplicationStorage;
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
                'py.paymentable_id as paymentable_id',
                'py.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $authorizedDays->value DAY), NOW()) as expiry_days")
            )
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->having('expiry_days', '=', 2)
            ->orderBy('py.id');

        $query->chunk(500, function ($results) {
            foreach ($results as $notification) {
                $shortCode = QuoteTypes::getNameShortCode(strtoupper(substr($notification->uuid, 0, strpos($notification->uuid, '-'))));
                $quoteType = $shortCode->value ?? null;
                if (! $quoteType) {
                    info('Notification not triggered for '.$notification->uuid);

                    return;
                }
                $path = '';
                $lead = $this->getQuoteObjectBy($quoteType, $notification->paymentable_id, 'id');
                if ($lead) {
                    $quoteTypeCode = strtolower($quoteType);
                    if ($quoteType == QuoteTypeCode::Business) {
                        if ($lead->business_type_of_insurance_id == QuoteBusinessTypeCode::getId(QuoteBusinessTypeCode::groupMedical)) {
                            $path = "medical/amt/$lead->uuid";
                        } else {
                            $path = "quotes/business/$lead->uuid";
                        }
                    } elseif (checkPersonalQuotes($quoteType)) {
                        $path = "personal-quotes/$quoteTypeCode/$lead->uuid";
                    } else {
                        $path = "quotes/$quoteTypeCode/$lead->uuid";
                    }

                    $url = url('/')."/$path";
                    if ($lead && isset($lead->uuid) && isset($lead->advisor_id)) {
                        event(new PaymentExpireNotifications($lead, $url, $notification->uuid));
                    }
                }
            }
        });
        info('Payment Expire Notifications Job Ended');
    }

}
