<?php

namespace App\Jobs;

use App\Services\SIBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSIBContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    protected $entity = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->entity) {
            return false;
        }

        $data = [
            'customerName' => isset($this->entity->full_name) ? $this->entity->full_name : null,
            'advisorName' => isset($this->entity->advisor) ? $this->entity->advisor->name : null,
            'advisorEmail' => isset($this->entity->advisor) ? $this->entity->advisor->email : null,
            'advisorMobile' => isset($this->entity->advisor) ? $this->entity->advisor->mobile_no : null,
            'customerLastName' => isset($this->entity->last_name) ? $this->entity->last_name : null,
            'customerFirstName' => isset($this->entity->first_name) ? $this->entity->first_name : null,
            'leadStatus' => isset($this->entity->quoteStatus) ? $this->entity->quoteStatus->text : null,
            'cdbid' => isset($this->entity->code) ? $this->entity->code : null,
            'link' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$this->entity->uuid,
            'advisorLandline' => isset($this->entity->advisor) ? $this->entity->advisor->landline_no : null,
        ];

        return SIBService::contactCreateUpdate(config('constants.SIB_HEALTH_EBP_LIST_ID'), $this->entity->first_name, $this->entity->last_name, $this->entity->email, null, $data);
    }
}
