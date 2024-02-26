<?php

namespace App\Repositories;

use App\Enums\EpCategoryEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\SendEPDocumentsJob;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Models\GenericDocument;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use finfo;
use Illuminate\Support\Facades\DB;
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
     * @param    $quoteType
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
                    $price->delete();
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
        return $this->where($column, $value)->with(['insuranceProvider', 'placements.quoteType', 'prices'])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData($fetchType = 'all')
    {
        $query = $this->with(['insuranceProvider'])->latest('updated_at');

        if ($fetchType === 'active') {
            $query = $query->active();
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
            ['payment_status_id',  '=', PaymentStatusEnum::CAPTURED],
        ])->whereIn('product_id', $optionsIds)->get();

        $certificate_number = '';
        if ($transaction->isNotEmpty()) {
            $certificate_number = $transaction[0]['certificate_number'];
            $premium = $transaction[0]['price_with_vat'];
        }
        $short_code = $ep->short_code;
        $pdf = $this->getPDF($short_code, $quoteObject, $certificate_number, $premium);

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
            ->get();
        $modelType = QuoteType::where('id', '=', $quoteTypeId)->value('code');
        $ep->each(function ($item) use ($modelType, $quoteTypeId, $quoteRequestId) {

            $item->send_document_button = false;
            $optionsIds = $item->prices->pluck('id');

            $transaction = EmbeddedTransaction::where([
                ['quote_type_id', '=', $quoteTypeId],
                ['quote_request_id',  '=', $quoteRequestId],
                ['is_selected',  '=', true],
                ['payment_status_id',  '=', PaymentStatusEnum::CAPTURED],
            ])->whereIn('product_id', $optionsIds)->get();

            if ($item->product_category == EpCategoryEnum::BOLT_ON) {
                $quoteObject = $this->getQuoteObject($modelType, $quoteRequestId);

                if ($quoteObject->payment_status_id == PaymentStatusEnum::CAPTURED) {

                    if ($transaction->isNotEmpty()) {
                        $item->send_document_button = true;
                    }
                }
            } elseif ($item->product_category == EpCategoryEnum::STAND_ALONE) {

                if ($transaction->isNotEmpty()) {
                    $item->send_document_button = true;
                }
            }
        });

        return $ep;
    }

    public function fetchSendDocument($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];

        $ep = $this->where('id', $epId)->first();
        $product_name = $short_code = $product_description = '';
        if ($ep) {
            $product_name = $ep->product_name;
            $product_description = $ep->description;
            $short_code = $ep->short_code;
            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $documents = json_decode($ep->company_documents);
            if (! empty($documents)) {
                foreach ($documents as $item) {

                    $path = $item->path;
                    $pwDoc = $path !== '' ? $websiteURL.$path : '';
                    if (! empty($path)) {

                        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

                        $file = file_get_contents($pwDoc);
                        $mimeType = $fileInfo->buffer($file);
                        $attachments[] = [
                            'Content' => base64_encode(file_get_contents($pwDoc)),
                            'Name' => $ep->display_name.'- Policy Wordings.pdf',
                            'ContentType' => $mimeType,
                        ];
                    }
                }
            }
        }

        // ep multiple options
        $optionsIds = [];
        $premium = '';
        if ($ep->prices) {
            $optionsIds = $ep->prices->pluck('id');
        }
        $quoteObject = $this->getQuoteObject($modelType, $quoteId);

        $advisorData = [];
        // advisor data
        if ($quoteObject->advisor) {
            $advisor = $quoteObject->advisor;
            $advisorData['email'] = $advisor->email;
            $advisorData['name'] = $advisor->name;
            $advisorData['phone'] = $advisor->mobile_no;
        }

        // certificate generation
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $transaction = EmbeddedTransaction::where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id',  '=', $quoteId],
            ['is_selected',  '=', true],
            ['payment_status_id',  '=', PaymentStatusEnum::CAPTURED],
        ])->whereIn('product_id', $optionsIds)->get();

        $certificate_number = '';
        if ($transaction->isNotEmpty()) {
            $certificate_number = $transaction[0]['certificate_number'];
            $premium = $transaction[0]['price_with_vat'];
        }
        // send certificate only for medex
        $pdf = $this->getPDF($short_code, $quoteObject, $certificate_number, $premium);
        if($pdf) {
            $attachments[] = [
                'Content' => base64_encode($pdf->output()),
                'Name' => 'Salama_Certificate.pdf',
                'ContentType' => 'application/pdf',
            ];
        }

        $body = json_encode([
            'From' => config('constants.MA_FROM_EMAIL'),
            'ReplyTo' => isset($advisorData['email']) ? $advisorData['email'] : null,
            'To' => $quoteObject->email,
            'Tag' => '',
            'TemplateAlias' => 'embedded-products-payment-auth',
            'Attachments' => isset($attachments) ? $attachments : null,
            'TemplateModel' => [
                'params' => [
                    'customerName' => $quoteObject->first_name.' '.$quoteObject->last_name,
                    // For MEDEX pass true else false
                    'isMedex' => strtoupper($short_code) == 'MDX' ? true : false,
                    'productName' => $product_name,
                    'productDescription' => $product_description,
                    'advisor' => (object) $advisorData,
                ],
                'subject' => 'Thank you for your purchase of '.$product_name.' with InsuranceMarket.ae - '.$short_code.'-'.$quoteObject->code,
            ],
            'MessageStream' => config('constants.MA_POSTMARK_STREAM'),
        ], JSON_UNESCAPED_SLASHES);

        SendEPDocumentsJob::dispatch($body);
    }

    /**
     * Retrieves the PDF certificate for a specific product.
     *
     * @param string $short_code
     * @param object $quoteObject
     * @param string $certificate_number
     * @param float $premium
     * @return \PDF|null The PDF document or null if the short code is not defined in config.
     */
    private function getPDF(
        $short_code,
        $quoteObject,
        $certificate_number,
        $premium
    ) {
        $pdf = null;
        $certificatesConfig = config('embedded-products.certificates');
        $short_code = strtoupper($short_code);
        if(isset($certificatesConfig[$short_code])) {
            $viewData = [
                'name' => $quoteObject->first_name . ' ' . $quoteObject->last_name,
                'dob' => isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format('m/d/Y') : '',
                'emirates_id' => $quoteObject->customer->emirates_id_number ?? '',
                'plan_type' => 'Individual',
                'certificate_number' => $certificate_number, // plan no
                'plan_currency' => 'AED',
                'plan_term' => '1 Year effect from Plan Commencement date and Subject to Contribution Paid',
                'date_of_enrollment' => isset($quoteObject->policy_start_date) ? Carbon::parse($quoteObject->policy_start_date)->format('m/d/Y') : '', // Plan Commencement Date
                'plan_beneficiary' => 'As per Shari’ah',
                'policy_insurance_date' => isset($quoteObject->policy_issuance_date) ? Carbon::parse($quoteObject->policy_issuance_date)->format('m/d/Y') : '',
            ];
            $viewData['contribution_amount'] = $viewData['plan_currency'] . " {$premium}  (Including VAT) Per Annum";

            $pdf = PDF::setOption(
                [
                    'isHtml5ParserEnabled' => true,
                    'dpi' => 150,
                ]
            )
                ->loadView($certificatesConfig[$short_code]['view_file'], compact('viewData'));
        }

        return $pdf;
    }
}
