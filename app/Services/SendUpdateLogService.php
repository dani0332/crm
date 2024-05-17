<?php

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestAddOn;
use App\Models\CycleQuote;
use App\Models\Emirate;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\DB;

class SendUpdateLogService
{
    use GenericQueriesAllLobs;

    private function _getQuoteRelation($quoteModel, $quoteType)
    {
        $quoteRelations = [];
        $parentSkipColumns = [
            'insurer_quote_number',
            'insurance_provider_id',
            'payment_id',
            'plan_id',
            'premium',
            'policy_number',
            'policy_start_date',
            'policy_issuance_date',
            'price_vat_not_applicable',
            'price_with_vat',
            'price_without_vat',
            'policy_issuance_status_id',
            'policy_booking_date',
            'payment_status_id',
            'payment_status_date',
            'payment_gateway',
            'previous_quote_policy_number',
            'previous_policy_expiry_date',
            'previous_quote_policy_premium',
            'price_vat_applicable',
            'paid_at',
            'payment_reference',
            'payment_hash_code',
            'premium_authorized',
            'premium_captured',
            'premium_refunded',
            'prefill_plan_id',
            'prefill_plan_selected_at',
            'plan_selected_at',
            'quote_batch_id',
            'renewal_batch',
            'renewal_expiry_date',
            'vat',
        ];

        $requestDetailsSkipColumns = [
            'next_followup_date',
            'notes',
            'lost_reason_id',
            'transapp_code',
            'actual_premium',
            'discount_premium',
            'premium_vat',
            'excess',
            'insurer_quote_number',
            'addon_total_premium',
        ];

        switch (ltrim($quoteModel, '\\')) {
            case CarQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'carQuoteRequestDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => CarQuote::class,
                ];
                break;

            case HomeQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'homeQuoteRequestDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => HomeQuote::class,
                ];
                break;

            case HealthQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'healthQuoteRequestDetail' => [
                            'skipColumns' => array_merge($requestDetailsSkipColumns, ['is_quote_plan_email_sent']),
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => array_merge($parentSkipColumns, ['health_plan_type_id', 'price_starting_from', 'health_plan_co_payment_id']),
                    'parentClass' => HealthQuote::class,
                ];
                break;

