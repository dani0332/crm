<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\QuoteRequestEntityMapping;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCustomerMemberMissingData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;
    public $backoff = 100;
    private $iteratedRecords = 0;


    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customerMembers = CustomerMembers::whereNull('customer_entity_id')->get();
        $customerMembersTotalCount = $customerMembers->count();
        info('Update Customer Member Job Start - Fetching records with empty customer_entity_id. Total Records Found - '.$customerMembersTotalCount);

        foreach ($customerMembers as $customerMember) {

            $quoteModel = $customerMember->quote_type;
            $quoteRequestDetails = $quoteModel::where('id', $customerMember->quote_id)->first();

            info('Quote Details Fetch - ' . 'Quote Model:' .$quoteModel.' - Ref-ID:' .$quoteRequestDetails->id. ' - Customer-ID:'.$quoteRequestDetails->customer_id);

            if ($customerMember->customer_type && $customerMember->customer_type == CustomerTypeEnum::Entity) {
                $quoteRequestMapping = QuoteRequestEntityMapping::where([
                    'quote_type_id' => '',
                    'quote_request_id' => $quoteRequestDetails->id])
                    ->first();

                info('Updating Entity Member - Entity-ID:' .$quoteRequestMapping->entity_id);
                $entityCodeExplode = explode('-', $customerMember->code);
                $entityCodeExplode[1] = 23; //$quoteRequestMapping->entity_id;

                $customerMember->customer_entity_id = $quoteRequestMapping->entity_id;
                $customerMember->code = implode('-', $entityCodeExplode);

            } else {

                $customerMemberCount = CustomerMembers::where('customer_entity_id', $quoteRequestDetails->customer_id)
                    ->where('customer_type', CustomerTypeEnum::Individual)->count();

                info('Updating Customer Member - Customer-ID:' .$quoteRequestDetails->customer_id);
                $customerMemberCount++;
                $customerMember->code = CustomerTypeEnum::IndividualShort .'-'. $quoteRequestDetails->customer_id .'-'. $customerMemberCount;
                $customerMember->customer_entity_id = $quoteRequestDetails->customer_id;
                $customerMember->customer_type = CustomerTypeEnum::Individual;
            }

            $customerMember->save();
            $customerMember->refresh();
            $this->iteratedRecords++;
        }

        info('Update Customer Member Job End. Total Records Updated - '. $this->iteratedRecords);
    }
}
