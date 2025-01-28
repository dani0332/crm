<?php

namespace App\Repositories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EpCategoryEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\Marshall;
use App\Jobs\EP\CancelEPJob;
use App\Jobs\MACRM\CancelCourierQuoteOnMACRM;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\ProcessSyncAlfredProtect;
use App\Jobs\SendEPDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\CustomerAddress;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\GenericDocument;
use App\Models\PaymentAction;
use App\Models\PaymentSplits;
use App\Models\QuoteType;
use App\Services\SendEmailCustomerService;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;
use App\Strategies\EmbeddedProducts\MDX;
use App\Strategies\EmbeddedProducts\RDX;
use App\Strategies\EmbeddedProducts\TravelAnnual;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use finfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class EmbeddedProductRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

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
                'prices.transactions' => function ($query) use ($quoteRequestId) {
                    $query->where('quote_request_id', $quoteRequestId);
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
            if ($isAlfredProtect) {
                $isDocPresent = count($transaction) > 0 ? $transaction[0]->documents()->count() > 0 : false;
                $item->download_document_button = $isDocPresent && $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);

                if (auth()->user()->hasRole(RolesEnum::Engineering)) {
                    $documentCount = ($isDocPresent == true) ? $transaction[0]->documents()->count() : 0;
                    $item->sync_document_button = $documentCount < 5;
                }

            }

            $item->send_document_button = $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);
            $item->can_cancel_payment = $this->canCancelPayment($transaction->first(), $quoteTypeId);
        });

        return $ep;
    }

    private function canCancelPayment($transaction, $quoteTypeId)
    {
        if ($transaction && $transaction->payments->first()) {
            $payment = $transaction->payments->first();
            if ($payment->getAttributes()['payment_status_id'] == PaymentStatusEnum::CAPTURED) {

                if ($transaction->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER) {
                    $address = CustomerAddress::where('quote_uuid', $transaction->quoteRequest->uuid)->where('quote_type_id', $quoteTypeId)->first();

                    return empty($address?->type);

                }

                $paymentDate = Carbon::parse($payment->getAttributes()['captured_at']);

                return $paymentDate->diffInDays(Carbon::now()) <= 3;
            }
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

    public function fetchSendDocumentsByLead($leadId, $modelType, $epId = null, $resendEmail = false)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
            return false;
        }

        $epTransaction = EmbeddedTransaction::where([
            ['quote_type_id', $quoteTypeId],
            ['quote_request_id', $leadId],
            ['is_selected', 1],
        ])
            ->whereIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])
            ->with(['product.embeddedProduct']);

        if (! empty($epId)) {
            $ep = $this->where('id', $epId)->first();
            $optionsIds = [];
            if ($ep->prices) {
                $optionsIds = $ep->prices->pluck('id');
            }
            $epTransaction = $epTransaction->whereIn('product_id', $optionsIds);
        }

        if ($resendEmail) {
            $epTransaction->whereHas('product.embeddedProduct', function ($query) {
                $query->whereIn('short_code', [EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX]);
            });
        }

        $epTransaction = $epTransaction->get();

        if ($epTransaction->isNotEmpty()) {
            foreach ($epTransaction as $item) {
                $product_id = $item->product_id;
                $embedded_product_id = EmbeddedProductOption::find($product_id)->embedded_product_id;

                $isDocPresent = $item->documents->count() > 0;
                if (EmbeddedProductStrategy::checkAlfredProtect($item->product->embeddedProduct->short_code) && ! $isDocPresent) {
                    $quoteObject = $this->getQuoteObject($modelType, $leadId);
                    ProcessSyncAlfredProtect::dispatch($quoteObject);

                } elseif ($item->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER
                && ucwords($modelType) == quoteTypeCode::Car) {

                    $quoteObject = $this->getQuoteObject($modelType, $leadId);
                    SyncCourierQuoteWithMacrm::dispatch($quoteObject, $quoteTypeId);

                } else {

                    // EP Send documents
                    $data = [];
                    $data['quoteId'] = $leadId;
                    $data['modelType'] = $modelType;
                    $data['epId'] = $embedded_product_id;
                    $data['regenerate'] = $resendEmail;
                    $this->fetchSendDocument($data);
                }
            }
        }
    }

    public function fetchSyncDocument($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $quoteObject = $this->getQuoteObject($modelType, $quoteId);
        if (empty($quoteObject)) {
            return 'Quote not found';
        }

        ProcessSyncAlfredProtect::dispatch($quoteObject);
    }

    public function fetchSendDocument($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];
        $regenerate = $data['regenerate'];

        $ep = $this->where('id', $epId)->first();
        if (! $ep) {
            return 'Embedded Product not found';
        }

        $short_code = $ep->short_code;
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($short_code);

        [$attachments, $attachmentsUrls] = $this->fetchAttachments($ep, $isAlfredProtect);

        $quoteObject = $this->getQuoteObject($modelType, $quoteId);
        if (empty($quoteObject)) {
            return 'Quote not found';
        }

        $advisorData = $this->fetchAdvisorData($quoteObject);
        $transaction = $this->fetchTransaction($modelType, $quoteId, $ep);

        $canSendDocuments = $this->canSendAndDownloadDocuments($ep->product_category, $quoteObject->quote_status_id, $transaction);
        if (! $canSendDocuments) {
            info('Documents cannot be sent '.json_encode(['uuid' => $quoteObject->uuid, 'ep category' => $ep->product_category, 'quote status' => $quoteObject->quote_status_id, 'transaction' => $transaction]));

            return 'Documents cannot be sent';
        }

        if ($isAlfredProtect) {
            return $this->sendAlfredProtectEmail($ep, $transaction, $quoteObject, $short_code, $attachmentsUrls, $advisorData);
        } elseif (in_array($short_code, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX])) {
            return $this->sendMedexEmail($short_code, $quoteObject, $transaction->first(), $modelType, $attachments, $advisorData, $ep, $regenerate);
        }
    }

    private function fetchAttachments($ep, $isAlfredProtect)
    {
        $attachments = [];
        $attachmentsUrls = [];
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
                        'Name' => $ep->display_name.'- Policy Wordings.pdf',
                        'ContentType' => $mimeType,
                    ];
                } else {
                    $attachmentsUrls[] = $pwDoc;
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
        }

        return $advisorData;
    }

    private function fetchTransaction($modelType, $quoteId, $ep, $selected = true)
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

        return $transactions->get();
    }

    private function sendAlfredProtectEmail($ep, $transaction, $quoteObject, $short_code, $attachmentsUrls, $advisorData)
    {
        $strategy = $this->createStrategy($short_code, true);
        $attachmentsUrls[] = $strategy->getCertificateDocumentUrl($ep, $transaction[0], $quoteObject);
        $emailTemplateId = intval(ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_PROTECT_BOOK_POLICY_TEMPLATE)->value('value'));

        $firstName = $quoteObject->quoteRequestEntityMapping ? $quoteObject->first_name ?? '' : $quoteObject->customer->insured_first_name ?? '';
        $lastName = $quoteObject->quoteRequestEntityMapping ? $quoteObject->last_name ?? '' : $quoteObject->customer->insured_last_name ?? '';

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
            return $this->handleAjaxResponse('Certificate sent successfully.', 'success');
        } else {
            return $this->handleAjaxResponse('Error sending Certificate.', 'error');
        }
    }

    private function sendMedexEmail($short_code, $quoteObject, $transaction, $modelType, $attachments, $advisorData, $ep, $regenerate)
    {
        $pdf = $this->getPDF($short_code, $quoteObject, $transaction, $modelType, $regenerate);
        $certificatesConfig = config('embedded-products.certificates');

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
                'subject' => 'Thank you for your purchase of '.$ep->product_name.' with InsuranceMarket.ae - '.$short_code.'-'.$quoteObject->code,
            ],
            'MessageStream' => config('constants.EMBEDDED_PRODUCTS_POSTMARK_STREAM'),
        ], JSON_UNESCAPED_SLASHES);

        SendEPDocumentsJob::dispatch($body);

        return 'Certificate sent successfully';
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
        } else {
            $strategy = new EmbeddedProductStrategy;
        }

        return $strategy;
    }

    public function fetchCancelEmbeddedProducts($leadId, $modelType)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
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
            ->get();

        if ($epTransaction->isNotEmpty()) {

            foreach ($epTransaction as $item) {
                $product_id = $item->product_id;
                $embedded_product_id = EmbeddedProductOption::find($product_id)->embedded_product_id;
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

                    ];
                    $processResponse = $this->processCancelPayment($data);

                    if (
                        $transaction->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER
                        && $type->code == quoteTypeCode::Car
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

        $response = Marshall::request('/payment/checkout/cancel', 'post', $planData);

        return $response;
    }

    public function fetchCapturePayment($leadId, $modelType)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if (! in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
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
        if ($epTransaction->isNotEmpty()) {
            foreach ($epTransaction as $item) {
                if (empty($payload)) {
                    $payload = [
                        'quoteUID' => $item->quoteRequest->uuid,
                        'quoteTypeId' => $quoteTypeId,
                    ];
                }

                $paymentSplit = PaymentSplits::where('code', $item->code)->orderBy('sr_no', 'desc')->first();
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
            Marshall::request('/payment/checkout/capture', 'post', $payload);
        } catch (Exception $e) {
            Log::error('Capture Payment Error: '.$e->getMessage());
        }
    }

    public function fetchGetDocuments($data)
    {
        $ep = $this->where('id', $data['epId'])->first();
        if (! $ep) {
            return false;
        }

        $quoteObject = $this->getQuoteObject($data['modelType'], $data['quoteId']);
        $transaction = $this->fetchTransaction($data['modelType'], $data['quoteId'], $ep, false);

        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($ep->short_code);
        $strategy = $this->createStrategy($ep->short_code, $isAlfredProtect);

        $canSendDocuments = $this->canSendAndDownloadDocuments($ep->product_category, $quoteObject->quote_status_id, $transaction);
        if ($canSendDocuments) {
            $this->getPDF($ep->short_code, $quoteObject, $transaction->first(), $data['modelType']);
        }

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
}
