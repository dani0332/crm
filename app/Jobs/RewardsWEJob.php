<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\MailerService;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Mail;

class RewardsWEJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $request;
    protected $customerId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $customerId)
    {
        $this->request = json_decode($request);
        $this->customerId = $customerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('daily')->info('Entered in RewardWEJob with customer id : '.$this->customerId);
        $customer = Customer::select('*')->where('id', $this->customerId)->first();
        if(!$customer->is_we_sent && $customer->has_reward_access && $customer->has_alfred_access){
            $email = new MailerService($this->request);
            Mail::to($this->request->to)->send($email);
            Log::channel('daily')->info('Email sent to customer having id: '. $this->customerId);
            $customer->is_we_sent = true;
            $customer->save();
            Log::channel('daily')->info('isWESent flag set to true for customer having id: '. $this->customerId);
        }else {
            Log::channel('daily')->info('Welcome email not sent to customer having id : '. $this->customerId. ' , is_we_sent: '.$customer->is_we_sent. ', has_alfred_access: '. $customer->has_alfred_access.', has_reward_access: '.$customer->has_reward_access);
        }
    }
}
