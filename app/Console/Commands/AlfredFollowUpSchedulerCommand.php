<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Jobs\AlfredFollowupEmailJob;
use App\Models\PersonalQuote;
use App\Services\CustomerService;
use App\Services\MyAlfredService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class AlfredFollowUpSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alfred:followupEmails';

    private $customerData = [];
    private $customerEmailSent = [];
    private $myAlfredService;
    private $customerService;
    private $appEnv = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sending follow up email';

    /**
     * Execute the console command.
     */
    public function handle(MyAlfredService $myAlfredService, CustomerService $customerServic)
    {
        info('start sending follow up email : '.now());

        $this->appEnv = config('constants.APP_ENV');
        $this->myAlfredService = $myAlfredService;
        $this->customerService = $customerServic;

        $emailCampaignName = getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN);
        if (isMyAlfredCampaignEnabled($emailCampaignName)) {
            info('WFS campaign is enabled : '.now());
            $campaign = getMyAlfredCampaign($emailCampaignName);
            $this->processWeeklyQuotes($campaign->data->startDate);
        } else {
            info('WFS campaign is not enabled : '.now());
        }

        info('end sending follow up email : '.now());
    }

    public function processWeeklyQuotes($startDate)
    {
        // Define the end date as yesterday at 11:59 PM
        $leadDataEndDate = now()->endOfDay();
        // Iterate over each week within the date range
        $currentDate = Carbon::parse($startDate);

        PersonalQuote::whereNotNull('transaction_approved_at')
            ->whereBetween('transaction_approved_at', [$currentDate, $leadDataEndDate])
            ->join('customer', 'personal_quotes.customer_id', '=', 'customer.id')
            ->where('customer.campaign_followups', '<', 3)
            ->when($this->appEnv != EnvEnum::PRODUCTION, function ($q) {
                $q->where('personal_quotes.email', 'like', 'abc_abhipfet%');
            })
            ->select('personal_quotes.first_name', 'personal_quotes.last_name', 'customer.email', 'customer.campaign_followups', 'customer.id')
            ->chunk(100, function ($quotes) {
                $customersList = $this->GetEligibleCustomersForFollowUp($quotes);
                $this->customerFollowupEmails($customersList);
            });
    }

    public function GetEligibleCustomersForFollowUp($quotes)
    {
        $customersList = [];

        if (! empty($quotes)) {
            $leadsGroupedByCustomerEmail = collect($quotes)->groupBy('email')->map(function ($item) {
                return (object) [
                    'noOfPolicy' => $item->count(),
                    'email' => $item->first()->email ?? null,
                    'name' => $item->first()->first_name.' '.$item->first()->last_name ?? null,
                    'customer_id' => $item->first()->id ?? null,
                    'campaign_followups' => $item->first()->campaign_followups ?? null,
                ];
            })->values();
            info('total eligible customers : '.count($leadsGroupedByCustomerEmail));
            $this->customerData[] = $leadsGroupedByCustomerEmail->toArray();
            $response = $this->myAlfredService->getAlfredEligibleCustomers($leadsGroupedByCustomerEmail->toArray());
            if (! empty($response)) {
                $customersList = $response->data ?? [];
            }
            info('total eligible after customers : '.count($customersList));
        }

        return $customersList;
    }

    public function customerFollowupEmails($customerFollowUpEmails)
    {
        $customerDataCollapsed = collect($this->customerData)->collapse();
        $this->customerData = $customerDataCollapsed->all();
        $jobs = [];
        if (! empty($customerFollowUpEmails)) {
            foreach ($customerFollowUpEmails as $customer) {
                if (! empty($customer->email) && isValidEmail($customer->email)) {
                    if ($customer->haveRemainingScratches) {
                        $isCustomer = collect($this->customerData)->where('email', $customer->email)->first();
                        $isEmailSent = collect($this->customerEmailSent)->where('email', $customer->email)->first();
                        if (empty($isEmailSent)) {
                            $checkCustomerFollowUps = $this->customerService->getCustomerCampaignFollowups($isCustomer->customer_id);
                            if ($checkCustomerFollowUps->campaign_followups < 3) {
                                $this->customerEmailSent[] = ['email' => $isCustomer->email, 'status' => true];
                                $jobs[] = new AlfredFollowupEmailJob($isCustomer);
                            }
                        }
                    }
                } else {
                    info('invaild customer email  received from alfred : '.$customer->email);

                    echo 'invaild customer email  received from alfred : '.$customer->email;
                }
            }
            if (count($jobs)) {
                Haystack::build()
                    ->addJobs($jobs)
                    ->then(function () {
                        info('AlfredFollowUpSchedule - all jobs completed successfully');
                    })
                    ->catch(function () {
                        info('AlfredFollowUpSchedule - one of batch is failed.');
                    })
                    ->finally(function () {
                        info('AlfredFollowUpSchedule - everything done');
                    })
                    ->allowFailures()
                    ->withDelay(1)
                    ->dispatch();
            } else {
                info('AlfredFollowUpSchedule - No Customer Found to Send Email');
            }
        } else {
            info('AlfredFollowUpSchedule - No Customers Found');
        }
    }
}
