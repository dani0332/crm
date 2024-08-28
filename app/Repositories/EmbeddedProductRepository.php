<?php

namespace App\Repositories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EpCategoryEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\Marshall;
use App\Jobs\ProcessSyncAlfredProtect;
use App\Jobs\SendEPDocumentsJob;
use App\Models\ApplicationStorage;
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
use App\Strategies\EmbeddedProducts\TravelAnnual;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use finfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public function fetchDownloadCertificate($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];

        $quoteObject = $this->getQuoteObject($modelType, $quoteId);

        $ep = $this->where('id', $epId)->first();
        $premium = '';
        $optionsIds = [];
        if ($ep->prices) {
            $optionsIds = $ep->prices->pluck('id');
        }

        // certificate generation
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $transaction = EmbeddedTransaction::where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id',  '=', $quoteId],
            ['is_selected',  '=', true],
        ])->whereIn('product_id', $optionsIds)->get();

        $certificate_number = '';
        $capturedAt = null;

        if ($transaction->isNotEmpty()) {
            $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($ep->short_code);
            if ($isAlfredProtect) {
                $strategy = $this->createStrategy($ep->short_code, $isAlfredProtect);
                $attachments = $strategy->getCertificateDocument($ep, $transaction[0], $quoteObject);

                return response()->json(
                    ['attachments' => $attachments]
                );
            } else {
                $certificate_number = $transaction[0]['certificate_number'];
                $premium = $transaction[0]['price_with_vat'];
                $capturedAt = $transaction[0]['payment_status_date'];
            }
        }
        $short_code = $ep->short_code;
        $pdf = $this->getPDF($short_code, $quoteObject, $certificate_number, $premium, $capturedAt);

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf->stream()), 'name' => 'Salama_Certificate']);
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

        //generate unique uuid
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
            ->where('is_active', 1)
            ->with([
                'prices' => function ($query) {
                    $query->where('is_active', 1);
                },
                'prices.transactions' => function ($query) use ($quoteRequestId) {
                    $query->where('quote_request_id', $quoteRequestId);
                },
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

            $transaction = EmbeddedTransaction::with('documents')->where([
                ['quote_type_id', '=', $quoteTypeId],
                ['quote_request_id',  '=', $quoteRequestId],
                ['is_selected',  '=', true],
            ])->whereIn('product_id', $optionsIds)->get();

            $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($item->short_code);
            if ($isAlfredProtect) {
                $isDocPresent = count($transaction) > 0 ? $transaction[0]->documents()->count() > 0 : false;
                $item->download_document_button = $isDocPresent;

                if (auth()->user()->hasRole(RolesEnum::Engineering)) {
                    $documentCount = ($isDocPresent == true) ? $transaction[0]->documents()->count() : 0;
                    $item->sync_document_button = $documentCount < 5;
                }

            }

            $quoteObject = $this->getQuoteObject($modelType, $quoteRequestId);
            $item->send_document_button = $this->canSendDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);
        });

        return $ep;
    }

    private function canSendDocuments($productCategory, $quoteStatusId, $transaction)
    {
        $canSend = false;
        if ($productCategory == EpCategoryEnum::BOLT_ON) {
            if (in_array($quoteStatusId, [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])) {
                if ($transaction->isNotEmpty()) {
                    $canSend = true;
                }
            }
        } elseif ($productCategory == EpCategoryEnum::STAND_ALONE) {
            if ($transaction->isNotEmpty()) {
                $canSend = true;
            }
        }

        return $canSend;
    }

    public function fetchSendDocumentsByLead($leadId, $modelType, $epId = null)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        if ($quoteTypeId !== QuoteTypeId::Car) {
            return false;
        }

        $epTransaction = EmbeddedTransaction::where([
            ['quote_type_id', $quoteTypeId],
            ['quote_request_id', $leadId],
            ['is_selected', 1],
        ])->whereIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED]);

        if (! empty($epId)) {
            $ep = $this->where('id', $epId)->first();
            $optionsIds = [];
            if ($ep->prices) {
                $optionsIds = $ep->prices->pluck('id');
            }
            $epTransaction = $epTransaction->whereIn('product_id', $optionsIds);
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

                } else {

                    // EP Send documents
                    $data = [];
                    $data['quoteId'] = $leadId;
                    $data['modelType'] = $modelType;
                    $data['epId'] = $embedded_product_id;
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

        $ep = $this->where('id', $epId)->first();
        if (! $ep) {
            return 'Embedded Product not found';
        }

        $short_code = $ep->short_code;
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($short_code);

        [$attachments, $attachmentsUrls] = $this->fetchAttachments($ep, $isAlfredProtect);

        $optionsIds = $ep->prices ? $ep->prices->pluck('id') : [];
        $quoteObject = $this->getQuoteObject($modelType, $quoteId);
        if (empty($quoteObject)) {
            return 'Quote not found';
        }

        $advisorData = $this->fetchAdvisorData($quoteObject);
        $transaction = $this->fetchTransaction($modelType, $quoteId, $optionsIds);

        if (! $this->canSendDocuments($ep->product_category, $quoteObject->quote_status_id, $transaction)) {
            info('Documents cannot be sent '.json_encode(['uuid' => $quoteObject->uuid, 'ep category' => $ep->product_category, 'quote status' => $quoteObject->quote_status_id, 'transaction' => $transaction]));

            return 'Documents cannot be sent';
        }

        $certificate_number = $transaction->isNotEmpty() ? $transaction[0]['certificate_number'] : '';
        $premium = $transaction->isNotEmpty() ? $transaction[0]['price_with_vat'] : '';
        $capturedAt = $transaction->isNotEmpty() ? $transaction[0]['payment_status_date'] : null;

        if ($isAlfredProtect) {
            return $this->sendAlfredProtectEmail($ep, $transaction, $quoteObject, $short_code, $attachmentsUrls, $advisorData);
        } else {
            return $this->sendMedexEmail($short_code, $quoteObject, $certificate_number, $premium, $capturedAt, $attachments, $advisorData, $ep);
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

    private function fetchTransaction($modelType, $quoteId, $optionsIds)
    {
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

        return EmbeddedTransaction::where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id', '=', $quoteId],
            ['is_selected', '=', true],
        ])->whereIn('product_id', $optionsIds)->get();
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

    private function sendMedexEmail($short_code, $quoteObject, $certificate_number, $premium, $capturedAt, $attachments, $advisorData, $ep)
    {
        $pdf = $this->getPDF($short_code, $quoteObject, $certificate_number, $premium, $capturedAt);
        if ($pdf) {
            $attachments[] = [
                'Content' => base64_encode($pdf->output()),
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
            'TemplateAlias' => 'embedded-products-payment-auth',
            'Attachments' => $attachments,
            'TemplateModel' => [
                'params' => [
                    'customerName' => $quoteObject->first_name.' '.$quoteObject->last_name,
                    'isMedex' => strtoupper($short_code) == 'MDX',
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
     * @param  string  $short_code
     * @param  object  $quoteObject
     * @param  string  $certificate_number
     * @param  float  $premium
     * @param  null|Carbon  capturedAt
     * @return PDF|null The PDF document or null if the short code is not defined in config.
     */
    private function getPDF(
        $short_code,
        $quoteObject,
        $certificate_number,
        $premium,
        $capturedAt
    ) {
        $pdf = null;
        $epMdxV2From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V2_FROM)->first();
        $epMdxV3From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V3_FROM)->first();
        $certificatesConfig = config('embedded-products.certificates');
        if (isset($certificatesConfig[$short_code])) {
            $viewFile = $certificatesConfig[$short_code]['view_file'];

            if ($epMdxV3From && ! empty($capturedAt)
                && Carbon::parse($capturedAt)->gte(Carbon::parse($epMdxV3From->value))) {
                $viewFile = $certificatesConfig[$short_code]['view_file_v3'];

            } elseif ($epMdxV2From && ! empty($capturedAt)
            && Carbon::parse($capturedAt)->gte(Carbon::parse($epMdxV2From->value))) {
                $viewFile = $certificatesConfig[$short_code]['view_file_v2'];
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
        }

        return $pdf;
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
        if ($shortCode == 'MDX') {
            $strategy = new MDX;
        } elseif ($shortCode == EmbeddedProductEnum::TRAVEL) {
            $strategy = new TravelAnnual;
        } elseif ($isAlfredProtect) {
            $strategy = new AlfredProtect;
        } else {
            $strategy = new EmbeddedProductStrategy;
        }

        return $strategy;
    }

    public function fetchCancelPayment($data)
    {
        $embeddedProductOptionsIds = EmbeddedProductOption::where('embedded_product_id', $data['embedded_id'])->pluck('id');
        $type = QuoteType::where('code', $data['modelType'])->first();

        $embededTransaction = EmbeddedTransaction::with(['payments'])->where('quote_request_id', $data['quote_id'])
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
                        'created_by' => auth()->user()->email,
                        'is_manager_approved' => 1,
                        'sr_no' => $sr,
                    ]);
                    $data = [
                        'uuid' => $data['uuid'],
                        'type_id' => $type->id,
                        'code' => $transaction->code,

                    ];
                    $processResponse = $this->processCancelPayment($data);

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
        if ($quoteTypeId !== QuoteTypeId::Car) {
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
                    'created_by' => auth()->user()->email,
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
            $this->fetchSendDocumentsByLead($leadId, $modelType);
        } catch (Exception $e) {
            Log::error('Capture Payment Error: '.$e->getMessage());
        }
    }
}
