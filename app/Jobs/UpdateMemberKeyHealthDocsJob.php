<?php

namespace App\Jobs;

use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\QuoteDocument;
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
        info('------------------- Update Member Detail ID Job Started At : '. now() .' -------------------');
        QuoteDocument::withTrashed()->hasMorph('quoteDocumentable', HealthQuote::class)
            ->whereNotNull('member_detail_id')
            ->where('created_at', '<=', '2023-11-23 23:59:59')
            ->chunk(1000, function ($healthDocuments){
                foreach ($healthDocuments as $healthDocument) {
                    info('Update Member Detail Job Processing to : health_quote_request_member_details table ID ' . $healthDocument->member_detail_id . ' quote_documents table ID ' .$healthDocument->id);

                    $getOldHealthMemberRecord = HealthMemberDetail::where([
                        'id' => $healthDocument->member_detail_id
                    ])->first();

                    $customerMemberFilter = [
                        'quote_id' => $getOldHealthMemberRecord->health_quote_request_id,
                        'customer_entity_id' => $getOldHealthMemberRecord->customer_id,
                        'code' => $getOldHealthMemberRecord->code,
                        'dob' => $getOldHealthMemberRecord->dob
                    ];

                    $getCustomerMemberRecord = CustomerMembers::hasMorph('quote', HealthQuote::class)->where($customerMemberFilter)->get();

                    if ($getOldHealthMemberRecord) {
                        if ($getOldHealthMemberRecord->count() == 1) {
                            info('Update Member Detail Job : Update member_detail_id ' . $healthDocument->member_detail_id . ' with customer_member ID '.$getCustomerMemberRecord[0]->id);
                            $healthDocument->member_detail_id = $getCustomerMemberRecord[0]->id;
                            $healthDocument->save();
                            $healthDocument->refresh();
                        } else {
                            info('Update Member Detail Job : Multiple records found against ' . json_encode($getCustomerMemberRecord));
                        }
                    } else {
                        info('Update Member Detail Job : Record not found in customer_member table against ' . json_encode($getCustomerMemberRecord));
                    }
                }
            });

        info('------------------- Update Member Detail ID Job End At : '. now() .' -------------------');

    }
}
