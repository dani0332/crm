<?php

namespace App\Repositories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\QuoteStatusLog;
use App\Services\CentralService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonalQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * @return mixed
     */
    public function fetchUpdateStatus($quoteType, $quoteId, $data)
    {
        return DB::transaction(function () use ($quoteId, $data) {
            $quote = $this->where('id', $quoteId)->firstOrFail();

            $previousStatusId = $quote->quote_status_id;

            $quoteData['quote_status_id'] = $data['quote_status_id'];
            $quoteData['quote_status_date'] = now();
            $quote->stale_at = null;

            if (! empty($data['notes'])) {
                $quoteData['notes'] = $data['notes'];
            }

            $quote->update($quoteData);

            if ($previousStatusId != $data['quote_status_id']) {
                $quote['previousStatusIdChanged'] = true;
            }
            $detailData = array_filter(Arr::only($data, ['lost_reason_id', 'transapp_code']));
            if (count($detailData)) {
                $quote->quoteDetail()->updateOrCreate(['personal_quote_id' => $quote->id], $detailData);
            }

            $activityCreated = (new CentralService())->saveAndAssignActivitesToAdvisor($quote, $quote->quote_type_id);

            QuoteStatusLog::create([
                'quote_type_id' => $quote->quote_type_id,
                'quote_request_id' => $quote->id,
                'current_quote_status_id' => $quote->quote_status_id,
                'previous_quote_status_id' => $previousStatusId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return ['quote' => $quote, 'activity_created' => $activityCreated];
        });
    }

    /**
     * @return mixed
     */
    public function fetchUploadDocument($id, $file, $data)
    {
        $query = DocumentTypeRepository::where('code', $data['document_type_code']);
        if (request()->quote_type_id) {
            $query->where('quote_type_id', request()->quote_type_id);
        }
        $documentType = $query->first();
        $quote = $this->getQuoteObject(request()->folder_path ?? '', $id);

        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = $file->getClientMimeType();

        //upload file to azure
        $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
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
            'created_by_id' => auth()->id(),
        ]);
    }

    /**
     * @param  $quoteType
     * @return mixed
     */
    public function fetchCreatePayment($quoteId, $data)
    {
        return DB::transaction(function () use ($quoteId, $data) {
            $quote = $this->where('id', $quoteId)->firstOrFail();

            $paymentData = Arr::only($data, ['collection_type', 'captured_amount', 'payment_methods_code', 'insurance_provider_id']);

            if ($data['payment_methods_code'] != PaymentMethodsEnum::CreditCard && $data['payment_methods_code'] != PaymentMethodsEnum::InsureNowPayLater) {
                $paymentData['authorized_at'] = now();
            }

            $count = $quote->payments->count();
            $paymentData['code'] = ($count > 0) ? $quote->code.'-'.$count : $quote->code;
            $paymentData['payment_status_id'] = PaymentStatusEnum::DRAFT;

            $quote->payments()->create($paymentData);

            PaymentStatusLogRepository::create([
                'current_payment_status_id' => PaymentStatusEnum::DRAFT,
                'payment_code' => $paymentData['code'],
            ]);

            $quote->update([
                'quote_status_id' => QuoteStatusEnum::PaymentPending,
                'quote_status_date' => now(),
            ]);

            return $quote;
        });
    }

    /**
     * @return mixed
     */
    public function fetchUpdatePayment($quoteId, $paymentCode, $data)
    {
        $payment = PaymentRepository::where('code', $paymentCode)->firstOrFail();
        $paymentData = Arr::only($data, ['collection_type', 'captured_amount', 'payment_methods_code', 'insurance_provider_id']);

        if (! empty($data['reference'])) {
            $paymentData['reference'] = $data['reference'];
        }
        $paymentData['updated_by'] = Auth::user()->id;

        $payment->update($paymentData);

        return $payment;
    }

    /**
     * @return mixed
     */
    public function fetchUpdatePolicyDetails($id, $data)
    {
        $quote = $this->findOrFail($id);
        $quote->update(Arr::only($data, ['policy_number', 'policy_issuance_date', 'policy_start_date', 'renewal_expiry_date', 'premium']));

        return $quote;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function fetchGetAuditHistory($leadId)
    {
        $audits = DB::table('audits as a')
            ->select(
                DB::raw('DATE_FORMAT(a.created_at, "%d-%m-%Y %H:%i:%s") as ModifiedAt'),
                DB::raw('(SELECT name from users where id = a.user_id) as ModifiedBy'),
                DB::raw("(SELECT TEXT FROM quote_status WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))) AS NewStatus"),
                DB::raw("(SELECT NAME FROM users WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.advisor_id'))) AS NewAdvisor"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.notes')) AS NewNotes")
            )
            ->where(function ($query) {
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
            })
            ->where(function ($query) use ($leadId) {
                $query->where('a.auditable_type', 'App\Models\\PersonalQuote')
                    ->where('a.auditable_id', $leadId);
            })
            ->orderBy('a.created_at', 'DESC')->get();

        return $audits;
    }

    /**
     * @return mixed
     */
    public function fetchChangePrimaryContact($quoteId, $data)
    {
        return DB::transaction(function () use ($quoteId, $data) {
            $quote = $this->findOrFail($quoteId);
            $updateData = [$data['key'] => $data['value']];
            $quote->update($updateData);

            return true;
        });
    }

    public function fetchCreateDuplicate(array $dataArr, $quoteTypeId): object
    {
        $dataArr['quoteTypeId'] = intval(array_search($quoteTypeId, QuoteTypeId::getOptions()));

        return Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);
    }
}
