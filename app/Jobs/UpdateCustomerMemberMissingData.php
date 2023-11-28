<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
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
    public $backoff = 90;
    private $iteratedRecords = 0;
    private $quoteTypeId = '';

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

        $quoteTypesIds = QuoteTypeId::asArray();
        foreach ($customerMembers as $customerMember) {

            $quoteModel = $customerMember->quote_type;
            $quoteType = str_replace('Quote', '', explode('\\', $quoteModel)[2]);
            $quoteRequestDetails = $quoteModel::where('id', $customerMember->quote_id)->first();
            $this->quoteTypeId = ($quoteType == QuoteTypes::PERSONAL->value) ? $quoteRequestDetails->quote_type_id ?? '' : $quoteTypesIds[$quoteType ?? ''];

            if ($quoteRequestDetails) {
                info('Quote Details Fetch - '.'Quote Model:'.$quoteModel.' Quote Type ID: '.$this->quoteTypeId.' - Ref-ID:'.$quoteRequestDetails->id.' - Customer-ID:'.$quoteRequestDetails->customer_id);

                if ($customerMember->customer_type && $customerMember->customer_type == CustomerTypeEnum::Entity) {
                    $quoteRequestMapping = QuoteRequestEntityMapping::where([
                        'quote_type_id' => $this->quoteTypeId,
                        'quote_request_id' => $quoteRequestDetails->id])
                        ->first();

                    if ($quoteRequestMapping) {
                        info('Updating Entity Member - Entity-ID:'.$quoteRequestMapping->entity_id ?? '');
                        if ($quoteRequestMapping) {
                            $entityCodeExplode = explode('-', $customerMember->code);
                            $entityCodeExplode[1] = $quoteRequestMapping->entity_id;

                            $customerMember->customer_entity_id = $quoteRequestMapping->entity_id;
                            $customerMember->code = implode('-', $entityCodeExplode);
                        }
                    } else {
                        info('Entity Not found against member. Customer Member ID:' . $customerMember->id);
                    }

                } else {
                    $customerMemberCount = CustomerMembers::where('customer_entity_id', $quoteRequestDetails->customer_id)
                        ->where('customer_type', CustomerTypeEnum::Individual)->count();

                    info('Updating Customer Member - Customer-ID:'.$quoteRequestDetails->customer_id);
                    $customerMemberCount++;
                    $customerMember->code = CustomerTypeEnum::IndividualShort.'-'.$quoteRequestDetails->customer_id.'-'.$customerMemberCount;
                    $customerMember->customer_entity_id = $quoteRequestDetails->customer_id;
                    $customerMember->customer_type = CustomerTypeEnum::Individual;
                }

                $customerMember->save();
                $customerMember->refresh();
                $this->iteratedRecords++;
            } else {

                info('Quote Details Fetch Failed - '.'Quote Model:'.$quoteModel.' Quote Type ID: '.$this->quoteTypeId.' - Ref-ID:'.$customerMember->quote_id. ' - Customer Member ID:'.$customerMember->id);
                $this->iteratedRecords++;
            }
        }

        info('Update Customer Member Job End. Total Records Updated - '.$this->iteratedRecords);
    }
}