            case LifeQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'lifeQuoteRequestDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => LifeQuote::class,
                ];
                break;

            case BusinessQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'businessQuoteRequestDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => BusinessQuote::class,
                ];
                break;

            case TravelQuote::class:
                $quoteRelations = [
                    'quoteRelations' => [
                        'travelQuoteRequestDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => TravelQuote::class,
                ];
                break;

            case PersonalQuote::class:
                $personalQuoteRelation = [];
                switch ($quoteType) {
                    case quoteTypeCode::Bike:
                        $personalQuoteRelation = [
                            'bikeQuote' => [
                                'skipColumns' => $parentSkipColumns,
                                'parentClass' => BikeQuote::class,
                                'quoteRelations' => [
                                    'bikeQuoteRequestDetail' => [
                                        'skipColumns' => ['next_followup_date', 'lost_reason_id', 'transapp_code'],
                                        'fillColumns' => ['advisor_assigned_date' => now()],
                                    ],
                                ],
                            ],
                        ];
                        break;

                    case quoteTypeCode::Yacht:
                        $personalQuoteRelation = [
                            'yachtQuote' => [
                                'skipColumns' => $parentSkipColumns,
                                'parentClass' => YachtQuote::class,
                                'quoteRelations' => [
                                    'yachtQuoteRequestDetail' => [
                                        'fillColumns' => ['advisor_assigned_date' => now()],
                                    ],
                                ],
                            ],
                        ];
                        break;

                    case quoteTypeCode::Pet:
                        $personalQuoteRelation = [
                            'petQuote' => [
                                'skipColumns' => $parentSkipColumns,
                                'parentClass' => PetQuote::class,
                                'quoteRelations' => [
                                    'petQuoteRequestDetail' => [
                                        'skipColumns' => ['next_followup_date', 'lost_reason_id', 'transapp_code'],
                                        'fillColumns' => ['advisor_assigned_date' => now()],
                                    ],
                                ],
                            ],
                        ];
                        break;

                    case quoteTypeCode::Cycle:
                        $personalQuoteRelation = [
                            'cycleQuote' => [
                                'parentClass' => CycleQuote::class,
                            ],
                        ];
                        break;

                    case quoteTypeCode::Jetski:
                        $personalQuoteRelation = [
                            'jetskiQuote' => [
                                'parentClass' => JetskiQuote::class,
                            ],
                        ];
                        break;
                }

                $quoteRelations = [
                    'quoteRelations' => [
                        'quoteDetail' => [
                            'skipColumns' => $requestDetailsSkipColumns,
                            'fillColumns' => ['advisor_assigned_date' => now()],
                        ],
                        'customerMembers' => [
                            'isMorph' => true,
                        ],
                        'quoteRequestEntityMapping' => [],
                    ],
                    'skipParentColumns' => $parentSkipColumns,
                    'parentClass' => PersonalQuote::class,
                ];

                if (! empty($personalQuoteRelation)) {
                    $quoteRelations['quoteRelations'] = array_merge($quoteRelations['quoteRelations'], $personalQuoteRelation);
                }
                break;

            default:
                $quoteRelations = [];
                break;
        }

        return $quoteRelations;
    }

    private function _createChildRelations($quoteModel, $relation, $relationObject, $modelRelationDetails, $replicateObject)
    {
        if (isset($modelRelationDetails['quoteRelations'][$relation]['quoteRelations'])) {
            $className = $modelRelationDetails['quoteRelations'][$relation]['parentClass'];
            $nestedRelationExist = $modelRelationDetails['quoteRelations'][$relation]['quoteRelations'];

            $nestedObject = $className::with(array_keys($nestedRelationExist))->find($relationObject->id);
            $getNestedRelations = $nestedObject->getRelations();

            $fillColumns = $modelRelationDetails['quoteRelations'][$relation]['fillColumns'] ?? [];
            if (in_array($relation, ['bikeQuote', 'yachtQuote', 'petQuote', 'cycleQuote', 'jetskiQuote'])) {
                $fillColumns = array_merge($fillColumns, ['personal_quote_id' => $replicateObject->id]);

                if (in_array($relation, ['bikeQuote', 'yachtQuote', 'petQuote'])) {
                    $fillColumns = array_merge($fillColumns, [
                        'code' => $replicateObject->code,
                        'uuid' => $replicateObject->uuid,
                        'quote_status_id' => QuoteStatusEnum::NewLead,
                    ]);
                }

                if ($relation == 'petQuote') {
                    $fillColumns = array_merge($fillColumns, ['parent_duplicate_quote_id' => $replicateObject->parent_duplicate_quote_id]);
                }
            }

            $nestedReplicateObject = $nestedObject->replicate($modelRelationDetails['quoteRelations'][$relation]['skipColumns'] ?? []);
            $nestedReplicateObject->fill($fillColumns)->save();

            foreach ($getNestedRelations as $nestedRelation => $nestedRelationObject) {
                if (class_exists($className) && method_exists($className, $nestedRelation) && ! empty($nestedRelationObject)) {
                    $this->_createChildRelations($className, $nestedRelation, $nestedRelationObject, $modelRelationDetails, $nestedReplicateObject);
                }
            }
        } else {
            if (isset($modelRelationDetails['quoteRelations'][$relation]['isMorph'])) {
                $fillColumns = $modelRelationDetails['quoteRelations'][$relation]['fillColumns'] ?? [];
                foreach ($replicateObject->{$relation} as $morphRelation) {
                    if ($relation == 'customerMembers') {
                        $customerMemberCode = generateQuoteMemberCode($morphRelation->customer_type, $morphRelation->customer_entity_id);
                        $fillColumns = array_merge($fillColumns, [
                            'code' => $customerMemberCode,
                        ]);
                    }
                    $newMorphRelation = $morphRelation->replicate($modelRelationDetails['quoteRelations'][$relation]['skipColumns'] ?? [])
                        ->fill($fillColumns);
                    $replicateObject->{$relation}()->save($newMorphRelation);
                }
            } else {
                $fillColumns = $modelRelationDetails['quoteRelations'][$relation]['fillColumns'] ?? [];
                $newRelation = $relationObject->replicate($modelRelationDetails['quoteRelations'][$relation]['skipColumns'] ?? [])
                    ->fill($fillColumns);
                $replicateObject->{$relation}()->save($newRelation);
            }
        }
    }

    public function createChildLead($quoteModel, $requestData, $quoteTypeCode)
    {
        $modelRelationDetails = $this->_getQuoteRelation($quoteModel, $quoteTypeCode);
        $quoteObject = $quoteModel::with(array_keys($modelRelationDetails['quoteRelations']))->find($requestData['ref_id']);

        $countChildRecords = $quoteModel::where('code', 'like', '%'.$quoteObject->code.'-%')->count();
        $childLeadDetails = [
            'childLeadsCount' => $countChildRecords,
            'parent_ref_id' => $quoteObject->code,
        ];

        if ($quoteTypeCode == quoteTypeCode::Business) {
            $childLeadDetails['businessTypeOfInsurance'] = $quoteObject->business_type_of_insurance_id;
        }

        if ($countChildRecords == 0) {

            $countChildRecords++;
            $explodeQuoteLink = explode('/', $quoteObject->quote_link);
            $explodeQuoteLink[array_key_last($explodeQuoteLink)] = $quoteObject->code.'-'.$countChildRecords;

            $getRelations = $quoteObject->getRelations();
            $replicateObject = $quoteObject->replicate($modelRelationDetails['skipParentColumns']);
            $replicateObject->fill([
                'code' => $quoteObject->code.'-'.$countChildRecords,
                'uuid' => $quoteObject->uuid.'-'.$countChildRecords,
                'quote_status_id' => QuoteStatusEnum::NewLead,
                'parent_duplicate_quote_id' => $quoteObject->code,
                'quote_link' => implode('/', $explodeQuoteLink),
            ])->save();

            foreach ($getRelations as $relation => $relationObject) {
                $className = $modelRelationDetails['parentClass'];
                if (method_exists($className, $relation) && $relationObject != null && ! empty($relationObject->toArray())) {
                    $this->_createChildRelations($className, $relation, $relationObject, $modelRelationDetails, $replicateObject);
                }
            }

            $childLeadDetails = array_merge($childLeadDetails, [
                'uuid' => $replicateObject->uuid,
                'ref_id' => $replicateObject->code,
                'quote_type_code' => $quoteTypeCode,
            ]);
        }

        return $childLeadDetails;
    }

    public function linkedQuoteDetails($quoteTypeCode, $quote)
    {
        $quoteTypeId = QuoteTypeId::getValue($quoteTypeCode);
        $quoteModel = $this->getModelObject($quoteTypeCode);
        $childRecords = $quoteModel::where('code', 'like', '%'.$quote->code.'-%')->get();

        $_return = [
            'quote_type_id' => $quoteTypeId,
            'parent_lead_ref_id' => '',
            'uuid' => '',
            'childLeadsCount' => $childRecords->count(),
            'childLeads' => '',
            'childLeadsUuid' => '',
        ];

        if (! empty($quote->parent_duplicate_quote_id)) {
            $_return['parent_lead_ref_id'] = $quote->parent_duplicate_quote_id;
            $_return['uuid'] = explode('-', $quote->parent_duplicate_quote_id)[1];
        }

        if ($childRecords->count() <= 1) {
            $_return['childLeads'] = $childRecords->value('code');
            $_return['childLeadsUuid'] = $childRecords->value('uuid');
        }

        return $_return;
    }

    public function isNegativeValue($sendUpdateLog): bool
    {
        $category = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            return true;
        }

        if ($category == SendUpdateLogStatusEnum::EF) {
            $option = LookupRepository::where('id', $sendUpdateLog->option_id)->value('code');
            if (in_array($option, [
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function getInvoiceDescription($sendUpdateLog, $quote, $quoteType, $insurance_provider_id)
    {
        $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
        $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();

        $sendUpdateLogCategory = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        $invoiceDescription = $insuranceProviderCode.'-'.$quoteType.'-'.$quote->policy_number;

        if ($sendUpdateLogCategory == SendUpdateLogStatusEnum::EF) {
            $invoiceDescription = 'E.'.$invoiceDescription;
        } elseif (in_array($sendUpdateLogCategory, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            $invoiceDescription = 'CI.'.$invoiceDescription;
        } elseif ($sendUpdateLogCategory == SendUpdateLogStatusEnum::CPD) {
            $reversalInvoiceDescription = 'R.'.$invoiceDescription;
            $invoiceDescription = 'C.'.$invoiceDescription;
        }

        return [
            'broker_invoice_number' => $insuranceProviderCode.$insuranceProviderLeadCount,
            'invoice_description' => $invoiceDescription,
            'reversal_invoice_description' => $reversalInvoiceDescription ?? '',
            'transaction_payment_status' => $sendUpdateLog->transaction_payment_status ?? '',
        ];
    }

    public function getPayments($quoteId, $quoteUuid, $quoteType)
    {
        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $payments = $repository::getBy('uuid', $quoteUuid)->payments;
        } else {
            $quoteServiceFile = app(getServiceObject($quoteType));
            $payments = $quoteServiceFile->getEntityPlain($quoteId)?->payments ?? null;
            if (! is_null($payments)) {
                $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);
            }
        }

        return $payments;
    }

    public function getReversalEntries($data): object
    {
        $payments = $this->getPayments($data['quoteId'], $data['quoteUuid'], $data['quoteType']);

        return collect($payments)->where('insurer_tax_number', $data['taxInvoiceNo'])->first();
    }

    public function getUploadedDocuments($sendUpdateLog): array
    {
        return $sendUpdateLog->documents()->pluck('document_type_code')->toArray();
    }

    public function getUpdateButtonStatus($sendUpdateLog): string
    {
        $sendUpdateCode = $sendUpdateLog->category->code;
        $uploadedDocuments = $this->getUploadedDocuments($sendUpdateLog);
        $requiredDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];

        // check if required documents not uploaded then show Send Update to Customer.
        $requiredDocumentsCheck = count(array_diff($requiredDocuments, $uploadedDocuments));

        // send update to customer button visibility validations.
        if ($sendUpdateCode != SendUpdateLogStatusEnum::CPD && $requiredDocumentsCheck == count($requiredDocuments)) {
            return SendUpdateLogStatusEnum::SUC;
        }

        if ($sendUpdateCode != SendUpdateLogStatusEnum::CPU && $requiredDocumentsCheck == 0) {
            return SendUpdateLogStatusEnum::SU;
        }

        return false;
    }

    public function getSendToCustomerValidation($sendUpdateId): string
    {
        $sendUpdate = SendUpdateLogRepository::getLogByid($sendUpdateId);

        $sendUpdateToCustomerValidation = in_array($sendUpdate->category->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]);
        $uploadedDocuments = $this->getUploadedDocuments($sendUpdate);

        if ($sendUpdateToCustomerValidation && ! in_array(DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, $uploadedDocuments)) {
            return 'Please note your current action will only send the update to the customer.';
        }

        return '';
    }

    public function mergeBookingDetails($bookingDetails, $sendUpdateLog)
    {
        $data = [
            'reversal_invoice' => $sendUpdateLog->reversal_invoice ?? null,
            'booking_date' => $sendUpdateLog->booking_date,
            'invoice_description' => $sendUpdateLog->invoice_description,
            'broker_invoice_number' => $sendUpdateLog->broker_invoice_number,
            'transaction_payment_status' => $sendUpdateLog->transaction_payment_status,
            'invoice_date' => $sendUpdateLog->invoice_date,
            'insurer_tax_invoice_number' => $sendUpdateLog->insurer_tax_invoice_number,
            'insurer_commission_invoice_number' => $sendUpdateLog->insurer_commission_invoice_number,
            'discount' => $sendUpdateLog->discount,
            'commission_percentage' => $sendUpdateLog->commission_percentage,
            'commission_vat_not_applicable' => $sendUpdateLog->commission_vat_not_applicable,
            'vat_on_commission' => $sendUpdateLog->vat_on_commission,
            'commission_vat_applicable' => $sendUpdateLog->commission_vat_applicable,
            'total_commission' => $sendUpdateLog->total_commission,
            'total_vat_amount' => $sendUpdateLog->total_vat_amount,
            'price_vat_applicable' => $sendUpdateLog->price_vat_applicable,
            'price_vat_not_applicable' => $sendUpdateLog->price_vat_not_applicable,
            'total_price' => $sendUpdateLog->total_price,
        ];

        return array_merge($bookingDetails, $data);
    }

    public function isPaymentVisible($categoryCode, $optionCode): bool
    {
        // categories in which we have to show manage payments.
        $categories = [
            SendUpdateLogStatusEnum::EF,
            SendUpdateLogStatusEnum::CPD,
        ];

        // if below options are not selected then we have to show manage payments, these are related to Endorsement Financial.
        $options = [
            SendUpdateLogStatusEnum::MPC,
            SendUpdateLogStatusEnum::MDOM,
            SendUpdateLogStatusEnum::MDOV,
            SendUpdateLogStatusEnum::ED,
            SendUpdateLogStatusEnum::DM,
        ];

        return in_array($categoryCode, $categories) && ! in_array($optionCode, $options);
    }

    public function isPolicyDetailsVisible($categoryCode, $optionCode): bool
    {
        return $categoryCode == SendUpdateLogStatusEnum::CPD ||
               ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::PPE);
    }

    public function getSendUpdatePayments($sendUpdateLog)
    {
        $payments = $sendUpdateLog->payments;
        if ($payments) {
            $payments->load(['paymentSplits', 'paymentStatus', 'paymentMethod', 'insuranceProvider', 'paymentStatusLog', 'paymentSplits.paymentStatus', 'paymentSplits.documents', 'paymentSplits.paymentMethod']);
        }

        return $payments;
    }

    public function updatePaymentDetails($sendUpdateLog)
    {
        // Update Payment Details
        $payment = Payment::where('send_update_log_id', $sendUpdateLog->id)->first();
        $sendUpdatePaymentDetails = [
            'policy_expiry_date' => $sendUpdateLog->expiry_date,
            'invoice_description' => $sendUpdateLog->invoice_description,
            'broker_invoice_number' => $sendUpdateLog->broker_invoice_number,
            'insurer_tax_number' => $sendUpdateLog->insurer_tax_invoice_number,
            'insurer_commmission_invoice_number' => $sendUpdateLog->insurer_commission_invoice_number,
            'commmission_percentage' => $sendUpdateLog->commission_percentage,
            'commission_vat_not_applicable' => $sendUpdateLog->commission_vat_not_applicable,
            'commission_vat_applicable' => $sendUpdateLog->commission_vat_applicable,
            'commission' => $sendUpdateLog->total_commission,
            'insurer_invoice_date' => $sendUpdateLog->invoice_date,
            // 'commission_vat' => $sendUpdateLog->vat_on_commission, // Didn't find respective column in send_update_log table
            // 'commission_without_vat' => $sendUpdateLog->commission_vat_applicable, // Didn't find respective column in send_update_log table
            // 'policy_due_date' => $sendUpdateLog->commission_vat_applicable, // Didn't find respective column in send_update_log table
        ];

        return $payment->update($sendUpdatePaymentDetails);
    }

    public function sendUpdateToSage($sendUpdateRequest, $sendUpdateLog)
    {
        $categoryCode = $sendUpdateLog->category->code;
        $quoteModel = $this->getModelObject($sendUpdateRequest->quoteType);
        $quote = $quoteModel::where('id', $sendUpdateRequest->quoteRefId)->first();

        if ($categoryCode == SendUpdateLogStatusEnum::EF) {
            $sageResponse = app(SageApiService::class)->handleDocumentsToSage(
                $sendUpdateRequest, $quote, [
                    'type' => SageEnum::PT_SEND_UPDATE,
                    'send_update_type' => SageEnum::SUT_NORMAL,
                    'category' => $categoryCode,
                    'option' => $sendUpdateLog->option->code,
                ]
            );

            return $sageResponse;
        }

        if ($categoryCode == SendUpdateLogStatusEnum::CPD) {
            $sageResponse = app(SageApiService::class)->handleDocumentsToSage(
                $sendUpdateRequest, $quote, [
                    'type' => SageEnum::PT_SEND_UPDATE,
                    'send_update_type' => SageEnum::SUT_REVE_CORR,
                    'category' => $categoryCode,
                    'send_update_log' => $sendUpdateLog,
                ]
            );

            return $sageResponse;
        }

        return ['status' => false, 'message' => 'Something went wrong'];
    }

    public function updatesMoveToLead($sendUpdateRequest, $sendUpdateLog)
    {
        $categoryCode = $sendUpdateLog->category?->code;
        $optionCode = $sendUpdateLog->option?->code;
        $quoteModel = $this->getModelObject($sendUpdateRequest->quoteType);
        $quote = $quoteModel::where('id', $sendUpdateRequest->quoteRefId)->first();

        try {
            DB::beginTransaction();

            if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD])) {

                Payment::where('send_update_log_id', $sendUpdateLog->id)->update([
                    'paymentable_id' => $quote->id,
                    'paymentable_type' => ltrim($quoteModel, '\\'),
                ]);

                if ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::PPE) {
                    $quote->update(['renewal_expiry_date' => $sendUpdateLog->expiry_date]);
                }

                if ($categoryCode == SendUpdateLogStatusEnum::CPD) {
                    $quote->update([
                        'policy_number' => $sendUpdateLog->policy_number,
                        'policy_start_date' => $sendUpdateLog->start_date,
                        'policy_issuance_date' => $sendUpdateLog->issuance_date,
                        'renewal_expiry_date' => $sendUpdateLog->expiry_date,
                        'insurer_quote_number' => $sendUpdateLog->insurer_quote_number,
                        'policy_issuance_status_id' => $sendUpdateLog->issuance_status_id,
                        'policy_booking_date' => $sendUpdateLog->booking_date,
                    ]);
                }
            }

            if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::CPD])) {
                if ($sendUpdateRequest->quoteType == quoteTypeCode::Car && $categoryCode == SendUpdateLogStatusEnum::EF) {
                    if (! empty($sendUpdateLog->car_addons)) { // will work on Add optional cover.
                        foreach ($sendUpdateLog->car_addons as $addonId) {
                            CarQuoteRequestAddOn::updateOrCreate([
                                'quote_request_id' => $quote->id,
                                'addon_option_id' => $addonId,
                            ], [
                                'quote_request_id' => $quote->id,
                                'addon_option_id' => $addonId,
                                'price' => 0,
                            ]);
                        }
                    } elseif (! empty($sendUpdateLog->emirates_registration)) { // will work on Change of Emirate.
                        $quote->update(['emirate_of_registration_id' => $sendUpdateLog->emirates_registration]);
                    } elseif (! empty($sendUpdateLog->seating_capacity) && $sendUpdateLog->seating_capacity != 0) { // will work on Change in seating capacity.
                        $quote->update(['seat_capacity' => $sendUpdateLog->seating_capacity]);
                    }
                }
                if ($categoryCode === SendUpdateLogStatusEnum::CIR) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
                        'quote_batch_id' => null,
                    ]);
                    (new AllocationService())->deductLeadAllocationCount($quoteModel, $sendUpdateRequest->quoteUuid);
                }
                $sendUpdateLog->update([
                    'booking_date' => now(),
                    'status' => SendUpdateLogStatusEnum::UPDATE_BOOKED,
                ]);
            }

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            info('Send update Lead impact Failed - Error : '.$exception->getMessage());

            return ['status' => false, 'message' => 'Update not booked'];
        }

        return ['status' => true, 'message' => 'Update booked'];
    }

    public function getPaymentCode($quoteCode): string
    {
        $countPayments = PaymentRepository::getPaymentsByQuoteCode($quoteCode);

        if ($countPayments < 2) {
            return $quoteCode.'-1';
        }

        return $quoteCode.'-'.$countPayments;
    }

    public function checkSendUpdatePermission($sendUpdateType): bool
    {
        switch ($sendUpdateType) {
            case SendUpdateLogStatusEnum::EF:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_ENDO_FIN_ADD);
            case SendUpdateLogStatusEnum::EN:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_ENDO_NON_FIN_ADD);
            case SendUpdateLogStatusEnum::CI:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD);
            case SendUpdateLogStatusEnum::CIR:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD);
            case SendUpdateLogStatusEnum::CPU:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_UPLOAD_ADD);
            case SendUpdateLogStatusEnum::CPD:
                return ! auth()->user()->can(PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_DETAILS_ADD);
            default:
                return false;
        }
    }

    public function getAdditionalOptionsForCar($sendUpdateLog): array
    {
        $data = [];
        switch ($sendUpdateLog->category->code) {
            case SendUpdateLogStatusEnum::EF:
                switch ($sendUpdateLog->option->code) {
                    case SendUpdateLogStatusEnum::CAR_AOC:
                        $data = $this->getCarAddons($sendUpdateLog->quote_uuid);
                        break;
                    case SendUpdateLogStatusEnum::COE:
                        $data = Emirate::where('is_active', true)->get()->toArray();
                        break;
                }
                break;
            case SendUpdateLogStatusEnum::EN:
                if ($sendUpdateLog->option->code == SendUpdateLogStatusEnum::COE_NFI) {
                    $data = Emirate::where('is_active', true)->get()->toArray();
                }
                break;
        }

        return $data;
    }

    public function getCarAddons($quoteUuid): array
    {
        $carQuote = CarQuote::where('uuid', $quoteUuid)->first();

        return $carQuote->plan->carAddons->toArray();
    }
}
