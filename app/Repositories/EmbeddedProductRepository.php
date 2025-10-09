<?php

namespace App\Repositories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedProductTypeEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\EpCategoryEnum;
use App\Enums\EpEcbExcludeVehicleEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Facades\Ken;
use App\Facades\Marshall;
use App\Jobs\EP\CancelEPJob;
use App\Jobs\EpPurchaseFlowJob;
use App\Jobs\EpSendDocumentJob;
use App\Jobs\MACRM\CancelCourierQuoteOnMACRM;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\ProcessSyncAlfredProtect;
use App\Jobs\SendEPDocumentsJob;
use App\Jobs\SukoonMedexPurchaseFlowJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CustomerAddress;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\GenericDocument;
use App\Models\PaymentAction;
use App\Models\PaymentSplits;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Models\SageProcess;
use App\Services\EpEcbService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Services\SukoonMedexService;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use App\Strategies\EmbeddedProducts\COU;
use App\Strategies\EmbeddedProducts\ECB;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;
use App\Strategies\EmbeddedProducts\MDX;
use App\Strategies\EmbeddedProducts\RDX;
use App\Strategies\EmbeddedProducts\TravelAnnual;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use finfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;

class EmbeddedProductRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public const SALAMA_DATE = '2025-07-15 21:00:00';
    public const SALAMA_POLICY_WORDINGS_PATH = 'documents/embedded_products/687774f80a867_embedded_product_687774f80a862_SalamaDriverCover(MEDEX)-PolicyWordings.pdf';
    public const SALAMA_POLICY_WORDINGS_URL = 'https://insurancemarket.blob.core.windows.net/imcrm/'.self::SALAMA_POLICY_WORDINGS_PATH;

    public function model()
    {
        return EmbeddedProduct::class;
    }

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'insuranceProviders' => InsuranceProviderRepository::getList(),
            'quoteTypes' => QuoteTypeRepository::getList(),
        ];
    }

    /**
     * @param  $quoteType
     * @return mixed
     */
    public function fetchCreate($data)
    {
        return DB::transaction(function () use ($data) {
            $product = $this->create($data);

            $product->placements()->createMany($data['placements']);
            $product->prices()->createMany($data['pricings']);

            return $product;
        });
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->where('id', $id)->firstOrFail();

            $product->update($data);
            $product->placements()->delete();
            $product->placements()->createMany($data['placements']);

            $prices = $product->prices()->get();

            foreach ($prices as $price) {
                if (! in_array($price->id, array_column($data['pricings'], 'id'))) {
                    if (EmbeddedTransaction::where('product_id', $price->id)->exists()) {
                        $price->is_active = 0;
                        $price->save();
                    } else {
                        // only delete options which are not used in any transaction
                        $price->delete();
                    }
                }
            }

            foreach ($data['pricings'] as $price) {
                if (isset($price['id'])) {
                    $product->prices()->where('id', $price['id'])->update($price);
                } else {
                    $product->prices()->create($price);
                }
            }

            return $product;
        });
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['insuranceProvider', 'placements.quoteType', 'prices' => function ($query) {
            $query->where('is_active', 1);
        }])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData($fetchType = 'all', $shortcodes = [])
    {
        $query = $this->with(['insuranceProvider'])->latest('updated_at');

        if ($fetchType === 'active') {
            $query = $query->active();
        }

        if (! empty($shortcodes)) {
            $query = $query->whereIn('short_code', $shortcodes);
        }

        return $query->simplePaginate();
    }

    /**
     * @return mixed
     */
    public function fetchUploadDocument($file, $title)
    {
        $type = 'embedded_product';
        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = $file->getClientMimeType();

        $fileNameAzure = uniqid().'_'.$type.'_'.$docName;
        $filePathAzure = $file->storeAs('documents/embedded_products', $fileNameAzure, 'azureIM');

        // generate unique uuid
        $docUuid = uniqid();
        while (GenericDocument::where('uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        GenericDocument::create([
            'uuid' => $docUuid,
            'name' => $title.'_'.$originalName,
            'path' => $filePathAzure,
            'mime_type' => $fileMimeType,
            'documentable_type' => 'App\Models\EmbeddedProduct',
            'created_by_id' => auth()->id(),
        ]);

        return [
            'path' => $filePathAzure,
            'title' => $title,
        ];
    }

    public function fetchByQuoteType($quoteTypeId, $quoteRequestId)
    {
        $ep = $this->whereHas('placements', function ($query) use ($quoteTypeId) {
            $query->where('quote_type_id', $quoteTypeId);
        })
            ->with([
                'prices' => function ($query) {
                    $query->where('is_active', 1);
                },
                'prices.transactions' => function ($query) use ($quoteTypeId, $quoteRequestId) {
                    $query->where('quote_request_id', $quoteRequestId);
                    $query->where('quote_type_id', $quoteTypeId);
                },
                'prices.transactions.payments',
                'prices.transactions.travelAnnualPayments',
            ])
            ->whereHas('prices.transactions', function ($query) use ($quoteRequestId) {
                $query->where('quote_request_id', $quoteRequestId);
            })
            ->get();
        $modelType = QuoteType::where('id', '=', $quoteTypeId)->value('code');
        $ep->each(function ($item) use ($modelType, $quoteTypeId, $quoteRequestId) {
            $item->send_document_button = false;
            $item->download_document_button = false;
            $optionsIds = $item->prices->pluck('id');
            $item->sync_document_button = false;

            $transaction = EmbeddedTransaction::with('documents', 'product.embeddedProduct')->where([
                ['quote_type_id', '=', $quoteTypeId],
                ['quote_request_id',  '=', $quoteRequestId],
                ['is_selected',  '=', true],
            ])->whereIn('product_id', $optionsIds)->get();
            $quoteObject = $this->getQuoteObject($modelType, $quoteRequestId);

            $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($item->short_code);
            $isSukoonMedex = EmbeddedProductStrategy::checkSukoonMedex($item->short_code);
            $isECB = $item->short_code == EmbeddedProductEnum::ECB;
            $isMedxOrEcb = $isSukoonMedex || $isECB;

            if ($isAlfredProtect) {
                $isDocPresent = count($transaction) > 0 ? $transaction[0]->documents()->count() > 0 : false;
                $item->download_document_button = $isDocPresent && $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);

                if (auth()->user()->hasRole(RolesEnum::Engineering)) {
                    $documentCount = ($isDocPresent == true) ? $transaction[0]->documents()->count() : 0;
                    $item->sync_document_button = $documentCount < 5;
                }
            } elseif ($isMedxOrEcb && count($transaction) > 0) {

                $isSukoonEpReadyForSage = $transaction[0]->policy_status == EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE;
                $canSendDocuments = $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction)
                    || $this->canSendSukoonMedexDocumentsWithPolicyIssued($item->product_category, $quoteObject->quote_status_id, $transaction);
                $item->sync_document_button = (! $isSukoonEpReadyForSage) && $canSendDocuments;
            }

            $item->send_document_button = $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);
            $item->can_cancel_payment = $this->canCancelPayment($transaction->first(), $quoteTypeId);
            $item->can_void_payment = $this->canVoidPayment($transaction->first());
            $item->can_book_embedded_product = $this->canBookEmbeddedProduct($transaction->first(), $quoteObject, $item);
        });

        return $ep;
    }

    private function canCancelPayment($transaction, $quoteTypeId)
    {
        if ($transaction && $transaction->payments->first()) {
            $payment = $transaction->payments->first();
            if ($payment->getAttributes()['payment_status_id'] == PaymentStatusEnum::CAPTURED) {

                $canCancel = false;
                if (auth()->user()->can(PermissionsEnum::EMBEDDED_PRODUCT_MANUAL_OVERRIDE)) {
                    $canCancel = true;

                } elseif ($transaction->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER) {
                    $address = CustomerAddress::where('quote_uuid', $transaction->quoteRequest->uuid)->where('quote_type_id', $quoteTypeId)->first();

                    $canCancel = empty($address?->type);

                } else {

                    $paymentDate = Carbon::parse($payment->getAttributes()['captured_at']);
                    $canCancel = $paymentDate->diffInDays(Carbon::now()) <= 3;
                }

                return $canCancel;
            }
        }

        return false;
    }

    private function canVoidPayment($transaction)
    {
        if (
            auth()->user()->can(PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_VOID)
            && $transaction
        ) {
            return $transaction->payment_status_id == PaymentStatusEnum::AUTHORISED;
        }

        return false;
    }

    private function canBookEmbeddedProduct($transaction, $quote, $ep)
    {
        $isPolicyBooked = $quote?->quote_status_id == QuoteStatusEnum::PolicyBooked;
        $isMedXEP = in_array($ep->short_code, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX]);
        if ($isMedXEP) {
            $epTranSageStatusFailed = $transaction?->sage_status_id == SageEmbeddedProductEnum::BOOKING_FAILED->id();
            $epTransSageProcess = SageProcess::where(['model_type' => $transaction?->getMorphClass(), 'model_id' => $transaction?->id])->first();
            $isSageProcessFailed = $epTransSageProcess?->status == SageEnum::SAGE_PROCESS_FAILED_STATUS;

            return $isPolicyBooked && $isMedXEP && (! $transaction?->sage_status_id || $epTranSageStatusFailed) && (! $epTransSageProcess || $isSageProcessFailed);
        }

        return false;

    }

    private function canSendAndDownloadDocuments($productCategory, $quoteStatusId, $transaction)
    {
        if (! $transaction->isEmpty() && in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if (
                $productCategory == EpCategoryEnum::STAND_ALONE ||
                ($productCategory == EpCategoryEnum::BOLT_ON && in_array($quoteStatusId, $this->canSendDocumentEnums()))) {
                return true;
            }
        }

        return false;
    }

    /**
     * canSendSukoonMedexDocuments - this is only used for Policy Issued status
     *
     * @param  mixed  $productCategory
     * @param  mixed  $quoteStatusId
     * @param  mixed  $transaction
     * @return void
     */
    private function canSendSukoonMedexDocuments($productCategory, $quoteStatusId, $transaction)
    {
        if (! $transaction->isEmpty() && in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if ($productCategory == EpCategoryEnum::BOLT_ON &&
                $quoteStatusId == QuoteStatusEnum::PolicyIssued &&
                in_array($transaction->first()->policy_status, [EmbeddedTransactionEnum::STATUS_BOOKED, EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE])) {
                return true;
            }
        }

        return false;
    }

    /**
     * canSendSukoonMedexDocuments - this is only used for Policy Issued status
     *
     * @param  mixed  $productCategory
     * @param  mixed  $quoteStatusId
     * @param  mixed  $transaction
     * @return void
     */
    private function canSendSukoonMedexDocumentsWithPolicyIssued($productCategory, $quoteStatusId, $transaction)
    {
        if (! $transaction->isEmpty() && in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if ($productCategory == EpCategoryEnum::BOLT_ON &&
                $quoteStatusId == QuoteStatusEnum::PolicyIssued) {
                return true;
            }
        }

        return false;
    }

    public function canSendDocumentEnums(): array
    {
        return [
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];
    }

    public function fetchSendDocumentsByLead($leadId, $modelType, $epId = null, $callPurchaseFlow = false)
    {
        $extra = [
            'quoteId' => $leadId,
            'modelType' => $modelType,
            'epId' => $epId,
            'callPurchaseFlow' => $callPurchaseFlow,
        ];

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home, QuoteTypeId::Travel])) {
            LoggerService::info('fetchSendDocumentsByLead - Only car, bike, home & travel lob are allowed', extra: $extra);

            return ['success' => false, 'message' => 'Only car, bike, home & travel lob are allowed'];
        }

        $epTransaction = EmbeddedTransaction::where([
            ['quote_type_id', $quoteTypeId],
            ['quote_request_id', $leadId],
            ['is_selected', 1],
        ])
            ->where('payment_status_id', PaymentStatusEnum::CAPTURED)
            ->with(['product.embeddedProduct']);

        if (! empty($epId)) {
            $ep = $this->where('id', $epId)->first();
            $optionsIds = [];
            if ($ep->prices) {
                $optionsIds = $ep->prices->pluck('id');
            }
            $epTransaction = $epTransaction->whereIn('product_id', $optionsIds);
        }

        $epTransaction = $epTransaction->get();
        if ($epTransaction->isEmpty()) {
            LoggerService::info('fetchSendDocumentsByLead - Record not found', extra: $extra);

            return ['success' => false, 'message' => 'Record not found'];
        }

        $response = ['success' => false];
        foreach ($epTransaction as $item) {

            $isDocPresent = $item->documents->count() > 0;
            $epShortCode = $item->product->embeddedProduct->short_code ?? '';
            $sukoonMedexCodes = EmbeddedProductEnum::getSukoonMedexCodes();

            if (EmbeddedProductStrategy::checkAlfredProtect($epShortCode) && ! $isDocPresent) {
                $quoteObject = $this->getQuoteObject($modelType, $leadId);
                ProcessSyncAlfredProtect::dispatch($quoteObject);
                $response = ['success' => true];

            } elseif ($epShortCode == EmbeddedProductEnum::COURIER
            && in_array(ucwords($modelType), [quoteTypeCode::Car, quoteTypeCode::Home, quoteTypeCode::Travel])) {

                $quoteObject = $this->getQuoteObject($modelType, $leadId);
                SyncCourierQuoteWithMacrm::dispatch($quoteObject, $quoteTypeId);
                $response = ['success' => true];

            } elseif (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])
                && in_array($epShortCode, [...$sukoonMedexCodes, EmbeddedProductEnum::ECB])) {

                $product_id = $item->product_id ?? null;
                $embedded_product_id = EmbeddedProductOption::find($product_id)?->embedded_product_id;

                if (in_array($epShortCode, $sukoonMedexCodes) && $item->paid_at && Carbon::parse($item->paid_at)->lt(Carbon::parse(self::SALAMA_DATE))) {

                    // EP Send documents
                    $response = $this->fetchSendDocument([
                        'quoteId' => $leadId,
                        'modelType' => $modelType,
                        'epId' => $embedded_product_id,
                        'isSalama' => true,
                    ]);

                } else {

                    $quoteObject = $this->getQuoteObject($modelType, $leadId);
                    $quoteObject->load('latestInsured', 'embeddedTransactions.product.embeddedProduct', 'customer');

                    if ($callPurchaseFlow) {
                        if (in_array($epShortCode, $sukoonMedexCodes)) {
                            // Sukoon Medex Purchase Flow
                            SukoonMedexPurchaseFlowJob::dispatch($quoteObject, $quoteTypeId, $item, isSendEmail: true);
                        } 
                        elseif ($quoteTypeId == QuoteTypeId::Car && $epShortCode == EmbeddedProductEnum::ECB) {
                            // ECB Purchase Flow
                            $quote = $this->getQuoteObject($modelType, $leadId);
                            $context = EpEcbService::buildContext($item->id, $leadId, $quoteTypeId, $quote->code);
                            dispatch(new EpPurchaseFlowJob($context));
                        }
                        $response = ['success' => true];

                    } else {
                        $watermarkedDocuments = $item->documents()
                            ->whereIn('document_type_code', QuoteDocumentsEnum::getSukoonInitialDocTypes())->get()
                            ->where('is_watermarked', true);

                        $watermarkedDocumentTypes = $watermarkedDocuments->pluck('document_type_code')->toArray();
                        $missingReqWatermarkedDocTypes = array_diff(QuoteDocumentsEnum::getSukoonInitialDocTypes(), $watermarkedDocumentTypes);

                        // make sure email required watermarked documents is not missing
                        if (empty($missingReqWatermarkedDocTypes)) {

                            $response = $this->fetchSendDocument([
                                'quoteId' => $leadId,
                                'modelType' => $modelType,
                                'epId' => $embedded_product_id,
                            ]);

                        } else {
                            LoggerService::info('fetchSendDocumentsByLead - Required watermarked documents are not saved, please sync documents first', extra: $extra);
                            $response = ['success' => false, 'message' => 'Required watermarked documents are not saved, please sync documents first'];
                            break;
                        }
                    }
                }

            }
        }

        return $response;
    }

    public function fetchSyncDocument($data)
    {
        $epId = $data['epId'];
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];

        $quoteObject = $this->getQuoteObject($modelType, $quoteId);
        if (empty($quoteObject)) {
            return ['success' => false, 'message' => 'Quote not found'];
        }

        $ep = $this->where('id', $epId)->first();
        if (empty($ep)) {
            return ['success' => false, 'message' => 'Embedded Product not found'];
        }

        $shortCode = $ep->short_code;
        if (EmbeddedProductStrategy::checkAlfredProtect($shortCode)) {
            ProcessSyncAlfredProtect::dispatch($quoteObject);
        } elseif (EmbeddedProductStrategy::checkSukoonMedex($shortCode)) {

            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
            $quoteObject->load('embeddedTransactions.product.embeddedProduct');

            $shortCodes = EmbeddedProductEnum::getSukoonMedexCodes() ?? [];
            $transaction = $this->fetchTransaction($modelType, $quoteId, $ep, shortCodes: $shortCodes)
                ->whereIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])
                ->first();

            if (empty($transaction)) {
                LoggerService::info("No transaction found, ref_id: {$quoteObject->code}");

                return ['success' => false, 'message' => 'No transaction found'];
            }

            try {
                $sukoonMedexService = app(SukoonMedexService::class);
                $sukoonMedexService->initiatePurchaseFlow($quoteObject, $quoteTypeId, $transaction);
                $sukoonMedexService->processPurchaseFlow();
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }

        } elseif ($shortCode == EmbeddedProductEnum::ECB) {

            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
            $transaction = $this->fetchTransaction($modelType, $quoteId, $ep, shortCodes: [EmbeddedProductEnum::ECB])
                ->whereIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])
                ->first();

            if (empty($transaction)) {
                LoggerService::info("No transaction found, ref_id: {$quoteObject->code}");
                return ['success' => false, 'message' => 'No transaction found'];
            }

            try {
                $context = EpEcbService::buildContext($transaction->id, $quoteId, $quoteTypeId, $quoteObject->code);
                dispatch(new EpPurchaseFlowJob($context));
            } catch (Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return ['success' => true];
    }

    public function fetchSendDocument($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];
        $isSalama = $data['isSalama'] ?? false;

        $ep = $this->where('id', $epId)->first();
        if (! $ep) {
            return ['success' => false, 'message' => 'Embedded Product not found'];
        }

        $short_code = $ep->short_code;
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($short_code);
        $isSukoonMedex = EmbeddedProductStrategy::checkSukoonMedex($short_code);
        $isECB = $short_code == EmbeddedProductEnum::ECB;
        $isMedxOrEcb = $isSukoonMedex || $isECB;

        [$attachments, $attachmentsUrls] = $this->fetchAttachments($ep, $isAlfredProtect, $isSalama);

        $quoteObject = $this->getQuoteObject($modelType, $quoteId);
        if (empty($quoteObject)) {
            return ['success' => false, 'message' => 'Quote not found'];
        }

        $advisorData = $this->fetchAdvisorData($quoteObject);
        $transaction = $this->fetchTransaction($modelType, $quoteId, $ep);
        if ($transaction->isEmpty()) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        $canSendDocuments = $this->canSendAndDownloadDocuments($ep->product_category, $quoteObject->quote_status_id, $transaction);
        if ($isECB || ! $isSalama) {
            $canSendDocuments = $canSendDocuments || ($isMedxOrEcb && $this->canSendSukoonMedexDocuments($ep->product_category, $quoteObject->quote_status_id, $transaction));
        }

        if (! $canSendDocuments) {
            LoggerService::info('Documents cannot be sent',
                extra: [
                    'et_ids' => $transaction->pluck('id'),
                    'ep_category' => $ep->product_category,
                    'quote_status' => $quoteObject->quote_status_id,
                ],
                context: ['ref_id' => $quoteObject->code]
            );

            return ['success' => false, 'message' => 'Documents cannot be sent'];
        }

        if ($isAlfredProtect) {
            return $this->sendAlfredProtectEmail($ep, $transaction, $quoteObject, $short_code, $attachmentsUrls, $advisorData);
        } elseif ($isSukoonMedex) {
            return $this->sendMedexEmail($short_code, $quoteObject, $transaction->first(), $attachments, $advisorData, $ep, $modelType, $isSalama);
        } elseif ($isECB) {
            return $this->sendECBEmail($transaction->first(), $quoteObject->id, $modelType);
        }
    }

    private function fetchAttachments($ep, $isAlfredProtect, $isSalama)
    {
        $attachments = [];
        $attachmentsUrls = [];

        if ($isSalama) {
            $url = self::SALAMA_POLICY_WORDINGS_URL;
            $file = file_get_contents($url);
            $attachments[] = [
                'Content' => base64_encode($file),
                'Name' => 'Policy Wordings.pdf',
                'ContentType' => 'application/pdf',
            ];

        } else {
            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $documents = json_decode($ep->company_documents);
            if (! empty($documents)) {
                foreach ($documents as $item) {
                    $path = $item->path;
                    $pwDoc = $path !== '' ? $websiteURL.$path : '';
                    if (! empty($path) && ! $isAlfredProtect) {
                        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                        $file = file_get_contents($pwDoc);
                        $mimeType = $fileInfo->buffer($file);
                        $attachments[] = [
                            'Content' => base64_encode(file_get_contents($pwDoc)),
                            'Name' => $ep->display_name.' - Policy Wordings.pdf',
                            'ContentType' => $mimeType,
                        ];
                    } else {
                        $attachmentsUrls[] = $pwDoc;
                    }
                }
            }
        }

        return [$attachments, $attachmentsUrls];
    }

    private function fetchAdvisorData($quoteObject)
    {
        $advisorData = [];
        if ($quoteObject->advisor) {
            $advisor = $quoteObject->advisor;
            $advisorData['email'] = $advisor->email;
            $advisorData['name'] = $advisor->name;
            $advisorData['phone'] = $advisor->mobile_no;

            $advisorData['profilePhotoPath'] = $advisor->profile_photo_path ?? '';
            $advisorData['mobileNo'] = $this->formatPhoneNumber($advisor->mobile_no ?? '');
            $advisorData['mobileNoWithOutSpace'] = str_replace('+', '', str_replace(' ', '', $advisor->mobile_no ?? ''));
            $advisorData['landlineNo'] = $this->formatPhoneNumber($advisor->landline_no ?? '');
            $advisorData['landlineNoWithoutSpaces'] = str_replace('+', '', str_replace(' ', '', $advisor->landline_no ?? ''));
        }

        return $advisorData;
    }

    // This function is only make and use for fullfill the email template requirement, due to recentemail template ui changes
    private function formatPhoneNumber($phoneNumber = '')
    {
        $prefixNumber = '';
        if (strpos($phoneNumber, '+') !== false) {
            $prefixNumber = '+';
        }

        if (! str_contains($phoneNumber, ' ')) {
            $onlyNumber = str_replace('+', '', $phoneNumber);
            $phoneNumber = $prefixNumber.implode(' ', str_split($onlyNumber, 3));
        }

        return $phoneNumber;
    }

    private function fetchTransaction($modelType, $quoteId, $ep, $selected = true, $shortCodes = [])
    {
        $optionsIds = $ep->prices ? $ep->prices->pluck('id') : [];
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

        $transactions = EmbeddedTransaction::where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id', '=', $quoteId],
        ])->whereIn('product_id', $optionsIds);

        if ($selected) {
            $transactions = $transactions->where('is_selected', true);
        }

        if (! empty($shortCodes)) {
            $transactions = $transactions->whereHas('product.embeddedProduct', function ($query) use ($shortCodes) {
                $query->whereIn('short_code', $shortCodes);
            });
        }

        return $transactions->get();
    }

    private function sendAlfredProtectEmail($ep, $transaction, $quoteObject, $short_code, $attachmentsUrls, $advisorData)
    {
        $strategy = $this->createStrategy($short_code, true);
        $attachmentsUrls[] = $strategy->getCertificateDocumentUrl($ep, $transaction[0], $quoteObject);
        $emailTemplateId = intval(ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_PROTECT_BOOK_POLICY_TEMPLATE)->value('value'));

        $firstName = $quoteObject->quoteRequestEntityMapping ? $quoteObject->first_name ?? '' : ($quoteObject->customer?->latestInsured?->first_name ?? $quoteObject->customer->insured_first_name) ?? '';
        $lastName = $quoteObject->quoteRequestEntityMapping ? $quoteObject->last_name ?? '' : ($quoteObject->customer?->latestInsured?->last_name ?? $quoteObject->customer->insured_first_name) ?? '';

        info('Send Alfred Protect Email Template ID: '.$emailTemplateId);
        $emailData = (object) [
            'quoteCdbId' => $short_code.'-'.$quoteObject->code,
            'customerName' => $firstName.' '.$lastName,
            'customerEmail' => $quoteObject->email,
            'advisorName' => $advisorData['name'] ?? null,
            'advisorEmailAddress' => $advisorData['email'] ?? null,
            'productName' => $ep->product_name,
            'advisorLandlineNo' => $advisorData['landline_no'] ?? null,
            'advisorMobileNo' => $advisorData['mobile_no'] ?? null,
            'documentUrl' => $attachmentsUrls,
        ];
        info('Send Alfred Protect Email Data: '.json_encode($emailData));

        $ccData = isset($advisorData['email']) ? [['email' => $advisorData['email'], 'name' => $advisorData['name']]] : [];

        $response = app(SendEmailCustomerService::class)->sendEmail($emailTemplateId, $emailData, 'policy-documents-alfred-protect', $ccData);
        info('Send Alfred Protect Email Response: '.json_encode($response));

        if ($response == 201) {
            return ['success' => true, 'message' => 'Certificate sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Error sending Certificate'];
        }
    }

    private function sendECBEmail($transaction, $quoteId, $modelType)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $quote = $this->getQuoteObject($modelType, $quoteId);

        $context = EpEcbService::buildContext($transaction->id, $quoteId, $quoteTypeId, $quote->code);
        dispatch(new EpSendDocumentJob($context));

        return ['success' => true, 'message' => 'Certificate sent successfully'];
    }

    private function sendMedexEmail($short_code, $quoteObject, $transaction, $attachments, $advisorData, $ep, $modelType, $isSalama)
    {
        if ($isSalama) {
            $pdf = $this->getPDF($short_code, $quoteObject, $transaction, $modelType);
            if ($pdf) {
                $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
                $url = $websiteURL.$pdf->doc_url;
                $file = file_get_contents($url);
                $attachments[] = [
                    'Content' => base64_encode($file),
                    'Name' => 'Salama_Certificate.pdf',
                    'ContentType' => 'application/pdf',
                ];
            }

        } else {
            $watermarkedDocuments = $transaction->documents()
                ->whereIn('document_type_code', QuoteDocumentsEnum::getSukoonInitialDocTypes())->get()
                ->where('is_watermarked', true);

            $watermarkedDocumentTypes = $watermarkedDocuments->pluck('document_type_code')->toArray();
            $missingReqWatermarkedDocTypes = array_diff(QuoteDocumentsEnum::getSukoonInitialDocTypes(), $watermarkedDocumentTypes);

            // make sure email required watermarked documents is not missing
            if (! empty($missingReqWatermarkedDocTypes)) {
                return ['success' => false, 'message' => 'Required watermarked document is not found'];
            }

            foreach ($watermarkedDocuments as $document) {
                $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
                $url = $websiteURL.$document->watermarked_doc_url;
                $file = file_get_contents($url);
                $attachments[] = [
                    'Content' => base64_encode($file),
                    'Name' => $document->original_name,
                    'ContentType' => 'application/pdf',
                ];
            }
        }

        $certificatesConfig = config('embedded-products.certificates');
        $driverOrRiderCover = $short_code == EmbeddedProductEnum::MDX ? 'Driver' : 'Rider';
        $subject = match ($short_code) {
            EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX => "Details of your {$driverOrRiderCover} medical cover purchase with InsuranceMarket.ae - {$short_code}-{$quoteObject->code}",
            default => "Thank you for your purchase of {$ep->product_name} with InsuranceMarket.ae - {$short_code}-{$quoteObject->code}",
        };

        $body = json_encode([
            'From' => config('constants.IM_FROM_EMAIL'),
            'ReplyTo' => $advisorData['email'] ?? null,
            'To' => $quoteObject->email,
            'Cc' => $advisorData['email'] ?? '',
            'Tag' => '',
            'TemplateAlias' => $certificatesConfig[$short_code]['email_template_alias'],
            'Attachments' => $attachments,
            'TemplateModel' => [
                'params' => [
                    'customerName' => $quoteObject->first_name.' '.$quoteObject->last_name,
                    'isMedex' => strtoupper($short_code) == EmbeddedProductEnum::MDX,
                    'productName' => $ep->product_name,
                    'productDescription' => $ep->description,
                    'advisor' => (object) $advisorData,
                ],
                'subject' => $subject,
            ],
            'MessageStream' => config('constants.EMBEDDED_PRODUCTS_POSTMARK_STREAM'),
        ], JSON_UNESCAPED_SLASHES);

        SendEPDocumentsJob::dispatch($body);

        return ['success' => true, 'message' => 'Certificate sent successfully'];
    }

    private function handleAjaxResponse($message, $status)
    {
        if (request()->ajax()) {
            return response()->json(['success' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }

    /**
     * Retrieves the PDF certificate for a specific product.
     *
     * @param  mixed  $short_code
     * @param  mixed  $quoteObject
     * @param  mixed  $transaction
     * @param  mixed  $modelType
     * @return mixed
     *
     * @throws \Exception
     */
    private function getPDF(
        $short_code,
        $quoteObject,
        $transaction,
        $modelType,
        $regenerate = false
    ) {

        $certificateDocument = null;
        $certificate_number = $transaction->certificate_number;
        $premium = $transaction->price_with_vat;
        $capturedAt = $transaction->payment_status_date;

        $certificateDocument = $transaction->documents->where('document_type_code', QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE)->first();
        if ($certificateDocument && $regenerate === false) {
            return $certificateDocument;
        }

        $certificatesConfig = config('embedded-products.certificates');
        if (isset($certificatesConfig[$short_code])) {
            $epMdxV2From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V2_FROM)->first();
            $epMdxV3From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V3_FROM)->first();
            $viewFile = $certificatesConfig[$short_code]['view_file'];
            if ($short_code === EmbeddedProductEnum::MDX) {
                if (
                    $epMdxV3From && ! empty($capturedAt)
                    && Carbon::parse($capturedAt)->gte(Carbon::parse($epMdxV3From->value))
                ) {
                    $viewFile = $certificatesConfig[$short_code]['view_file_v3'];

                } elseif (
                    $epMdxV2From && ! empty($capturedAt)
                    && Carbon::parse($capturedAt)->gte(Carbon::parse($epMdxV2From->value))
                ) {
                    $viewFile = $certificatesConfig[$short_code]['view_file_v2'];
                }
            }

            $strategy = $this->createStrategy($short_code);
            $viewData = $strategy->getPDFData($quoteObject, $certificate_number, $premium);
            $pdf = PDF::setOption(
                [
                    'isHtml5ParserEnabled' => true,
                    'dpi' => 150,
                ]
            )
                ->loadView($viewFile, compact('viewData'));

            $pdfContent = $pdf->output();
            $docUuid = uniqid();
            $title = "{$docUuid}_PolicyContract-{$certificate_number}.pdf";
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
            $documentType = DocumentType::where('code', QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE)->where('quote_type_id', $quoteTypeId)->first();
            $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$title;
            Storage::disk('azureIM')->put($filePathAzure, $pdfContent);
            if (! Storage::disk('azureIM')->exists($filePathAzure)) {
                throw new Exception('Error uploading document');
            }

            $documentData = [
                'doc_name' => $title,
                'original_name' => $title,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => 'application/pdf',
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'created_by_id' => null,
            ];

            $certificateDocument = $transaction->documents()->updateOrCreate(
                ['document_type_code' => $documentType->code],
                $documentData
            );
        }

        return $certificateDocument;
    }

    /**
     * Fetches the sold transaction list for a given EmbeddedProduct and optional filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function fetchGetSoldTransactionList(EmbeddedProduct $ep, $filters = [])
    {
        $strategy = $this->createStrategy($ep->short_code);

        $dataset = $strategy->filterReport($ep, $filters);

        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($ep->short_code);

        $dataset = $strategy->getTransactionData($dataset, $isAlfredProtect);

        return $dataset;
    }

    /**
     * This function use to get embedded product strategy
     *
     * @param [type] $shortCode
     * @param  bool  $isAlfredProtect
     * @return class
     */
    public function createStrategy($shortCode, $isAlfredProtect = false)
    {
        $strategy = null;
        $shortCode = strtoupper($shortCode);
        if ($shortCode == EmbeddedProductEnum::MDX) {
            $strategy = new MDX;
        } elseif ($shortCode == EmbeddedProductEnum::TRAVEL) {
            $strategy = new TravelAnnual;
        } elseif ($isAlfredProtect) {
            $strategy = new AlfredProtect;
        } elseif ($shortCode == EmbeddedProductEnum::RDX) {
            $strategy = new RDX;
        } elseif ($shortCode == EmbeddedProductEnum::COURIER) {
            $strategy = new COU;
        } elseif ($shortCode == EmbeddedProductEnum::ECB) {
            $strategy = new ECB;
        } else {
            $strategy = new EmbeddedProductStrategy;
        }

        return $strategy;
    }

    public function fetchCancelEmbeddedProducts($leadId, $modelType)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home, QuoteTypeId::Travel])) {
            return false;
        }

        $epTransaction = EmbeddedTransaction::where([
            ['quote_type_id', $quoteTypeId],
            ['quote_request_id', $leadId],
            ['is_selected', 1],
        ])
            ->whereHas('payments', function ($query) {
                $query->where('payment_status_id', PaymentStatusEnum::AUTHORISED);
            })
            ->with(['payments', 'quoteRequest'])
            ->whereHas('product.embeddedProduct', function ($query) {
                $query->where('product_type', EmbeddedProductTypeEnum::NON_INSURANCE);
            })
            ->get();

        if ($epTransaction->isNotEmpty()) {

            foreach ($epTransaction as $item) {
                $product_id = $item->product_id;
                $embedded_product_id = EmbeddedProductOption::find($product_id)?->embedded_product_id;
                $payment = $item['payments'][0];

                $data = [
                    'embedded_id' => $embedded_product_id,
                    'modelType' => ucfirst($modelType),
                    'amount' => $payment->total_amount,
                    'reason' => 'policy cancelled',
                    'uuid' => $item->quoteRequest->uuid,
                    'quote_id' => $item->quoteRequest->id,
                ];
                CancelEPJob::dispatch($data);
            }
        }
    }

    public function fetchCancelPayment($data)
    {
        $embeddedProductOptionsIds = EmbeddedProductOption::where('embedded_product_id', $data['embedded_id'])->pluck('id');
        $type = QuoteType::where('code', $data['modelType'])->first();

        $embededTransaction = EmbeddedTransaction::with(['payments', 'quoteRequest'])->where('quote_request_id', $data['quote_id'])
            ->where('quote_type_id', $type->id)
            ->where('is_selected', true)
            ->whereIn('product_id', $embeddedProductOptionsIds)
            ->get();

        if ($embededTransaction->isNotEmpty()) {
            if (! empty($embededTransaction[0]['payments'][0])) {
                $transaction = $embededTransaction[0];
                $payment = $transaction['payments'][0];
                $paymentStatus = $payment['payment_status_id'];

                $maxAmount = 0;
                $errorMessage = 'Cancel amount should not exceeded from transaction amount';
                if (in_array($paymentStatus, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PAID])) {
                    $maxAmount = $payment->premium_captured - $payment->premium_refunded;
                } elseif ($paymentStatus === PaymentStatusEnum::AUTHORISED) {
                    $maxAmount = $payment->premium_authorized - $payment->premium_refunded;
                } else {
                    $errorMessage = 'Invalid Payment status';
                }

                if ($maxAmount >= $data['amount']) {

                    // Remove all previous refund actions
                    PaymentAction::where('payment_code', $transaction->code)
                        ->where('is_fulfilled', 0)
                        ->where('action_type', 'REFUND')
                        ->where('is_manager_approved', 1)
                        ->delete();

                    $paymentSplit = PaymentSplits::where('code', $transaction->code)->orderBy('sr_no', 'desc')->first();
                    $sr = ! empty($paymentSplit) ? $paymentSplit->sr_no : 1;
                    PaymentAction::create([
                        'payment_code' => $transaction->code,
                        'is_fulfilled' => 0,
                        'action_type' => 'REFUND',
                        'reason' => $data['reason'],
                        'amount' => $data['amount'],
                        'created_by' => auth()->user()->email ?? 'system',
                        'is_manager_approved' => 1,
                        'sr_no' => $sr,
                    ]);
                    $data = [
                        'uuid' => $data['uuid'],
                        'type_id' => $type->id,
                        'code' => $transaction->code,
                        'payment_gateway_id' => $paymentSplit->payment_gateway_id,
                    ];
                    $processResponse = $this->processCancelPayment($data);

                    if (
                        $transaction->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER
                        && in_array($type->code, [quoteTypeCode::Car, quoteTypeCode::Home, quoteTypeCode::Travel])
                    ) {
                        CancelCourierQuoteOnMACRM::dispatch($transaction->quoteRequest, $type->id);
                    }

                    return [
                        'data' => $processResponse,
                        'code' => 200,
                    ];
                } else {
                    return [
                        'data' => [$errorMessage],
                        'code' => 403,
                    ];
                }
            } else {
                return [
                    'data' => ['Payment not exist'],
                    'code' => 403,
                ];
            }
        }

        return [
            'data' => ['Transaction does not exist'],
            'code' => 403,
        ];
    }

    public function fetchVoidPayment($data)
    {
        $embeddedProductOptionsIds = EmbeddedProductOption::where('embedded_product_id', $data['embedded_id'])->pluck('id');
        $type = QuoteType::where('code', $data['modelType'])->first();

        $embededTransaction = EmbeddedTransaction::with(['payments', 'quoteRequest'])
            ->where('quote_request_id', $data['quote_id'])
            ->where('quote_type_id', $type->id)
            ->where('is_selected', true)
            ->whereIn('product_id', $embeddedProductOptionsIds)
            ->first();

        if (! $embededTransaction) {
            $response = ['data' => ['Transaction does not exist'], 'code' => 403];
        } elseif ($embededTransaction->payment_status_id !== PaymentStatusEnum::AUTHORISED) {
            $response = ['data' => ['Invalid payment status'], 'code' => 403];
        } else {
            $payment = $embededTransaction->payments->first();
            $response = $this->fetchCancelPayment([
                'amount' => $payment->premium_authorized,
                'reason' => 'Payment void',
                ...$data,
            ]);
        }

        return $response;
    }

    private function processCancelPayment($data)
    {
        $planData = [
            'quoteUID' => $data['uuid'],
            'quoteTypeId' => $data['type_id'],
            'payments' => [
                [
                    'codeRef' => $data['code'],
                ],
            ],
        ];
        $paymentGatewayEndpoint = PaymentGatewayEnum::getName($data['payment_gateway_id']);
        info('Payment code: '.$data['uuid'].' Payment Gateway Endpoint: '.$paymentGatewayEndpoint);
        $response = Marshall::request('/payment/'.$paymentGatewayEndpoint.'/cancel', 'post', $planData);

        return $response;
    }

    public function fetchCapturePayment($leadId, $modelType)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home, QuoteTypeId::Travel])) {
            return false;
        }

        $epTransaction = EmbeddedTransaction::where([
            ['quote_type_id', $quoteTypeId],
            ['quote_request_id', $leadId],
            ['is_selected', 1],
            ['payment_status_id', PaymentStatusEnum::AUTHORISED],
        ])->with(['quoteRequest', 'product.embeddedProduct' => function ($query) {
            $query->where('product_category', EpCategoryEnum::BOLT_ON);
        }])
            ->get();

        $payload = [];
        $paymentGatewayEndpoint = '';
        if ($epTransaction->isNotEmpty()) {
            foreach ($epTransaction as $item) {
                $paymentSplit = PaymentSplits::where('code', $item->code)->orderBy('sr_no', 'desc')->first();
                if (empty($payload)) {
                    $payload = [
                        'quoteUID' => $item->quoteRequest->uuid,
                        'quoteTypeId' => $quoteTypeId,
                    ];
                    $paymentGatewayEndpoint = PaymentGatewayEnum::getName($paymentSplit->payment_gateway_id);
                }

                $sr = ! empty($paymentSplit) ? $paymentSplit->sr_no : 1;
                $payload['payments'][] = [
                    'codeRef' => $item->code.'-'.$sr,
                ];

                PaymentAction::where('payment_code', $item->code)
                    ->where('action_type', 'CAPTURE')
                    ->where('is_fulfilled', 0)
                    ->where('is_manager_approved', 1)
                    ->delete();

                PaymentAction::create([
                    'payment_code' => $item->code,
                    'action_type' => 'CAPTURE',
                    'amount' => $item->price_with_vat,
                    'is_fulfilled' => 0,
                    'created_by' => auth()->user()->email ?? 'system',
                    'reason' => 'Payment Captured',
                    'is_manager_approved' => 1,
                    'sr_no' => $sr,
                ]);
            }
        }

        if (empty($payload)) {
            return false;
        }

        try {
            LoggerService::info('Embedded product payment capture in process', extra: ['payload' => $payload]);
            Marshall::request("/payment/{$paymentGatewayEndpoint}/capture", 'post', $payload);
        } catch (Exception $e) {
            LoggerService::warning('Capture Payment Error: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function fetchGetDocuments($data)
    {
        $ep = $this->where('id', $data['epId'])->first();
        if (! $ep) {
            return false;
        }

        $transaction = $this->fetchTransaction($data['modelType'], $data['quoteId'], $ep, false);

        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($ep->short_code);
        $strategy = $this->createStrategy($ep->short_code, $isAlfredProtect);

        $epDocuments = $strategy->getDocumentList($ep, $transaction);

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($data['modelType']));
        $documentType = DocumentType::where('code', QuoteDocumentsEnum::EP)->where('quote_type_id', $quoteTypeId)->first();
        $canAddDocument = false;
        if ($transaction->isNotEmpty()) {
            $canAddDocument = in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::PAID]);
        }

        return [
            'ep' => $ep,
            'documents' => $epDocuments,
            'document_type' => $documentType,
            'can_add_document' => $canAddDocument,
        ];
    }

    public function fetchUploadQuoteDocument($data)
    {
        $ep = $this->where('id', $data['epId'])->first();
        if (! $ep) {
            return false;
        }

        $quoteObject = $this->getQuoteObject($data['modelType'], $data['quoteId']);
        $transaction = $this->fetchTransaction($data['modelType'], $data['quoteId'], $ep, false);

        $documentData = $this->prepareDocumentData($data['file'][0]['file'], $data['title'], $data['type'], $quoteObject, $data['modelType']);
        $transaction->first()->documents()->create($documentData);

        return true;
    }

    private function prepareDocumentData($file, $title, $type, $quoteObject, $modelType)
    {
        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $documentType = DocumentType::where('code', QuoteDocumentsEnum::EP)->where('quote_type_id', $quoteTypeId)->first();
        $fileNameAzure = $quoteObject->uuid.'_'.$docName;
        $docUuid = uniqid();
        $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');
        if ($filePathAzure == false) {
            throw new Exception('Error uploading document');
        }

        return [
            'doc_name' => $title,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $file->getClientMimeType(),
            'document_type_code' => $documentType->code,
            'document_type_text' => $type,
            'doc_uuid' => $docUuid,
            'created_by_id' => null,
        ];
    }

    public function fetchGenerateEPRenewal($batchName)
    {
        $batch = RenewalBatch::where('name', $batchName)->first();
        $capturedStartDate = Carbon::createFromFormat('Y-m-d', $batch->start_date)->subMonths(16)->startOfMonth()->format('Y-m-d H:i:s');
        $capturedEndDate = Carbon::createFromFormat('Y-m-d', $batch->end_date)->subMonths(10)->endOfMonth()->format('Y-m-d H:i:s');

        $ep = EmbeddedProductRepository::where('short_code', EmbeddedProductEnum::MDX)->with('prices')->first();
        $embeddedOptionIds = $ep->prices->pluck('id')->toArray();

        $quotes = DB::table('car_quote_request as c1')
            ->join('car_quote_request as c2', function ($join) {
                $join->on('c1.email', '=', 'c2.email')
                    ->on('c1.car_make_id', '=', 'c2.car_make_id')
                    ->on('c1.car_model_id', '=', 'c2.car_model_id')
                    ->on('c1.year_of_manufacture', '=', 'c2.year_of_manufacture')
                    ->whereColumn('c1.code', '!=', 'c2.code');
            })
            ->join('embedded_transactions as e', function ($join) {
                $join->on('e.quote_request_id', '=', 'c2.id')
                    ->where('e.quote_request_type', '=', 'App\\Models\\CarQuote');
            })
            ->join('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'e.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\EmbeddedTransaction');
            })
            ->where(function ($query) use ($batch) {
                $query->where('c1.renewal_batch', $batch->name)
                    ->orWhereBetween('c1.previous_policy_expiry_date', [$batch->start_date, $batch->end_date]);
            })
            ->where('c1.source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->whereBetween('p.captured_at', [$capturedStartDate, $capturedEndDate])
            ->where('e.payment_status_id', PaymentStatusEnum::CAPTURED)
            ->whereIn('e.product_id', $embeddedOptionIds)
            ->select('c1.id', 'c1.code as c1_code', 'e.product_id', 'e.price_without_vat', 'e.price_with_vat', 'e.vat')
            ->orderBy('c1.id', 'asc');

        if ($quotes->count() > 0) {
            $quotes->chunk(1000, function ($quoteBatch) use ($batchName) {
                $codes = $quoteBatch->pluck('c1_code')->map(function ($code) {
                    return 'MDX-'.$code;
                })->toArray();
                $epsToUpdate = EmbeddedTransaction::whereIn('code', $codes)->get()->pluck('code')->toArray();

                if (! empty($epsToUpdate)) {
                    EmbeddedTransaction::whereIn('code', $epsToUpdate)->update(['is_selected' => 1]);
                    info('EP Renewals - Updated EPs for batch: '.$batchName.' - count: '.count($epsToUpdate));
                }
            });
        }
    }

    public function saveEmbeddedTransaction($quote, $quoteTypeId)
    {
        $response = Ken::request('/save-embedded-transaction', 'post',
            ['quoteUID' => $quote->uuid, 'quoteTypeId' => $quoteTypeId]);
        if (isset($response->status) && $response->status == 200) {
            return $response->data;
        }

        return null;
    }

    /**
     * Check if the quote has an EpEcb payment with authorised or captured status
     * Only for CAR Quote With EP ECB
     *
     * @param int $quoteId
     * @return bool
     */
    public function checkIsEpEcbPaymentAuthorisedOrCaptured($quoteId): bool
    {
        return EmbeddedTransaction::where(['quote_type_id' => QuoteTypeId::Car, 'quote_request_id' => $quoteId, 'is_selected' => true])
            ->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED])
            ->whereHas('product.embeddedProduct', fn ($q) => $q->where('short_code', EmbeddedProductEnum::ECB))
            ->exists();
    }

    /**
     * Check if the CarMakeId is matched with the excluded vehicles of EpEcb
     * Only for CAR Quote With EP ECB
     *
     * @param int $makeId
     * @return bool
     */
    public function checkIsCarMakeIdMatchedWithExcludedEcbVehicle($makeId): bool
    {
        $carMake = CarMake::select('id', 'code')->find($makeId);
        if(empty($carMake?->code)) {
            return false;
        }
        return in_array($carMake->code, EpEcbExcludeVehicleEnum::CAR_MAKE_CODES);
    }

    /**
     * Check if the CarModelId is matched with the excluded vehicles of EpEcb
     * Only for CAR Quote With EP ECB
     *
     * @param int $modelId
     * @return bool
     */
    public function checkIsCarModelIdMatchedWithExcludedEcbVehicle($modelId): bool
    {
        $carModel = CarModel::select('id', 'code')->find($modelId);
        if(empty($carModel?->code)) {
            return false;
        }
        return in_array($carModel->code, EpEcbExcludeVehicleEnum::CAR_MODEL_CODES);
    }

    /**
     * Process cancel payment
     * Only for CAR Quote With EP ECB
     *
     * @param Quote $quote
     * @param int $quoteTypeId
     * @param string $reason
     * @return array
     */
    public function processEpEcbCancelPayment($quote, $quoteTypeId, $reason)
    {
        $extraLog = ['quote_id' => $quote?->id, 'quote_type_id' => $quoteTypeId, 'reason' => $reason];

        $modelType = QuoteTypes::getName($quoteTypeId);
        if($quoteTypeId != QuoteTypeId::Car || empty($quote?->id) || empty($reason)) {
            return ['success' => false, 'message' => 'Invalid quote, quote_type_id or reason'];
        }

        $epTransactionDetails = $this->getEpTransactionDetails($quoteTypeId, $quote->id, [EmbeddedProductEnum::ECB])->first();
        $epId = $epTransactionDetails->product->embedded_product_id ?? null;
        $payment = $epTransactionDetails->payments->first();

        $extraLog = [...$extraLog, 'epId' => $epId, 'amount' => $payment?->premium_authorized];

        if(empty($epId) || empty($payment?->premium_authorized)) {
            return ['success' => false, 'message' => 'Not Found - embedded_product_id or payment_amount'];
        }

        $cancelPaymentData = [
            'embedded_id' => $epId,
            'quote_id' => $quote->id,
            'modelType' => $modelType,
            'reason' => $reason,
            'amount' => $payment->premium_authorized,
            'uuid' => $quote->uuid,
        ];
        $response = app(EmbeddedProductRepository::class)->fetchCancelPayment($cancelPaymentData);

        if($response['code'] != 200) {
            return ['success' => false, 'message' => $response['data'][0] ?? 'Failed to cancel payment for Embedded Product (ECB)'];
        }

        return ['success' => true, 'message' => $response['data'][0] ?? 'Payment cancelled successfully for Embedded Product (ECB)'];
    }

    public function getEpTransactionDetails($quoteTypeId, $quoteId, $shortCodes = [])
    {
        return EmbeddedTransaction::with('payments:id,paymentable_id,paymentable_type,premium_authorized', 'product:id,embedded_product_id','product.embeddedProduct:id,short_code,insurance_provider_id')
            ->select('id', 'quote_type_id', 'quote_request_id', 'is_selected', 'payment_status_id', 'product_id')
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteId, 'is_selected' => true])
            ->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED])
            ->when(! empty($shortCodes), 
                fn ($query) => $query->whereHas('product.embeddedProduct', 
                    fn ($q) => $q->whereIn('short_code', $shortCodes)
                )
            )->get();
    }
}
