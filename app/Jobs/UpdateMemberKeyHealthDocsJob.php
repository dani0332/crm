<?php

namespace App\Jobs;

use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\QuoteDocument;
use Http\Client\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMemberKeyHealthDocsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            info('------------------- Update Member Detail ID Job Started At : ' . now() . ' -------------------');
            QuoteDocument::withTrashed()
                ->where('quote_documentable_type', 'App\Models\HealthQuote')
                ->whereNotNull('member_detail_id')
                ->where('created_at', '<=', '2023-11-23 23:59:59')
                ->chunk(1000, function ($healthDocuments) {
                    foreach ($healthDocuments as $healthDocument) {
                        info('Processing... Quote Document ID: ' . $healthDocument->id . ' - Member Detail ID: ' . $healthDocument->member_detail_id);

                        $getOldHealthMemberRecord = HealthMemberDetail::where([
                            'id' => $healthDocument->member_detail_id
                        ])->first();

                        if ($getOldHealthMemberRecord) {

                            $customerMemberFilter = [
                                'quote_id' => $getOldHealthMemberRecord->health_quote_request_id,
                                'customer_entity_id' => $getOldHealthMemberRecord->customer_id ?? '',
                                'code' => $getOldHealthMemberRecord->code ?? '',
                                'dob' => $getOldHealthMemberRecord->dob ?? ''
                            ];

                            $getCustomerMemberRecord = CustomerMembers::where($customerMemberFilter)->where('quote_type', 'App\Models\HealthQuote')->get();
                            if ($getOldHealthMemberRecord->count() == 1) {
                                info('Updating... Quote Document ID: '.$healthDocument->id.' - Old member_detail_id: '.$healthDocument->member_detail_id.' - New member_detail_id: '.$getCustomerMemberRecord[0]->id);
                                $healthDocument->old_member_detail_id = $healthDocument->member_detail_id;
                                $healthDocument->member_detail_id = $getCustomerMemberRecord[0]->id;
                                $healthDocument->save();
                            } else {
                                info('Multiple records found in customer_members against Quote Request ID: ' . $getOldHealthMemberRecord->health_quote_request_id . ' - Customer ID: ' . $getOldHealthMemberRecord->customer_id . ' - Code: ' . $getOldHealthMemberRecord->code . ' - DOB: ' . $getOldHealthMemberRecord->dob);
                            }
                        } else {
                            info('Record not found in health_quote_request_member_details against : Quote Request ID : ' . $healthDocument->quote_documentable_id . ' - ID : ' . $healthDocument->member_detail_id);
                        }
                    }
                });

            info('------------------- Update Member Detail ID Job End At : ' . now() . ' -------------------');
        } catch (Exception $exception) {
            \Log::error('Update Member Detail ID Job - Error - Message: '.$exception->getMessage());
        }

    }
}
