<?php

namespace App\Repositories;

use App\Models\EmbeddedProduct;
use App\Models\GenericDocument;
use App\Services\PostMarkService;
use App\Traits\GenericQueriesAllLobs;
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
     * @param $quoteType
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
                if (!in_array($price->id, array_column($data['pricings'], 'id'))) {
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
    public function fetchGetData()
    {
        return $this->with(['insuranceProvider'])->latest('updated_at')->simplePaginate();
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

        $advisorData = [];
        $viewData['name'] = $quoteObject->first_name . ' ' . $quoteObject->last_name;
        $viewData['dob'] = $quoteObject->dob;

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.ep_certificate', compact('viewData'));

        return response()->json(['data' => 'data:application/pdf;base64,' . base64_encode($pdf->stream()), 'name' => 'Certificate']);
    }

    /**
     * @return mixed
     */
    public function fetchUploadDocument($file, $title)
    {
        $type = 'embedded_product';
        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid() . '_' . $originalName);
        $fileMimeType = $file->getClientMimeType();

        $fileNameAzure = uniqid() . '_' . $type . '_' . $docName;
        $filePathAzure = $file->storeAs('documents/embedded_products', $fileNameAzure, 'azureIM');

        //generate unique uuid
        $docUuid = uniqid();
        while (GenericDocument::where('uuid', $docUuid)->first()) {
            $docUuid = uniqid() . rand(1, 100);
        }

        GenericDocument::create([
            'uuid' => $docUuid,
            'name' => $title . '_' . $originalName,
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
            ->with(['prices.transactions' => function ($query) use ($quoteRequestId) {
                $query->where('quote_request_id', $quoteRequestId);
            }])
            ->get();

        $ep->each(function ($item) {
            $item->send_document_button = strtolower($item->short_code) == 'mdx' && strtolower($item->product_name) == 'medex';
        });

        return $ep;
    }

    public function fetchSendDocument($data)
    {
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];

        $ep = $this->where('id', $epId)->first();
        $product_name = $short_code = '';
        if ($ep) {
            $product_name = $ep->product_name;
            $short_code = $ep->short_code;
        }
        $quoteObject = $this->getQuoteObject($modelType, $quoteId);

        $advisorData = [];
        if ($quoteObject->advisor) {
            $advisor = $quoteObject->advisor;
            $advisorData['email'] = $advisor->email;
            $advisorData['name'] = $advisor->name;
            $advisorData['phone'] = $advisor->mobile_no;
        }
        $viewData['name'] = $quoteObject->first_name . ' ' . $quoteObject->last_name;
        $viewData['dob'] = $quoteObject->dob;

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.ep_certificate', compact('viewData'));

        $attachments[] = [
            'Content' => base64_encode($pdf->output()),
            'Name' => 'Certificate.pdf',
            'ContentType' => 'application/pdf',
        ];

        $body = json_encode([
            'From' => config('constants.MA_FROM_EMAIL'),
            'ReplyTo' => isset($advisorData['email']) ? $advisorData['email'] : null,
            // 'To' => $quoteObject->email,
            'To' => 'nouman.hussain@insurancemarket.ae',
            'Tag' => '',
            'TemplateAlias' => 'embedded-products-payment-auth',
            'Attachments' => isset($attachments) ? $attachments : null,
            'TemplateModel' => [
                'params' => [
                    'customerName' => $quoteObject->first_name . ' ' . $quoteObject->last_name,
                    'isMedex' => true,
                    'productName' => 'demo',
                    'productDescription' => 'this is desc',
                    'advisor' => (object) $advisorData,
                ],
                'subject' => 'Thank you for your purchase of ' . $product_name . ' with Alfred - < ' . $short_code . '-' . $quoteObject->code . ' >',
            ],
            'MessageStream' => config('constants.MA_POSTMARK_STREAM'),
        ], JSON_UNESCAPED_SLASHES);

        $obj = new PostMarkService();

        $res = $obj->sendEmail($body);

        return $res;
    }
}
