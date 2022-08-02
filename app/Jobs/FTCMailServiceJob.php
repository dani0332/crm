<?php

namespace App\Jobs;

use App\Mail\FTCMailerService;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class FTCMailServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $email = new FTCMailerService($this->request);
            $sender = Mail::to($this->request['to']);
            if (isset($this->request['cc'])) {
                $sender->cc($this->request['cc']);
            }
            $sender->send($email);

            return 'Success';
        } catch (Exception $ex) {
            return $ex;
        } finally {
            DB::disconnect('mysql');
        }
    }
}
