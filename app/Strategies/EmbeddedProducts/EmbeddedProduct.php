<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\CourierSyncStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SageEmbeddedProductEnum;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedProductRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
            'EP Payment Status',
            'EP API Status',
            'EP Sage Status',
            'CERTIFICATE NUMBER',
            'Tax Invoice Number',
            'Tax Invoice Raised by Buyer Number',
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
            $certificate->ep_payment_status,
            $certificate->ep_api_status,
            $certificate->ep_sage_status,
            $certificate->certificate_number,
            $certificate->tax_invoice_no ?? '',
            $certificate->tax_invoice_buyer_no ?? '',
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
            $latestInsured = $quoteObject->latestInsured ?? null;

            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
                $emiratesIdNumber = '';
            } else {
                $firstName = ($latestInsured?->first_name ?? $customer?->insured_first_name) ?? '';
                $lastName = ($latestInsured?->last_name ?? $customer?->insured_last_name) ?? '';
                $emiratesIdNumber = ($latestInsured?->id_number ?? $customer?->emirates_id_number) ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->payment_date = isset($item->captured_at) ? Carbon::parse($item->captured_at)->format($dateFormat) : '';
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $firstName.' '.$lastName;
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            $item->ep_payment_status = $item->paymentStatus?->text ?? '';
            $item->ep_api_status = $item->policy_status ?? '';
            $item->ep_sage_status = $item->sage_status instanceof SageEmbeddedProductEnum
                ? $item->sage_status->value
                : ($item->sage_status ?? '');
            $item->emirates_id_number = $emiratesIdNumber;
            $item->tax_invoice_no = $item->tax_invoice_no ?? '';
            $item->tax_invoice_buyer_no = $item->tax_invoice_buyer_no ?? '';

            if ($item?->product?->embeddedProduct?->short_code === EmbeddedProductEnum::COURIER) {
                $item->sync_status = $item->courier_sync_status_info;
            }

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

        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $item->advisor_name = $quoteObject?->advisor?->name ?? '';
        $item->dob = isset($quoteObject?->dob) ? Carbon::parse($quoteObject?->dob)->format($dateFormat) : '';
        $item->nationality = $quoteObject?->customer?->nationality?->text ?? '';
        $item->policy_issuance_date = $quoteObject?->policy_issuance_date ?? '';
        $item->age = isset($quoteObject?->dob) ?
            floor(Carbon::parse($quoteObject?->dob)->diffInYears(Carbon::now())).' Years'
            : '';

        return $item;
    }

    /**
     * Process report record for Car/Bike renewals (RDX/COU) that support both quote types.
     *
     * @param  object  $quoteObject
     * @param  object  $item
     * @return object
     */
    protected function processCarBikeReportRecord($quoteObject, $item)
    {
        $item->lob = $this->resolveLineOfBusinessLabelForEmbeddedReportRow($item);

        if ($item->quote_type_id == QuoteTypeId::Car) {
            $carMake = $quoteObject?->carMake?->text ?? '';
            $carModel = $quoteObject?->carModel?->text ?? '';
            $item->vehicle = $carMake.' '.$carModel;
        } elseif ($item->quote_type_id == QuoteTypeId::Bike) {
            $make = $quoteObject?->bikeQuote?->bikeMake?->text ?? '';
            $model = $quoteObject?->bikeQuote?->bikeModel?->text ?? '';
            $item->vehicle = $make.' '.$model;
        } else {
            $item->vehicle = 'N/A';
        }

        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $item->advisor_name = $quoteObject?->advisor?->name ?? '';
        $item->dob = isset($quoteObject?->dob) ? Carbon::parse($quoteObject?->dob)->format($dateFormat) : '';
        $item->nationality = $quoteObject?->customer?->nationality?->text ?? '';
        $item->policy_issuance_date = $quoteObject?->policy_issuance_date ?? '';
        $item->age = isset($quoteObject?->dob) ?
            floor(Carbon::parse($quoteObject?->dob)->diffInYears(Carbon::now())).' Years'
            : '';

        return $item;
    }

    /**
     * LOB for the grid/export: prefer {@see EmbeddedTransaction::$quote_type_id}, then parse Courier EP ref (COU-CAR-, COU-HAM- for Home Appliances, …).
     */
    protected function resolveLineOfBusinessLabelForEmbeddedReportRow(object $item): string
    {
        $quoteTypeId = $item->quote_type_id ?? null;
        if ($quoteTypeId !== null && $quoteTypeId !== '') {
            $options = QuoteTypeId::getOptions();
            $id = (int) $quoteTypeId;
            if (array_key_exists($id, $options)) {
                return $options[$id];
            }
        }

        $fromRef = EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode((string) ($item->code ?? ''));

        return $fromRef ?? '';
    }

    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.customer',
            'quoteRequest.customer.nationality',
            'quoteRequest.latestInsured',
            'quoteRequest.carMake',
            'quoteRequest.carModel',
            'quoteRequest.quoteStatus',
            'quoteRequest.advisor',
            'quoteRequest.quoteRequestEntityMapping',
            'paymentStatus',
        ];
    }

    public function filterReport($ep, $filters)
    {
        $lobQuoteTypeIds = EmbeddedProductRepository::resolveLobFilterQuoteTypeIds(
            $ep->short_code,
            (array) ($filters['lob'] ?? [])
        );

        $productTransaction = EmbeddedTransaction::whereHas('product.embeddedProduct', function ($query) use ($ep) {
            $query->where('id', $ep->id);
        });

        $dataset = $productTransaction->with($this->getReportRelations())
            ->join('payments', function ($join) {
                $join->on('embedded_transactions.id', '=', 'payments.paymentable_id')
                    ->where('payments.paymentable_type', '=', 'App\\Models\\EmbeddedTransaction');
            })
            ->where('embedded_transactions.is_selected', true)
            ->when(
                $lobQuoteTypeIds !== null,
                fn ($query) => $this->applyLobFilterToEmbeddedReportQuery($query, $lobQuoteTypeIds)
            )
            ->when(! empty($filters['ep_payment_status'] ?? null), function ($query) use ($filters) {
                $query->whereIn('embedded_transactions.payment_status_id', (array) $filters['ep_payment_status']);
            })
            ->when(isset($filters['ref_id']), function ($query) use ($filters) {
                $query->where('embedded_transactions.code', 'like', "%{$filters['ref_id']}%");
            })
            ->when(isset($filters['certificate_number']), function ($query) use ($filters) {
                $query->where('embedded_transactions.certificate_number', 'like', "%{$filters['certificate_number']}%");
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
                $this->applyDateOfPurchaseFilterToEmbeddedReportQuery($query, $filters);
            })
            ->when(isset($filters['sync_status']), function ($query) use ($filters) {
                $query->filterBySyncStatus(CourierSyncStatusEnum::tryFrom($filters['sync_status']));
            })
            ->when(! empty($filters['ep_api_status'] ?? null), function ($query) use ($filters) {
                $query->whereIn('embedded_transactions.policy_status', (array) $filters['ep_api_status']);
            })
            ->when(! empty($filters['ep_sage_status'] ?? null), function ($query) use ($filters) {
                $this->applySageStatusFilterToEmbeddedReportQuery($query, $filters);
            })
            ->when(isset($filters['tax_invoice_no']), function ($query) use ($filters) {
                $query->where('embedded_transactions.tax_invoice_no', 'like', "%{$filters['tax_invoice_no']}%");
            })
            ->when(isset($filters['tax_invoice_buyer_no']), function ($query) use ($filters) {
                $query->where('embedded_transactions.tax_invoice_buyer_no', 'like', "%{$filters['tax_invoice_buyer_no']}%");
            });

        $dataset = $this->updateQuery($dataset, $filters);

        [$sortBy, $sortOrder] = $this->resolveEmbeddedReportSort($filters);

        $dataset = $dataset->orderBy($sortBy, $sortOrder);
        $dataset = $this->fetchEmbeddedReportResults($dataset, $filters);

        $dataset = $this->postFilterReportProcessing($dataset);

        return $dataset;
    }

    protected function applyLobFilterToEmbeddedReportQuery(EloquentBuilder|QueryBuilder $query, array $lobQuoteTypeIds): void
    {
        if ($lobQuoteTypeIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $segments = [];
        foreach ($lobQuoteTypeIds as $id) {
            $segment = EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter((int) $id);
            if ($segment !== null) {
                $segments[] = $segment;
            }
        }
        $segments = array_values(array_unique($segments));

        $query->where(function ($q) use ($lobQuoteTypeIds, $segments) {
            $q->whereIn('embedded_transactions.quote_type_id', $lobQuoteTypeIds);
            if ($segments !== []) {
                $q->orWhere(function ($sub) use ($segments) {
                    $sub->whereNull('embedded_transactions.quote_type_id')
                        ->where(function ($inner) use ($segments) {
                            foreach ($segments as $segment) {
                                $inner->orWhereRaw('LOWER(embedded_transactions.code) LIKE ?', [
                                    'cou-'.strtolower($segment).'-%',
                                ]);
                            }
                        });
                });
            }
        });
    }

    protected function applyDateOfPurchaseFilterToEmbeddedReportQuery(EloquentBuilder|QueryBuilder $query, array $filters): void
    {
        $query->whereHas('quoteRequest', function ($query) use ($filters) {
            $startDate = (isset($filters['date_of_purchase'][0]) && $filters['date_of_purchase'][0] != null && $filters['date_of_purchase'][0] != 'null')
                ? Carbon::parse($filters['date_of_purchase'][0])->startOfDay()
                : today()->startOfDay();
            $endDate = (isset($filters['date_of_purchase'][1]) && $filters['date_of_purchase'][1] != null && $filters['date_of_purchase'][1] != 'null')
                ? Carbon::parse($filters['date_of_purchase'][1])->endOfDay()
                : today()->endOfDay();
            $query->whereBetween('payments.captured_at', [$startDate, $endDate]);
        });
    }

    protected function applySageStatusFilterToEmbeddedReportQuery(EloquentBuilder|QueryBuilder $query, array $filters): void
    {
        $sageStatusIds = SageEmbeddedProductEnum::idsFromValues((array) $filters['ep_sage_status']);

        if (! empty($sageStatusIds)) {
            $query->whereIn('embedded_transactions.sage_status_id', $sageStatusIds);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveEmbeddedReportSort(array $filters): array
    {
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

        return [$sortBy, $sortOrder];
    }

    protected function fetchEmbeddedReportResults(EloquentBuilder|QueryBuilder $dataset, array $filters): Collection|Paginator
    {
        if (isset($filters['excel_export']) && $filters['excel_export'] == true) {
            return $dataset->get();
        }

        return $dataset->simplePaginate()->withQueryString();
    }

    protected function loadVehicleRelations($dataset)
    {
        // Group transactions by quote_type_id
        $carTransactions = $dataset->where('quote_type_id', QuoteTypeId::Car);
        $bikeTransactions = $dataset->where('quote_type_id', QuoteTypeId::Bike);

        // Load car-specific relations in a single query for the car group
        if ($carTransactions->isNotEmpty()) {
            $carTransactions->loadMissing([
                'quoteRequest.carMake',
                'quoteRequest.carModel',
            ]);
        }

        // Load bike-specific relations in a single query for the bike group
        if ($bikeTransactions->isNotEmpty()) {
            $bikeTransactions->loadMissing([
                'quoteRequest.bikeQuote',
                'quoteRequest.bikeQuote.bikeMake',
                'quoteRequest.bikeQuote.bikeModel',
            ]);
        }

        return $dataset;
    }

    protected function postFilterReportProcessing($dataset)
    {
        return $dataset;
    }

    protected function updateQuery($query, $filters)
    {
        return $query;
    }

    public static function checkAlfredProtect($product)
    {
        $product = strtoupper(trim($product));

        return in_array($product, EmbeddedProductEnum::getAlfredProtectCodes());
    }

    public static function checkSukoonMedex($product)
    {
        $product = strtoupper(trim($product));

        return in_array($product, EmbeddedProductEnum::getSukoonMedexCodes());
    }

    public function getDocumentList($ep, $transaction)
    {
        $isSalama = false;
        if (! $transaction->isEmpty() && in_array($ep->short_code, EmbeddedProductEnum::getSukoonMedexCodes())) {
            $paidAt = $transaction->first()->paid_at ?? null;
            $isSalama = $paidAt && Carbon::parse($paidAt)->lt(Carbon::parse(EmbeddedProductRepository::SALAMA_DATE));
        }

        $epDocuments = $this->getPolicyWordings($ep, $isSalama);
        $epDocuments = array_merge($epDocuments, $this->getadditionalDocuments($transaction));

        return $epDocuments;
    }

    protected function getPolicyWordings($ep, $isSalama)
    {
        if ($isSalama) {
            return [[
                'document_type' => 'Policy Wordings',
                'document_number' => 'Not Applicable',
                'url' => EmbeddedProductRepository::SALAMA_POLICY_WORDINGS_URL,
                'path' => EmbeddedProductRepository::SALAMA_POLICY_WORDINGS_URL,
                'is_policy_wordings' => true,
            ]];
        }
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
                        'is_policy_wordings' => true,
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
            QuoteDocumentsEnum::POLICY_SCHEDULE => $transaction['certificate_number'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER => $transaction['tax_invoice_buyer_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_INVOICE => $transaction['tax_invoice_no'] ?? '',
            QuoteDocumentsEnum::CAR_EP_TAX_INVOICE => $transaction['tax_invoice_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_CREDIT_RAISE_BY_BUYER => $transaction['credit_note_buyer_no'] ?? '',
            QuoteDocumentsEnum::CAR_TAX_CREDIT => $transaction['credit_note_no'] ?? '',
            QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE => $transaction['certificate_number'] ?? '',
        ];

        $docs = $documents->map(function ($document) use ($documentNumbers, $websiteURL) {

            $documentNumber = $document->document_type_code === QuoteDocumentsEnum::EP ? $document->doc_name : $documentNumbers[$document->document_type_code] ?? '';

            return [
                'id' => $document->id,
                'watermarked_doc_url' => ! empty($document->watermarked_doc_url) ? $websiteURL.$document->watermarked_doc_url : '',
                'watermarked_doc_path' => $document->watermarked_doc_url,
                'is_watermarked' => $document->is_watermarked ?? false,
                'document_type' => $document->document_type_text,
                'document_number' => $documentNumber,
                'url' => $document->doc_url !== '' ? $websiteURL.$document->doc_url : '',
                'path' => $document->doc_url,
                'is_policy_wordings' => false,
                'is_manual_override' => (bool) $document->is_manual_override,
            ];
        })->toArray();

        return $docs;
    }

    /**
     * Whether the quote satisfies product-specific eligibility. Base implementation always matches;
     * override in subclasses (e.g. ECB) when EP availability depends on quote data.
     */
    protected function isCriteriaMatched(mixed $quote): bool
    {
        return true;
    }

    public function isDisabled(EmbeddedTransaction $epTransaction): bool
    {
        return $this->preCheckEpTransactionIsDisabled($epTransaction);
    }

    protected function preCheckEpTransactionIsDisabled(EmbeddedTransaction $epTransaction): bool
    {
        $isPaymentPaid = in_array($epTransaction->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED]);

        return ! $epTransaction->is_active || $isPaymentPaid;
    }
}
