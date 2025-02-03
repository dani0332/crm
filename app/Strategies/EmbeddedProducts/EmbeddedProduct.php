<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\quoteTypeCode;
use App\Models\EmbeddedTransaction;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class EmbeddedProduct
{
    use GenericQueriesAllLobs;

    public function getPDFData($quoteObject, $certificate_number, $premium)
    {
        throw new Exception('Method not implemented');
    }

    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'ADVISOR NAME',
            'DATE OF ISSUANCE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'VEHICLE',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
            'CERTIFICATE NUMBER',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->advisor_name,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->age,
            $certificate->vehicle,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->certificate_number,
        ];
    }

    /**
     * Retrieves sold transaction data from a dataset.
     *
     * @return Collection
     */
    public function getTransactionData($dataset, $isAlfredProtect = false)
    {
        $dataset->each(function ($item) use ($isAlfredProtect) {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $quoteObject = $item->quoteRequest;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $advisorName = $quoteObject->advisor->name ?? '';
            $nationality = $quoteObject->customer->nationality->text ?? '';

            $age = isset($quoteObject->dob) ?
                floor(Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now())).' Years'
                : '';
            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
            } else {
                $firstName = $customer->insured_first_name ?? '';
                $lastName = $customer->insured_last_name ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->advisor_name = $advisorName;
            $item->payment_date = isset($item->captured_at) ? Carbon::parse($item->captured_at)->format($dateFormat) : '';
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $firstName.' '.$lastName;
            $item->dob = isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '';
            $item->age = $age;
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->nationality = $nationality ?? '';
            $item->email = $quoteObject->email ?? '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            $item->policy_issuance_date = $quoteObject->policy_issuance_date ?? '';
            $item->emirates_id_number = $customer->emirates_id_number ?? '';

            if ($isAlfredProtect) {
                $item->plan_type = EmbeddedProductEnum::{$item->product->embeddedProduct->short_code}()->value;
                $item->tax_invoice_no = $item->tax_invoice_no ?? '';
                $item->tax_invoice_buyer_no = $item->tax_invoice_buyer_no ?? '';
                $item->credit_note_no = $item->credit_note_no ?? '';
                $item->credit_note_buyer_no = $item->credit_note_buyer_no ?? '';
                $item->commission_with_vat = $item->commission_with_vat ?? '';
                $item->premium_with_vat = $item->contribution_amount;
            }

            $item = $this->processReportRecord($quoteObject, $item);

            return $item;
        });

        return $dataset;
    }

    protected function processReportRecord($quoteObject, $item)
    {
        $item->lob = quoteTypeCode::getName($quoteObject::class) ?? '';
        $carMake = $quoteObject->carMake->text ?? '';
        $carModel = $quoteObject->carModel->text ?? '';
        $item->vehicle = $carMake.' '.$carModel;

        return $item;
    }

    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.customer',
            'quoteRequest.customer.nationality',
            'quoteRequest.carMake',
            'quoteRequest.carModel',
            'quoteRequest.quoteStatus',
            'quoteRequest.advisor',
            'quoteRequest.quoteRequestEntityMapping',
        ];
    }

    public function filterReport($ep, $filters)
    {
        $productTransaction = EmbeddedTransaction::whereHas('product.embeddedProduct', function ($query) use ($ep) {
            $query->where('id', $ep->id);
        });
        $dataset = $productTransaction->with($this->getReportRelations())
            ->join('payments', function ($join) {
                $join->on('embedded_transactions.id', '=', 'payments.paymentable_id')
                    ->where('payments.paymentable_type', '=', 'App\\Models\\EmbeddedTransaction');
            })
            ->where('embedded_transactions.is_selected', true)
            ->where('embedded_transactions.payment_status_id', PaymentStatusEnum::CAPTURED)
            ->when(isset($filters['ref_id']), function ($query) use ($filters) {
                $query->where('embedded_transactions.code', 'like', "%{$filters['ref_id']}%");
            })
            ->when(isset($filters['months']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['months'])->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::parse($filters['months'])->endOfMonth()->format('Y-m-d');
                $query->whereBetween('payments.captured_at', [$startDate, $endDate]);

            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                $query->whereHas('quoteRequest', function ($query) use ($filters) {
                    $name = $filters['name'];
                    $query->where('first_name', 'like', "%{$name}%")
                        ->orWhere('last_name', 'like', "%{$name}%");
                });
            })
            ->when(isset($filters['email']), function ($query) use ($filters) {
                $query->whereHas('quoteRequest', function ($query) use ($filters) {
                    $email = $filters['email'];
                    $query->where('email', 'like', "%{$email}%");
                });
            })
            ->when(isset($filters['date_of_purchase']), function ($query) use ($filters) {
                $query->whereHas('quoteRequest', function ($query) use ($filters) {
                    $startDate = Carbon::parse($filters['date_of_purchase'][0])->startOfDay();
                    $endDate = Carbon::parse($filters['date_of_purchase'][1])->endOfDay();
                    $query->whereBetween('payments.captured_at', [$startDate, $endDate]);
                });
            });

        $sortBy = 'embedded_transactions.id';
        $sortOrder = 'desc';
        if (! empty($filters['sortBy']) && ! empty($filters['sortType'])) {
            $sortableColumns = [
                'payment_date' => 'payments.captured_at',
                'contribution_amount' => 'embedded_transactions.price_with_vat',
            ];
            $sortBy = $sortableColumns[$filters['sortBy']] ?? 'embedded_transactions.id';
            $sortOrder = $filters['sortType'] ?? 'desc';
        }

        $dataset = $dataset->orderBy($sortBy, $sortOrder);

        if (isset($filters['excel_export']) && $filters['excel_export'] == true) {
            $dataset = $dataset->get();
        } else {
            $dataset = $dataset->simplePaginate()->withQueryString();
        }

        return $dataset;
    }

    public static function checkAlfredProtect($product)
    {
        $product = strtoupper(trim($product));

        return in_array($product, EmbeddedProductEnum::getAlfredProtectCodes());
    }

    public function getDocumentList($ep, $transaction)
    {
        $epDocuments = $this->getPolicyWordings($ep);
        $epDocuments = array_merge($epDocuments, $this->getadditionalDocuments($transaction));

        return $epDocuments;
    }

    protected function getPolicyWordings($ep)
    {
        $epDocuments = [];

        // get policy wordings
        $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $documents = json_decode($ep->company_documents);
        if (! empty($documents)) {
            foreach ($documents as $item) {
                $path = $item->path;
                $pwDoc = $path !== '' ? $websiteURL.$path : '';
                if (! empty($path)) {
                    $epDocuments[] = [
                        'document_type' => 'Policy Wordings',
                        'document_number' => 'Not Applicable',
                        'url' => $pwDoc,
                        'path' => $item->path,
                    ];
                }
            }
        }

        return $epDocuments;
    }

    protected function getadditionalDocuments($transaction)
    {
        if ($transaction->isEmpty()) {
            return [];
        }

        $transaction = $transaction->first();
        $transaction->load('documents');
        $documents = $transaction->documents;
        if ($documents->isEmpty()) {
            return [];
        }

        $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        $documentNumbers = [
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER => $transaction['tax_invoice_buyer_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_INVOICE => $transaction['tax_invoice_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_CREDIT_RAISE_BY_BUYER => $transaction['credit_note_buyer_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_CREDIT => $transaction['credit_note_no'] ?? '',
            QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE => $transaction['certificate_number'] ?? '',
        ];

        $docs = $documents->map(function ($document) use ($documentNumbers, $websiteURL) {

            $documentNumber = $document->document_type_code === QuoteDocumentsEnum::EP ? $document->doc_name : $documentNumbers[$document->document_type_code] ?? '';

            return [
                'document_type' => $document->document_type_text,
                'document_number' => $documentNumber,
                'url' => $document->doc_url !== '' ? $websiteURL.$document->doc_url : '',
                'path' => $document->doc_url,
            ];
        })->toArray();

        return $docs;
    }
}
