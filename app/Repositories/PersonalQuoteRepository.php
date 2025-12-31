<?php

namespace App\Repositories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Facades\Capi;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\QuoteStatusLog;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OCRService;
use App\Services\OCR\OcrUtils;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonalQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs, OcrUtils;

    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * function renamed from fetchUpdateStatus, because updateStatus named function already in GenericQueriesAllLobs
     *
     * @return mixed
     */
    public function fetchUpdateStatuses($quoteType, $quoteId, $data)
    {
        return DB::transaction(function () use ($quoteId, $data, $quoteType) {
            $quote = $this->where('id', $quoteId)->firstOrFail();

            $previousStatusId = $quote->quote_status_id;

            $quoteData['quote_status_id'] = $data['quote_status_id'];
            $quoteData['quote_status_date'] = now();
            $quote->stale_at = null;

            if (! empty($data['notes'])) {
                $quoteData['notes'] = $data['notes'];
            }
            if ($data['quote_status_id'] == QuoteStatusEnum::TransactionApproved) {
                app(CRUDService::class)->calculateScore($quote, $quoteType);
            }

            $quote->update($quoteData);

            if ($previousStatusId != $data['quote_status_id']) {
                $quote['previousStatusIdChanged'] = true;
            }
            $detailData = array_filter(Arr::only($data, ['lost_reason_id', 'transapp_code']));
            if (count($detailData)) {
                $quote->quoteDetail()->updateOrCreate(['personal_quote_id' => $quote->id], $detailData);
            }

            $activityCreated = (new CentralService)->saveAndAssignActivitesToAdvisor($quote, $quote->quote_type_id);

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
        try {
            $fileName = $file->getClientOriginalName();
            $isSendUpdate = request()->is_send_update;
            LoggerService::info(self::class.' - fn: fetchUploadDocument called - Quote UUID: '.$data['quote_uuid']);
            $quoteType = '';
            $insuranceProviderId = null;

            $query = DocumentTypeRepository::where('code', $data['document_type_code']);
            if (request()->quote_type_id) {
                $query->where('quote_type_id', request()->quote_type_id);
            }
            if (isset(request()->quote_type)) {
                $quoteType = request()->quote_type;
            }

            $documentType = $query->first();

            if ($isSendUpdate) {
                $quote = SendUpdateLog::where('id', request()->send_update_id ?? '')->first();
                LoggerService::startQuoteLogging($quote);
                LoggerService::info('fn: fetchUploadDocument start for Send Update Log');
                [$insuranceProviderId] = app(SendUpdateLogService::class)->getEndorsementProviderDetails($quote);

                // Update Send Update log with insurance provider if not set
                if ($insuranceProviderId && ! $quote->insurance_provider_id) {
                    $quote->update(['insurance_provider_id' => $insuranceProviderId]);
                    LoggerService::info('Updated Send Update Log with insurance provider', [
                        'send_update_uuid' => $quote->uuid,
                        'insurance_provider_id' => $insuranceProviderId,
                    ]);
                }
            } else {
                $quote = $this->getQuoteObject($quoteType ?? '', $id);
            }

            $isWaterMarkQualifyDoc = app(QuoteDocumentService::class)->getWatermarkProperty($quote, $documentType, $insuranceProviderId);

            $originalName = sanitizeFileName($file->getClientOriginalName());
            $docName = preg_replace('/\s+/', '', $originalName);
            $fileMimeType = $file->getClientMimeType();
            // upload file to azure
            $fileNameAzure = uniqid().'_'.$quote->uuid.'_original_'.$docName;
            $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

            // generate unique uuid
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }
            $documentTypeText = $documentType->text;

            if ($data['document_type_code'] == DocumentTypeCode::Illustration_Document && $quote->quote_type_id == QuoteTypeId::Life) {
                $documentTypeText = $quote->insuranceProvider ? $quote->insuranceProvider->text.' - '.$documentType->text : $documentType->text;
                $originalName = $quote->insuranceProvider ? $quote->insuranceProvider->text.' Illustration Document for '.$quote->customer->first_name.' '.$quote->customer->last_name.' '.$quote->code.'.'.$file->getClientOriginalExtension() : 'Illustration Document for '.$quote->customer->first_name.' '.$quote->customer->last_name.' '.$quote->code.'.'.$file->getClientOriginalExtension();
            }
            // This data will store in quote documents table
            $document = [
                'doc_name' => 'original_'.$docName,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentTypeText,
                'doc_uuid' => $docUuid,
                'created_by_id' => Auth::id(),
            ];
            // info('Document array prepared for creation', $document);

            try {
                $quoteDocument = null;

                DB::transaction(function () use ($quote, $document, $documentType, $insuranceProviderId, &$quoteDocument, $isSendUpdate) {

                    $quoteDocuments = $quote->documents->pluck('document_type_code')->toArray();
                    $taxInvoiceDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];

                    if ($isSendUpdate && in_array($documentType->code, $taxInvoiceDocuments) && count(array_intersect($taxInvoiceDocuments, $quoteDocuments)) == 0) {
                        LoggerService::info('Tax Invoice and Tax Invoice Raised Buyer documents found for Send Update Log');
                        if ($insuranceProviderId) {
                            LoggerService::info('insuranceProviderId: '.$insuranceProviderId.' found for Send Update Log');
                            $checkTransactionApprovedInSUStatusLogs = app(CentralService::class)->checkStatusSUStatusLogs($quote->id, SendUpdateLogStatusEnum::UPDATE_ISSUED);
                            if ($checkTransactionApprovedInSUStatusLogs) {
                                app(SendUpdateLogService::class)->generateBrokerInvoiceNumberForSU($quote);
                            } else {
                                app(CentralService::class)->updateSendUpdateStatusLogs($quote->id, $quote->status, SendUpdateLogStatusEnum::UPDATE_ISSUED);
                                $quote->update(['status' => SendUpdateLogStatusEnum::UPDATE_ISSUED]);
                                LoggerService::info('Send Update status updated to UPDATE_ISSUED');
                            }

                        }
                    }

                    $quoteDocument = $quote->documents()->create($document);
                    LoggerService::info('Document uploaded - Ref: '.$quote->code);
                });

                $isSendUpdateEligibleForOCR = $this->isSendUpdateEligibleForOCR($quote, $isSendUpdate);

                LoggerService::info(self::class.' - fn: populateDocumentData called - Quote UUID: '.$data['quote_uuid']);
                $this->populateDocumentData($documentType, $quote, $filePathAzure, $fileMimeType, $isSendUpdateEligibleForOCR);

                if ($isWaterMarkQualifyDoc && $quoteDocument) {
                    LoggerService::info(self::class.' - Dispatching WatermarkDocumentsJob - Quote UUID: '.$data['quote_uuid']);
                    WatermarkDocumentsJob::dispatch(
                        $quoteDocument->id, $data['quote_uuid'], $documentType->id
                    )->afterCommit();
                }

                if (! $insuranceProviderId && $isSendUpdate) {
                    LoggerService::info('File Uploaded - Insurance Provider is required to generate broker invoice number');

                    return ['status' => true, 'message' => 'File Uploaded - Insurance Provider is required to generate broker invoice number'];
                }

                return ['status' => true, 'message' => 'File Uploaded'];
            } catch (\Exception $exception) {
                LoggerService::error('Error while uploading document - Ref: '.$quote->uuid, exception: $exception);

                return ['status' => false, 'message' => $fileName.' :  '.($exception->getMessage() ?? 'Error uploading file')];
            }
        } catch (\Exception $exception) {
            LoggerService::error('Document Upload Error - UUID: '.$quote->uuid, exception: $exception);

            return ['status' => true, 'message' => $fileName.' :  Document upload failed, please try again'];
        }
    }

    private function populateDocumentData(DocumentType $documentType, $quote, $filePathAzure, $fileMimeType, $isSendUpdateEligibleForOCR)
    {
        $isOcrSendUpdateLogFlagEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_SENDUPDATE_OCR, useCache: true) == '1';
        // For SendUpdateLog, get the quote type from the quote_type_id
        $quoteTypeParam = null;
        if ($quote instanceof SendUpdateLog && $isOcrSendUpdateLogFlagEnabled) {
            // Get the quote type name for the SendUpdateLog
            $quoteTypeParam = QuoteTypes::getName($quote->quote_type_id)?->value;
            LoggerService::info('Determined quote type for SendUpdateLog', [
                'quote_uuid' => $quote->uuid,
                'quote_type_id' => $quote->quote_type_id,
                'quote_type_param' => $quoteTypeParam,
            ]);
        } else {
            $isSendUpdateEligibleForOCR = false;
        }
        LoggerService::info('check OCR Send Update Log Flag', [
            'isOcrSendUpdateLogFlagEnabled' => $isOcrSendUpdateLogFlagEnabled,
            'isSendUpdateEligibleForOCR' => $isSendUpdateEligibleForOCR,
        ]);

        app(OCRService::class)->dispatchJobIfEligible(
            $documentType,
            $quote,
            $filePathAzure,
            $fileMimeType,
            $quoteTypeParam, // Pass the determined quote type for send update log flow
            $isSendUpdateEligibleForOCR
        );
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
        $quote->update(Arr::only($data, ['policy_number', 'policy_issuance_date', 'policy_start_date', 'policy_expiry_date', 'premium']));

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

    public function fetchCreateDuplicate(array $dataArr, $quoteTypeId): object
    {
        $dataArr['quoteTypeId'] = intval(array_search($quoteTypeId, QuoteTypeId::getOptions()));

        return Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);
    }

    public function fetchGetById($quoteId)
    {
        return $this->where('id', $quoteId)->first();
    }

    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['payments'])->first();
    }

}
