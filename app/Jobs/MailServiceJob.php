<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\MailerService;
use Mail;

class MailServiceJob implements ShouldQueue
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
        $this->request = $request;//json_decode($request);;
        //dd($this->request['to']);exit;

       // dd($this->request);exit;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            $email = new MailerService($this->request);
            Mail::to($this->request['to'])->send($email);
            return "Success";
        } catch (Exception $ex) {
            // Debug via $ex->getMessage();
            return $ex;
        }

    }
}
