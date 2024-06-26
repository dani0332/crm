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

        $url = url('/');
        // CAR PAYMENT EXPIRE NOTIFICATION
        $carNotification = DB::table('car_quote_request as cqr')
            ->leftJoin('payments as py', 'py.code', '=', 'cqr.code')
            ->select(
                'cqr.id',
                'cqr.code as uuid',
                'cqr.advisor_id as advisor_id',
                'cqr.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->where('cqr.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->whereNotNull('py.authorized_at')
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($carNotification)) {
            info('Car Quotes Expire Notification Job Start');
            foreach ($carNotification as $car) {
                $model = $this->getModelObject(strtolower('car'));
                $model = $model::find($car->id);
                $url .= '/quotes/'.strtolower('car').'/'.$model->uuid;
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // HEALTH PAYMENT EXPIRE NOTIFICATION
        $healthNotification = DB::table('health_quote_request as hqr')
            ->leftJoin('payments as py', 'py.code', '=', 'hqr.code')
            ->select(
                'hqr.id',
                'hqr.code as uuid',
                'hqr.advisor_id as advisor_id',
                'hqr.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('hqr.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($healthNotification)) {
            info('Health Quotes Expire Notification Job Start');
            foreach ($healthNotification as $health) {
                $model = $this->getModelObject(strtolower('health'));
                $model = $model::find($health->id);
                $url .= '/quotes/'.strtolower('health').'/'.$model->uuid;
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // BUSINESS PAYMENT EXPIRE NOTIFICATION
        $businessNotification = DB::table('business_quote_request as bqr')
            ->leftJoin('payments as py', 'py.code', '=', 'bqr.code')
            ->select(
                'bqr.id',
                'bqr.code as uuid',
                'bqr.advisor_id as advisor_id',
                'bqr.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('bqr.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($businessNotification)) {
            info('Business Quotes Expire Notification Job Start');
            foreach ($businessNotification as $business) {
                $model = $this->getModelObject(strtolower('business'));
                $model = $model::find($business->id);
                $url .= "/quotes/business/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // BUSINESS QUOTE MEDICAL PAYMENT EXPIRE NOTIFICATION
        $medicalNotification = DB::table('business_quote_request as bqr')
            ->leftJoin('payments as py', 'py.code', '=', 'bqr.code')
            ->select(
                'bqr.id',
                'bqr.code as uuid',
                'bqr.advisor_id as advisor_id',
                'bqr.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('bqr.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('bqr.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical))
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($medicalNotification)) {
            info('Group Medical Quotes Expire Notification Job Start');
            foreach ($medicalNotification as $business) {
                $model = $this->getModelObject(strtolower('business'));
                $model = $model::find($business->id);
                $url .= "/medical/amt/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // PERSONAL QUOTES NOTIFICATION //

        // BIKE QUOTE  PAYMENT EXPIRE NOTIFICATION
        $bikeNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Bike)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($bikeNotification)) {
            info('Bike Quotes Expire Notification Job Start');
            foreach ($bikeNotification as $bike) {
                $model = $this->getModelObject(strtolower('bike'));
                $model = $model::find($bike->id);
                $url .= "/personal-quotes/bike/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // CYcle QUOTE  PAYMENT EXPIRE NOTIFICATION
        $cycleNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Cycle)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($cycleNotification)) {
            info('Cycle Quotes Expire Notification Job Start');
            foreach ($cycleNotification as $cycle) {
                $model = $this->getModelObject(strtolower('cycle'));
                $model = $model::find($cycle->id);
                $url .= "/personal-quotes/cycle/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // YACHT QUOTE  PAYMENT EXPIRE NOTIFICATION
        $yachtNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Yacht)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($yachtNotification)) {
            info('Yacht Quotes Expire Notification Job Start');
            foreach ($yachtNotification as $yacht) {
                $model = $this->getModelObject(strtolower('yacht'));
                $model = $model::find($yacht->id);
                $url .= "/personal-quotes/yacht/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // PET QUOTE  PAYMENT EXPIRE NOTIFICATION
        $petNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Pet)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($petNotification)) {
            info('Pet Quotes Expire Notification Job Start');
            foreach ($petNotification as $pet) {
                $model = $this->getModelObject(strtolower('pet'));
                $model = $model::find($pet->id);
                $url .= "/personal-quotes/pet/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // JETSKI QUOTE  PAYMENT EXPIRE NOTIFICATION
        $jetkiNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Jetski)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($jetkiNotification)) {
            info('JETKI Quotes Expire Notification Job Start');
            foreach ($jetkiNotification as $jetski) {
                $model = $this->getModelObject(strtolower('jetski'));
                $model = $model::find($jetski->id);
                $url .= "/personal-quotes/jetski/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // Home QUOTE  PAYMENT EXPIRE NOTIFICATION
        $homeNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Home)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($homeNotification)) {
            info('Home Quotes Expire Notification Job Start');
            foreach ($homeNotification as $home) {
                $model = $this->getModelObject(strtolower('home'));
                $model = $model::find($home->id);
                $url .= "/quotes/home/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // LIFE QUOTE  PAYMENT EXPIRE NOTIFICATION
        $lifeNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Life)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($lifeNotification)) {
            info('Life Quotes Expire Notification Job Start');
            foreach ($lifeNotification as $life) {
                $model = $this->getModelObject(strtolower('life'));
                $model = $model::find($life->id);
                $url .= "/quotes/life/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

        // Travel QUOTE  PAYMENT EXPIRE NOTIFICATION
        $travelNotification = DB::table('personal_quotes as pq')
            ->leftJoin('payments as py', 'py.code', '=', 'pq.code')
            ->select(
                'pq.id',
                'pq.code as uuid',
                'pq.advisor_id as advisor_id',
                'pq.payment_status_id as payment_status_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                DB::raw('DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL 7 DAY), NOW()) as expiry_days')
            )
            ->whereNotNull('advisor_id')
            ->whereNotNull('py.authorized_at')
            ->where('py.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->where('pq.quote_type_id', QuoteTypeId::Travel)
            ->having('expiry_days', '=', 2)
            ->get();

        if (! empty($travelNotification)) {
            info('Travel Quotes Expire Notification Job Start');
            foreach ($travelNotification as $travel) {
                $model = $this->getModelObject(strtolower('travel'));
                $model = $model::find($travel->id);
                $url .= "/quotes/travel/$model->uuid";
                event(new PaymentExpireNotifications($model, $url));
            }
        }

    }

}
