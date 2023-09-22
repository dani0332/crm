<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Jobs\CarRevivalFollowUpEmailJob;
use App\Models\CarQuote;
use App\Models\DttRevival;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt:followup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        try {

            $today = Carbon::today();
            $leads = [];
            $unreplied = DttRevival::where([
                ['reply_received', 0],
                ['is_assigned', 0],
            ])->get();

            foreach ($unreplied as $item) {
                $created_at = $item->created_at;
                $lead = CarQuote::where('uuid', $item->uuid)->first();
                if (!empty($created_at) && !in_array($lead->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED])) {

                    $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
                    $afterSevenDays = Carbon::parse($created_at)->addDays(7)->startOfDay();
                    $aftertThirteenDays = Carbon::parse($created_at)->addDays(13)->startOfDay();
                    $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();
                    $afterTwentyeightDays = Carbon::parse($created_at)->addDays(28)->startOfDay();

                    $data = [];
                    $data['quoteId'] = $lead->id;
                    $data['quoteCdbId'] = $lead->code;
                    $data['customerName'] = $lead->first_name . ' ' . $lead->last_name;
                    $data['buttonUrl'] = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL') . $lead->uuid;
                    // $data['customerEmail'] = $lead->email;
                    $data['customerEmail'] = 'nouman.hussain@insurancemarket.ae';

                    if ($today->eq($afterTwoDays)) {
                        $data['templateId'] = 296;
                        $data['subject'] = 'Reminder: Purchase Your Motor Policy ' . $lead->code;
                        $leads[] = (object) $data;
                    }
                    if ($today->eq($afterSevenDays)) {
                        $data['templateId'] = 296;
                        $data['subject'] = 'Reminder: Purchase Your Motor Policy ' . $lead->code;
                        $leads[] = (object)  $data;
                    }
                    if ($today->eq($aftertThirteenDays)) {
                        $data['templateId'] = 296;
                        $data['subject'] = 'Friendly Reminder: Secure Your Motor Policy Today ' . $lead->code;
                        $leads[] = (object)  $data;
                    }
                    if ($today->eq($afterTwentyDays)) {
                        $data['templateId'] = 296;
                        $data['subject'] = 'Gentle Reminder: Secure Your Motor Policy Today ' . $lead->code;
                        $leads[] = (object)  $data;
                    }
                    if ($today->eq($afterTwentyeightDays)) {
                        $data['templateId'] = 296;
                        $data['subject'] = 'Final Reminder: Secure Your Motor Policy Now ' . $lead->code;
                        $leads[] = (object) $data;
                    }
                }
            }

            $jobs = [];
            foreach ($leads as $item) {
                $jobs[] = new CarRevivalFollowUpEmailJob($item);
            }
            $logPrefix = '------carRevivalEmailJob------';

            if ($jobs != null && count($jobs)) {
                Haystack::build()
                    ->addJobs($jobs)

                    ->then(function () use ($logPrefix) {
                        info('------' . $logPrefix . ' all jobs completed successfully ------');
                    })
                    ->catch(function () use ($logPrefix) {
                        info('------' . $logPrefix . ' one of batch is failed.------');
                    })
                    ->finally(function () use ($logPrefix) {
                        info('------' . $logPrefix . ' everything done ------');
                    })
                    ->allowFailures()
                    ->withDelay(2)
                    ->dispatch();
            } else {
                info('------No lead Found------');
            }
        } catch (\Exception $exception) {
            info('DTT Exception : ' . $exception->getMessage());
        }
    }
}
