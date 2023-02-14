<?php

namespace App\Repositories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\QuoteStatusLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PersonalQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    /**
     * @param $quoteId
     * @param $data
     * @return mixed
     */
    public function fetchUpdateStatus($quoteType, $quoteId, $data)
    {
       return DB::transaction(function() use($quoteType, $quoteId, $data)
       {
           $quote = $this->where('id', $quoteId)->firstOrFail();

           $previousStatusId = $quote->quote_status_id;

           $quoteData['quote_status_id'] = $data['quote_status_id'];

           if(!empty($data['notes'])) {
               $quoteData['notes'] = $data['notes'];
           }

           $quote->update($quoteData);

           QuoteStatusLog::create([
               'quote_type_id' => $quote->quote_type_id,
               'quote_request_id' => $quote->id,
               'current_quote_status_id' => $quote->quote_status_id,
               'previous_quote_status_id' => $previousStatusId,
               'created_at' => Carbon::now(),
               'updated_at' => Carbon::now(),
           ]);

           return $quote;
       });
    }

    /**
     * @param $file
     * @param $data
     * @return mixed
     */
    public function fetchUploadDocument($file, $data)
    {
        $documentType = DocumentTypeRepository::where('code', $data['document_type_code'])->first();
        $quote = $this->whereId($data['quote_id'])->first();

        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = $file->getClientMimeType();

        //upload file to azure
        $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
        $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

        //generate unique uuid
        $docUuid = uniqid();
        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        return $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => $docUuid,
            'member_detail_id' => $data['member_detail_id'] ?? null,
            'created_by_id' => auth()->id(),
        ]);
    }


    /**
     * @param $quoteType
     * @param $quoteId
     * @param $data
     * @return mixed
     */
    public function fetchCreatePayment($quoteType, $quoteId, $data)
    {
        return DB::transaction(function() use ($quoteType, $quoteId, $data)
        {
            $quote =  $this->where('id', $quoteId)->firstOrFail();

            $paymentData = Arr::only($data, ['collection_type', 'captured_amount', 'reference', 'payment_methods_code', 'insurance_provider_id', 'plan_id']);

            if($data['payment_methods_code'] != PaymentMethodsEnum::CreditCard) {
                $paymentData['authorized_at'] = now();
            }

            $paymentData['code'] = 'P-'.strtoupper(substr(uniqid(''), 0, 8));
            $paymentData['payment_status_id'] = PaymentStatusEnum::PENDING;
            $quote->payments()->create($paymentData);

            PaymentStatusLogRepository::create([
                'current_payment_status_id' => PaymentStatusEnum::PENDING,
                'payment_code' => $paymentData['code']
            ]);

            $quote->update(['quote_status_id' => QuoteStatusEnum::PaymentPending]);

            return $quote;
        });
    }
}
