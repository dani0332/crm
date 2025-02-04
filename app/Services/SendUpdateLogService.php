<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\PaymentFrequency;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Models\BikeQuote;
use App\Models\BrokerInvoiceNumber;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteType;
use App\Models\CarAddOn;
use App\Models\CarAddOnOption;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestAddOn;
use App\Models\CycleQuote;
use App\Models\Emirate;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
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
            'policy_expiry_date',
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

        $countChildRecords = $quoteModel::where('parent_duplicate_quote_id', $quoteObject->code)->count();
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
                'renewal_batch' => $quoteObject->renewal_batch ?? null,
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
        $childRecords = $quoteModel::where('parent_duplicate_quote_id', $quote->code)->get();

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
        $category = $sendUpdateLog->category->code;

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            return true;
        }

        if ($category == SendUpdateLogStatusEnum::EF) {
            $option = $sendUpdateLog?->option?->code;
            if (in_array($option, [
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
                SendUpdateLogStatusEnum::DTSI,
                SendUpdateLogStatusEnum::DOV,
                SendUpdateLogStatusEnum::ATCRNB,
                SendUpdateLogStatusEnum::ATCRNB_RBB,
                SendUpdateLogStatusEnum::ATCRN_CRNRBB,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function getEndorsementProviderDetails($sendUpdateLog): array
    {
        $planId = null;
        $quoteType = QuoteTypes::getName($sendUpdateLog->quote_type_id)->value;
        $getQuoteDetails = $this->getQuoteObjectBy($quoteType, $sendUpdateLog->quote_uuid, 'uuid');
        $payments = $this->getPayments($getQuoteDetails->id, $getQuoteDetails->uuid, QuoteTypes::getName($sendUpdateLog->quote_type_id)->value);

        if ($sendUpdateLog?->category->code == SendUpdateLogStatusEnum::CPD || $payments->isEmpty()) {
            $insuranceProviderId = $sendUpdateLog->insurance_provider_id;
        } else {
            if ($getQuoteDetails->insly_id || $getQuoteDetails->insly_migrated) {
                if (empty($sendUpdateLog->insurance_provider_id)) {
                    if (in_array($quoteType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                        $getQuoteDetails->load('plan.insuranceProvider');
                        $insuranceProviderId = $getQuoteDetails?->plan?->insuranceProvider?->id;
                    } else {
                        $insuranceProviderId = $getQuoteDetails?->insurance_provider_id;
                    }
                } else {
                    $insuranceProviderId = $sendUpdateLog->insurance_provider_id;
                }
            } elseif (! $payments->isEmpty()) {
                $insuranceProviderId = $payments[0]->insurance_provider_id ?? null;
                $planId = $payments[0]->plan_id ?? null;
            } else {
                @[$insuranceProviderId, $planId] = $this->getProviderDetails($getQuoteDetails, $sendUpdateLog->quote_type_id);
            }
        }

        return [$insuranceProviderId, $planId];
    }

    public function getInvoiceDescription($sendUpdateLog, $quote, $quoteType): array
    {
        [$insuranceProviderId, $planId] = $this->getEndorsementProviderDetails($sendUpdateLog);
        $sendUpdateLogCategory = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');
        $insuranceProvider = InsuranceProviderRepository::find($insuranceProviderId);
        $isNonSelfBillingEnabled = false;
        if ($insuranceProvider) {
            $isNonSelfBillingEnabled = $insuranceProvider?->non_self_billing;
        }

        if (empty($sendUpdateLog->invoice_description) && $insuranceProvider) {
            if ($quoteType == quoteTypeCode::Business && $quote->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $invoiceDescription = $insuranceProvider->code.'-'.quoteTypeCode::GroupMedical.'-'.$quote->policy_number;
            } else {
                $invoiceDescription = $insuranceProvider->code.'-'.$quoteType.'-'.$quote->policy_number;
            }

            if ($sendUpdateLogCategory == SendUpdateLogStatusEnum::EF) {
                $invoiceDescription = ($sendUpdateLog->option->code == SendUpdateLogStatusEnum::ATICB) ? 'A.'.$invoiceDescription : 'E.'.$invoiceDescription;
            } elseif (in_array($sendUpdateLogCategory, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
                $invoiceDescription = 'CI.'.$invoiceDescription;
            } elseif ($sendUpdateLogCategory == SendUpdateLogStatusEnum::CPD) {
                $reversalInvoiceDescription = 'R.'.$invoiceDescription;
                $invoiceDescription = 'C.'.$invoiceDescription;
            }
        } else {
            $invoiceDescription = $sendUpdateLog->invoice_description;
        }

        $response = [
            'booking_date' => ! is_null($sendUpdateLog->booking_date) ? Carbon::parse($sendUpdateLog->booking_date)->format(config('constants.DATE_DISPLAY_FORMAT')) : null,
            'invoice_description' => $invoiceDescription ?? '',
            'reversal_invoice_description' => $reversalInvoiceDescription ?? '',
            'is_non_self_billing_enabled' => $isNonSelfBillingEnabled,
        ];

        $payment = Payment::where('send_update_log_id', $sendUpdateLog->id)->first();

        if ($payment) {
            $response['isLackingOfPayment'] = $this->isLackingPayment($payment);
        }

        return $response;
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
                $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider', 'sendUpdateLog', 'paymentable']);
            }
        }

        return $payments;
    }

    public function getReversalEntries($data): object
    {
        $payments = $this->getPayments($data['quoteId'], $data['quoteUuid'], $data['quoteType']);

        $sendUpdateLog = SendUpdateLogRepository::getLogByTaxInvoiceNumber($data);

        return (object) [
            'send_update_log' => $sendUpdateLog,
            'payment' => collect($payments)->where('insurer_tax_number', $data['taxInvoiceNo'])->first() ?? [],
        ];
    }

    public function getUploadedDocuments($sendUpdateLog): array
    {
        return $sendUpdateLog->documents()->pluck('document_type_code')->toArray();
    }

    public function getUpdateButtonStatus($sendUpdateLog): string
    {
        $category = $sendUpdateLog->category->code;
        $option = $sendUpdateLog?->option?->code;
        $uploadedDocuments = $this->getUploadedDocuments($sendUpdateLog);

        $isPolicyCertOrScheduleUploaded = in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $uploadedDocuments) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $uploadedDocuments);
        $requiredDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];

        if (in_array($sendUpdateLog->option?->code, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATICB])) {
            return SendUpdateLogStatusEnum::SU; // Book Update
        }

        // check if required documents not uploaded then show Send Update to Customer.
        $requiredDocumentsCheck = count(array_diff($requiredDocuments, $uploadedDocuments));

        if ($category == SendUpdateLogStatusEnum::CPD ||
            ($category == SendUpdateLogStatusEnum::EF && in_array($option, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB])) ||
            in_array($sendUpdateLog->status, [SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER, SendUpdateLogStatusEnum::UPDATE_BOOKED])
        ) {
            return SendUpdateLogStatusEnum::SU; // Book Update
        }

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::EF]) &&
            (($requiredDocumentsCheck == 0) && ($sendUpdateLog->is_booking_filled)) &&
            ! in_array($option, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB, SendUpdateLogStatusEnum::ATCRNB_RBB, SendUpdateLogStatusEnum::ATCRN_CRNRBB])
        ) {
            return SendUpdateLogStatusEnum::SNBU;
        }

        if (($category == SendUpdateLogStatusEnum::EF && $sendUpdateLog->status == SendUpdateLogStatusEnum::TRANSACTION_APPROVED) ||
            ($isPolicyCertOrScheduleUploaded && ! $sendUpdateLog->is_booking_filled)
        ) {
            return SendUpdateLogStatusEnum::SUC;
        }

        if (in_array($option, [
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
            SendUpdateLogStatusEnum::ATCRN_CRNRBB,
        ]) && $sendUpdateLog->is_booking_filled) {
            if ($option == SendUpdateLogStatusEnum::ATCRN_CRNRBB && $requiredDocumentsCheck == 0) {
                return SendUpdateLogStatusEnum::SU;
            }

            if ($option == SendUpdateLogStatusEnum::ATCRNB && in_array(DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, $uploadedDocuments)) {
                return SendUpdateLogStatusEnum::SU;
            }

            if ($option == SendUpdateLogStatusEnum::ATCRNB_RBB && in_array(DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER, $uploadedDocuments)) {
                return SendUpdateLogStatusEnum::SU;
            }
        }

        return '';
    }

    public function getSendToCustomerValidation($data): string
    {
        $sendUpdate = SendUpdateLogRepository::getLogByid($data['sendUpdateId']);

        $sendUpdateToCustomerValidation = in_array($sendUpdate->category->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]);

        if ($sendUpdateToCustomerValidation && $data['action'] == SendUpdateLogStatusEnum::ACTION_SUC) {
            return 'Please note your current action will only send the update to the customer.';
        }

        return '';
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
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
            SendUpdateLogStatusEnum::DTSI,
            SendUpdateLogStatusEnum::DOV,
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
            SendUpdateLogStatusEnum::ATCRN_CRNRBB,
        ];

        return in_array($categoryCode, $categories) && ! in_array($optionCode, $options);
    }

    public function isPolicyDetailsVisible($categoryCode, $optionCode): bool
    {
        return $categoryCode == SendUpdateLogStatusEnum::CPD ||
               ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::PPE);
    }

    public function getSendUpdatePayments($sendUpdateLog, $quoteType)
    {
        $payments = $sendUpdateLog->payments;
        if ($payments) {
            $payments->load(['paymentSplits', 'paymentStatus', 'paymentMethod', 'insuranceProvider', 'paymentStatusLog', 'paymentSplits.paymentStatus', 'paymentSplits.documents', 'paymentSplits.paymentMethod', 'paymentSplits.verifiedByUser', 'paymentSplits.processJob']);
            if ($quoteType == quoteTypeCode::Travel) {
                $payments->load(['travelPlan']);
            }
        }

        return $payments;
    }

    public function updatePaymentDetails($payment, $sendUpdateLog, $ignoreDiscount = false, $insurerDetails = null)
    {
        try {
            if ($insurerDetails !== null) {
                $sendUpdatePaymentDetails = [
                    'invoice_description' => $insurerDetails['invoice_description'],
                    'broker_invoice_number' => $insurerDetails['broker_invoice_number'] ?? $sendUpdateLog->broker_invoice_number ?? null,
                ];
            } else {
                $sendUpdatePaymentDetails = [
                    'policy_expiry_date' => $sendUpdateLog->expiry_date,
                    'insurer_tax_number' => $sendUpdateLog->insurer_tax_invoice_number,
                    'insurer_commmission_invoice_number' => $sendUpdateLog->insurer_commission_invoice_number,
                    'commmission_percentage' => $sendUpdateLog->commission_percentage,
                    'commission_vat_not_applicable' => $sendUpdateLog->commission_vat_not_applicable,
                    'commission_vat_applicable' => $sendUpdateLog->commission_vat_applicable,
                    'commission' => $sendUpdateLog->total_commission,
                    'insurer_invoice_date' => $sendUpdateLog->invoice_date,
                    'commission_vat' => $sendUpdateLog->vat_on_commission,
                ];
            }

            if (! $ignoreDiscount) {
                $sendUpdatePaymentDetails['discount_value'] = $sendUpdateLog->discount;
            }

            $payment->update($sendUpdatePaymentDetails);

            info('Book Update - Payment Details Updated - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

        } catch (\Exception $exception) {
            logger()->error('Book Update - Error while updating details in Payment - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid.' - Exception: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    public function checkInslyMigratedLead($request, $quoteDetails)
    {
        $inslyMigrated = false;
        if ($request->inslyMigrated) {
            $inslyMigrated = true;
        } else {
            $inslyMigrated = ! empty($getQuoteDetails->insly_id) && $quoteDetails->insly_id != null;
        }

        return $inslyMigrated;
    }

    public function preparedDetailsForEndorsement($sendUpdateRequest, $quote, $sendUpdateLog): array
    {
        $paymentBeforeUpdate = Payment::where('send_update_log_id', $sendUpdateRequest->sendUpdateId)->first();

        if (empty($sendUpdateLog->invoice_description)) {
            $this->updateInsurerDetails($sendUpdateRequest, $sendUpdateLog);
        }

        if ($paymentBeforeUpdate) {
            $this->updatePaymentDetails($paymentBeforeUpdate, $sendUpdateLog, true);
            app(SplitPaymentService::class)->updateCommissionSchedule($paymentBeforeUpdate);
        }

        $payment = Payment::with('paymentSplits')->where(['send_update_log_id' => $sendUpdateRequest->sendUpdateId])->first();
        if ($payment) {
            info('fn:preparedDetailsForEndorsement - Fetching Payment details from Send Update. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            return ['payment' => $payment, 'splitPayments' => $payment?->paymentSplits];
        } else {
            $checkInslyMigratedLead = $this->checkInslyMigratedLead($sendUpdateRequest, $quote);

            if ($checkInslyMigratedLead) {
                info('fn:preparedDetailsForEndorsement - Creating payment details based on the send update - The lead originated from Insly. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
                $payment = new Payment;
                $splitPayments = collect([new PaymentSplits]);

            } else {
                // If we don't have payment details then we fetched it from the Main Lead
                info('fn:preparedDetailsForEndorsement - Fetching Payment details from Main Lead. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
                $quote->load(['payments' => function ($query) {
                    $query->whereNull('send_update_log_id');
                }, 'payments.paymentSplits']);

                $payment = $quote->payments->first();
                $splitPayments = $payment->paymentSplits;
            }

            $payment->fill([
                'discount_value' => $sendUpdateLog->discount,
                'invoice_description' => $sendUpdateLog->invoice_description,
                'insurer_invoice_date' => $sendUpdateLog->invoice_date,
                'commission_vat' => abs($sendUpdateLog->vat_on_commission),
                'total_price' => abs($sendUpdateLog->price_with_vat), // Reminder: AP CREDIT NOTE issue fix, Change price_with_vat instead of price_without_vat
                'total_amount' => abs($sendUpdateLog->price_vat_applicable),
                'commission' => abs($sendUpdateLog->total_commission),
                'commission_vat_applicable' => abs($sendUpdateLog->commission_vat_applicable),
                'commission_vat_not_applicable' => abs($sendUpdateLog->commission_vat_not_applicable),
                'commmission_percentage' => abs($sendUpdateLog->commission_percentage),
                'insurer_tax_number' => $sendUpdateLog->insurer_tax_invoice_number,
                'insurer_commmission_invoice_number' => $sendUpdateLog->insurer_commission_invoice_number,
                'policy_expiry_date' => $sendUpdateLog->expiry_date,
                'broker_invoice_number' => $sendUpdateLog->broker_invoice_number,
                'frequency' => PaymentFrequency::UPFRONT,
                'insurance_provider_id' => ($checkInslyMigratedLead) ? $sendUpdateLog?->insurance_provider_id : $payment->insurance_provider_id,
            ]);

            $splitPayments->first()->fill([
                'due_date' => $sendUpdateLog->invoice_date,
                'payment_amount' => abs($sendUpdateLog->price_vat_applicable),
                'collection_amount' => abs($sendUpdateLog->price_vat_applicable),
                'commission_vat_applicable' => ($payment->commission - $payment->commission_vat),
                'commission_vat' => $payment->commission_vat,
            ]);

            $mainLeadDetails = [
                'payment' => [
                    'insurer_tax_number' => $payment->insurer_tax_number,
                    'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                ],
            ];

            $response = ['payment' => $payment, 'splitPayments' => $splitPayments, 'mainLeadDetails' => $mainLeadDetails];

            // Reminder: price_vat_applicable > 0 => Debit Note, if negative then should be Credit Note
            if ($checkInslyMigratedLead && ($sendUpdateLog->price_vat_applicable > 0)) {
                unset($response['mainLeadDetails']);
            }

            $response['quotePolicyDetails'] = [
                'policy_booking_date' => $sendUpdateLog->booking_date,
                'renewal_expiry_date' => $sendUpdateLog->expiry_date,
                'policy_number' => $sendUpdateLog->policy_number,
                'transaction_type_id' => $quote->transaction_type_id,
                'advisor_id' => $sendUpdateLog->advisor_id,
                'price_vat_applicable' => abs($response['payment']->total_price),
                'price_with_vat' => abs($sendUpdateLog->price_with_vat),
            ];

            return $response;
        }
    }

    public function preparedDataForEndorsement($sendUpdateRequest)
    {
        $sendUpdateLog = SendUpdateLog::with('category', 'sageApiLogs')->find($sendUpdateRequest->sendUpdateId);
        info('fn:preparedDataForEndorsement - Preparing Data for Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

        $skipCategories = [SendUpdateLogStatusEnum::EN];
        if (in_array($sendUpdateLog?->category?->code, $skipCategories)) {
            info('fn:preparedDataForEndorsement - Skipping Sage APIs for Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            return ['status' => true, 'skipSageCalls' => true, 'message' => 'Skipping Sage APIs for Non Financial Endorsement'];
        }

        $quoteModelObject = $this->getModelObject($sendUpdateRequest->quoteType);
        $quoteDetails = $quoteModelObject::where('id', $sendUpdateRequest->quoteRefId)->first();
        $preparedDetailsForEndorsement = $this->preparedDetailsForEndorsement($sendUpdateRequest, $quoteDetails, $sendUpdateLog);

        $paymentInsurerInvoiceNumber = ($preparedDetailsForEndorsement['payment']->insurer_tax_number ?? $preparedDetailsForEndorsement['payment']->insurer_commmission_invoice_number) ?? null;
        if (empty($paymentInsurerInvoiceNumber)) {
            info('fn:preparedDataForEndorsement - Payment not successfully updated - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            return ['status' => false, 'message' => 'Payment not successfully updated'];
        }

        $sendUpdateLogDetails = [
            'personal_quote_id' => $sendUpdateLog->personal_quote_id,
            'policy_booking_date' => $sendUpdateLog->booking_date,
            'policy_expiry_date' => $sendUpdateLog->expiry_date,
            'policy_number' => $sendUpdateLog->policy_number,
            'transaction_type_id' => $quoteDetails->transaction_type_id,
            'advisor_id' => $quoteDetails?->advisor_id ?? null,
            'price_vat_applicable' => abs($preparedDetailsForEndorsement['payment']->total_price),
            'price_with_vat' => abs($sendUpdateLog->price_with_vat),
            'insly_migrated' => $quoteDetails->insly_migrated,
            'insurance_provider_id' => $preparedDetailsForEndorsement['payment']->insurance_provider_id, // TODO:: Need to verify this field
            'booking_filled_by' => $sendUpdateLog->booking_filled_by,
        ];

        info('fn:preparedDataForEndorsement - Preparing Sage Payload for Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
        $sageRequestPayload = SagePayloadFactory::sagePayLoad($sendUpdateRequest->quoteType, $preparedDetailsForEndorsement['payment'], (object) $sendUpdateLogDetails, $preparedDetailsForEndorsement['splitPayments']);
        $sageRequestPayload->customerId = app(SageApiService::class)->verifySageCustomer(
            $quoteDetails->customer_id,
            ['quoteTypeId' => QuoteTypes::getIdFromValue($sendUpdateRequest->quoteType), 'id' => $quoteDetails->id],
            $quoteDetails,
            ($sendUpdateLog?->category?->code == SendUpdateLogStatusEnum::CPD ? 21 : 13)
        );

        $checkRequiredSageValidations = app(SageApiService::class)->checkRequiredSageIds($sageRequestPayload);
        if (! $checkRequiredSageValidations['status']) {
            info('fn:preparedDataForEndorsement - Sage Validation Failed - '.$checkRequiredSageValidations['message'].' - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            return $checkRequiredSageValidations;
        }

        return [
            'status' => true,
            'message' => 'The send update booking process has started. It will take some time to complete. Please check back later to see the status',
            'sageRequestPayload' => $sageRequestPayload,
        ];
    }

    public function updateSageProcessForDispatching($request, $quote, $sageRequestPayload)
    {
        $response = false;
        $sageProcessData = [
            'user_id' => $sageRequestPayload->userId,
            'insurance_provider_id' => $sageRequestPayload->insurerID,
            'request' => json_encode([
                'sagePayload' => $sageRequestPayload,
                'requestPayload' => $request,
            ]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ];

        if ($quote::class == SendUpdateLog::class) {
            $sageProcessDataRequest = json_decode($sageProcessData['request'], true);
            $sageRequestPayload->sageProcessRequestType = SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST;
            $sageProcessDataRequest['sagePayload'] = $sageRequestPayload;
            unset($request['dispatchSageCall']);

            $sageProcessDataRequest['requestPayload'] = $request;
            $sageProcessData['request'] = json_encode($sageProcessDataRequest);
        }

        $sageProcess = SageProcess::where([
            'model_type' => $quote::class,
            'model_id' => $quote->id,
        ])->first();

        if ($sageProcess) {
            if ($sageProcess->status == SageEnum::SAGE_PROCESS_FAILED_STATUS) {
                $response = $sageProcess->update($sageProcessData);
                info('fn:updateSageProcessForDispatching - Sage process schedule updated Successfully');
            }
        } else {
            $sageProcessData['model_type'] = $quote::class;
            $sageProcessData['model_id'] = $quote->id;
            $response = $sageScheduleResponse = SageProcess::create($sageProcessData);
            $quote->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED]);
            info('fn:updateSageProcessForDispatching - Sage process scheduled Successfully');
        }

        return $response;
    }

    public function updatesMoveToLead($preparedData)
    {
        [$request, $sendUpdateLog, $preparedData] = $preparedData;
        $categoryCode = $sendUpdateLog?->category?->code;
        $optionCode = $sendUpdateLog?->option?->code;
        $quoteModel = $this->getModelObject($request->quoteType);
        $quote = $quoteModel::where('id', $request->quoteRefId)->with(['payments' => function ($query) {
            $query->whereNull('send_update_log_id');
        }])->first();
        $currentDate = now();

        try {
            DB::beginTransaction();
            info('Book Update - Moving Send Update impact to Main Lead. QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

            $payment = Payment::where('send_update_log_id', $sendUpdateLog->id)->first();
            if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::CPD])) {
                if ($payment) {
                    info('Book Update - Updating Payment Details for Main Lead - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $payment->update([
                        'paymentable_id' => $quote->id,
                        'paymentable_type' => ltrim($quoteModel, '\\'),
                    ]);
                }

                // Cases for Endorsment Financial Start
                if ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::PPE) {
                    info('Book Update - Updating Policy Expiry Date for Main Lead - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $quote->update(['policy_expiry_date' => $sendUpdateLog->expiry_date]);
                }

                if ($request->quoteType == quoteTypeCode::Car && $categoryCode == SendUpdateLogStatusEnum::EF) {
                    // Addons for Car move to main lead
                    if (! empty($sendUpdateLog->car_addons) && $optionCode == SendUpdateLogStatusEnum::AOCOV) {
                        foreach ($sendUpdateLog->car_addons as $addonId) {
                            $plansAddons = CarAddOnOption::where('addon_id', $addonId)->get();
                            foreach ($plansAddons as $planAddon) {
                                CarQuoteRequestAddOn::updateOrCreate([
                                    'quote_request_id' => $quote->id,
                                    'addon_option_id' => $planAddon->id,
                                ], [
                                    'quote_request_id' => $quote->id,
                                    'addon_option_id' => $planAddon->id,
                                    'price' => 0,
                                ]);
                            }
                        }
                    }
                    // Emirate of Registration for Car move to main lead
                    elseif (! empty($sendUpdateLog->emirates_id) && $optionCode == SendUpdateLogStatusEnum::COE) {
                        $quote->update(['emirate_of_registration_id' => $sendUpdateLog->emirates_id]);
                        info('emirate id : '.$sendUpdateLog->emirates_id);
                    }
                    // Seat Capacity for Car move to main lead
                    elseif (! empty($sendUpdateLog->seating_capacity) && $sendUpdateLog->seating_capacity != 0 && $optionCode == SendUpdateLogStatusEnum::CISC) {
                        $quote->update(['seat_capacity' => $sendUpdateLog->seating_capacity]);
                    }
                }
                // Cases for Endorsement Financial End

                // Cases for Cancel Inception and Cancel Inception Reissue Start
                if ($categoryCode === SendUpdateLogStatusEnum::CIR) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyCancelledReissued,
                        // 'quote_status_id' => QuoteStatusEnum::PolicyCancelled, // Below code overrides status, it should be PolicyCancelledReissued not PolicyCancelled
                        'quote_batch_id' => null,
                    ]);
                    (new AllocationService)->deductLeadAllocationCount($quoteModel, $quote->uuid);
                } elseif ($categoryCode == SendUpdateLogStatusEnum::CI || ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::MPC)) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
                    ]);
                }
                // Cases for Cancel Inception and Cancel Inception Reissue End

                // Cases for Correct Policy Details Start
                if ($categoryCode == SendUpdateLogStatusEnum::CPD && ($request->reversalInvoice == $quote->payments->value('insurer_tax_number')
                )) {
                    info('Book Update - Updating Policy and Booking Details for Main Lead - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $quote->update([
                        'policy_number' => $sendUpdateLog->policy_number,
                        'policy_start_date' => $sendUpdateLog->start_date,
                        'policy_expiry_date' => $sendUpdateLog->expiry_date,
                        // 'policy_booking_date' => $currentDate, // Booking Date should not be updated Task:86eqajbyv
                    ]);
                }
                // Cases for Correct Policy Details End
            }

            if ($payment) {
                if ($payment->captured_amount < 1) {
                    $status = SendUpdateLogStatusEnum::UNPAID;
                } elseif (($payment->captured_amount + $payment->discount_value) < $payment->total_price) {
                    $status = SendUpdateLogStatusEnum::PARTIALLY_PAID;
                } elseif (($payment->captured_amount + $payment->discount_value) >= $payment->total_price) {
                    $status = SendUpdateLogStatusEnum::FULL_PAID;
                }
            }

            $sendUpdateLog->update([
                'booking_date' => $currentDate,
                'transaction_payment_status' => $status ?? '',
                'status' => SendUpdateLogStatusEnum::UPDATE_BOOKED,
            ]);

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            info('Book Update - Error while moving updates to main lead - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid.' - Exception: '.$exception->getMessage());

            return ['status' => false, 'message' => 'Update not booked'];
        }

        info('Book Update - Send Update impact successfully moved - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$quote->uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

        return ['status' => true, 'message' => SendUpdateLogStatusEnum::UPDATE_BOOKED];
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
                    case SendUpdateLogStatusEnum::AOCOV:
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

    public function getCarAddons($quoteUuid, $addonsIds = null): array
    {
        if (! empty($addonsIds)) {
            return CarAddOn::whereIn('id', $addonsIds)->pluck('text')->toArray();
        }

        $carQuote = CarQuote::where('uuid', $quoteUuid)->first();

        return (isset($carQuote->plan->carAddons)) ? $carQuote?->plan?->carAddons->toArray() : [];
    }

    public function sendUpdateToCustomerEmailData($sendUpdateLog, $action): array
    {
        $quoteTypeId = $sendUpdateLog->quote_type_id;
        $quoteType = QuoteTypeId::getOptions()[$quoteTypeId];
        $quoteModel = $this->getModelObject($quoteType);
        $quote = $quoteModel::where('uuid', $sendUpdateLog->quote_uuid)->first();
        $insuranceProviderText = $sendUpdateLog?->insuranceProvider?->text ?? $quote?->insuranceProvider?->text ?? $quote?->plan?->insuranceProvider?->text ?? '';
        $optionCode = $sendUpdateLog->option?->code;
        $categoryCode = $sendUpdateLog->category->code;

        if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::EN]) && $optionCode != SendUpdateLogStatusEnum::MPC) {
            $update = $sendUpdateLog?->option->text;
        } elseif (in_array($categoryCode, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) || ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::MPC)) {
            $update = quoteStatusCode::POLICY_CANCELLED;
        }

        if (! in_array($quoteTypeId, [QuoteTypeId::Jetski, QuoteTypeId::Business])) {
            $documents = $sendUpdateLog->documents->whereIn('document_type_code', [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE])->toArray();
        } elseif ($quoteTypeId == QuoteTypeId::Business) {
            $documents = $sendUpdateLog->documents->whereIn('document_type_code', [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE])->toArray();
        }

        $emailData = (object) [
            'clientFirstName' => $quote->first_name,
            'clientFullName' => $quote->first_name.' '.$quote->last_name,
            'policyNumber' => $quote->policy_number ?? $quote?->previous_quote_policy_number ?? '',
            'carQuoteId' => $sendUpdateLog->code,
            'currentInsurer' => $insuranceProviderText,
            'policyUpdate' => $update ?? '',
            'customerEmail' => $quote->email,
            'advisor' => (object) [
                'landLine' => $quote->advisor->landline_no ?? '',
                'email' => $quote->advisor->email ?? '',
                'name' => $quote->advisor->name ?? '',
                'mobileNo' => $quote->advisor->mobile_no ?? '',
                'profilePicture' => $quote->advisor->profile_photo_path,
            ],
            'googleMeet' => $quote->advisor->calendar_link ?? '',
            'documents' => $documents,
        ];

        if ($quoteTypeId == QuoteTypeId::Business) {
            $emailData->lobType = BusinessQuoteType::where('id', $quote->business_type_of_insurance_id)->where('is_active', true)->first()->text;
            if ($quote->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $emailData->isGroupMedical = true;
                $templateId = getAppStorageValueByKey(ApplicationStorageEnums::GROUP_MEDICAL_SEND_POLICY_TEMPLATE);
            } elseif ($quote->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::tradeCredit)) {
                $templateId = getAppStorageValueByKey(ApplicationStorageEnums::CORPLINE_TRADE_SEND_POLICY_TEMPLATE);
            } else {
                $templateId = getAppStorageValueByKey(ApplicationStorageEnums::CORPLINE_CAR_SEND_POLICY_TEMPLATE);
            }
        } else {
            $templateCode = strtoupper(QuoteTypeId::getOptions()[$quoteTypeId]).'_SEND_POLICY_TEMPLATE';
            $constantName = 'App\Enums\ApplicationStorageEnums::'.$templateCode;
            $templateId = getAppStorageValueByKey(constant($constantName));
        }

        if ($quoteTypeId == QuoteTypeId::Car) {
            if ($optionCode == SendUpdateLogStatusEnum::AOCOV) {
                $emailData->policyNewExpiry = ! empty($sendUpdateLog->car_addons) ? implode(', ', $this->getCarAddons($sendUpdateLog->quote_uuid, $sendUpdateLog->car_addons)) : '';
            } elseif (in_array($optionCode, [SendUpdateLogStatusEnum::COE, SendUpdateLogStatusEnum::COE_NFI])) {
                $emailData->policyNewExpiry = $sendUpdateLog->emirates->text ?? '';
            } elseif (in_array($optionCode, [SendUpdateLogStatusEnum::CISC, SendUpdateLogStatusEnum::CISC_NFI])) {
                $emailData->policyNewExpiry = $sendUpdateLog->seating_capacity ?? '';
            } elseif ($optionCode == SendUpdateLogStatusEnum::PPE) {
                $emailData->policyNewExpiry = $sendUpdateLog->expiry_date ? 'New Expiry Date: '.Carbon::parse($sendUpdateLog->expiry_date)->format('d-M-Y') : '';
            }

            if (! empty($quote->plan->insuranceProvider->code) && ! in_array($categoryCode, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) && $optionCode != SendUpdateLogStatusEnum::MPC) {
                $roadsideAssistanceNumber = $quote?->insuranceProvider?->roadside_phone_number ?? $quote?->plan?->insuranceProvider?->roadside_phone_number ?? null;
                if (! is_null($roadsideAssistanceNumber) && $roadsideAssistanceNumber != 0) {
                    $emailData->roadsideAssistance = $roadsideAssistanceNumber;
                }
            }
        }

        return [$templateId, $emailData, 'send-update', $quoteTypeId];
    }

    /**
     * it will check for Indicative Additional Price section, if the option relation not available means it is Correction of Policy.
     */
    public function isPlanDetailAvailable($sendUpdateLog): bool
    {
        if (in_array($sendUpdateLog->category->code, [SendUpdateLogStatusEnum::EN, SendUpdateLogStatusEnum::CPU, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) ||
            in_array($sendUpdateLog->option?->code, [
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
                SendUpdateLogStatusEnum::DOV,
                SendUpdateLogStatusEnum::ACB,
                SendUpdateLogStatusEnum::ATIB,
                SendUpdateLogStatusEnum::DTSI,
                SendUpdateLogStatusEnum::ATCRNB,
                SendUpdateLogStatusEnum::ATCRNB_RBB,
                SendUpdateLogStatusEnum::ATCRN_CRNRBB,
            ])) {
            return false;
        }

        return true;
    }

    public function getSendUpdateDocuments($category, $option): array
    {
        $documentTypesByCategory = app(QuoteDocumentService::class)->getSendUpdateDocumentTypes();

        foreach ($documentTypesByCategory as $documentCategory => $documentTypes) {
            foreach ($documentTypes as $key => $documentType) {
                if (! in_array($category, [SendUpdateLogStatusEnum::EN, SendUpdateLogStatusEnum::CPD]) &&
                    in_array($documentType['code'], [
                        DocumentTypeCode::SEND_UPDATE_TAX_INVOICE,
                        DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER,
                    ])
                ) {
                    $isRequired = true;
                    if ($option == SendUpdateLogStatusEnum::ATCRNB && $documentType['code'] == DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER) {
                        $isRequired = false;
                    }
                    if ($option == SendUpdateLogStatusEnum::ATCRNB_RBB && $documentType['code'] == DocumentTypeCode::SEND_UPDATE_TAX_INVOICE) {
                        $isRequired = false;
                    }
                    $documentTypesByCategory[$documentCategory][$key]['is_required'] = (int) $isRequired;
                } elseif (
                    in_array($option, [
                        SendUpdateLogStatusEnum::ATICB,
                        SendUpdateLogStatusEnum::ATCRNB,
                        SendUpdateLogStatusEnum::ATCRNB_RBB,
                        SendUpdateLogStatusEnum::ATCRN_CRNRBB,
                    ]) &&
                    in_array($documentType['code'], [
                        DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                        DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                    ])
                ) {
                    $documentTypesByCategory[$documentCategory][$key]['is_required'] = (int) false;
                }
            }
        }

        return $documentTypesByCategory;
    }

    public function sendUpdatePriceAndDiscount($sendUpdateLog, $payment): void
    {
        app(CentralService::class)->synchronizePaymentInformation($sendUpdateLog, $payment);
    }

    public function updatePaymentTotalPrice($payment, $totalPrice): void
    {
        info('Master payment code: '.$payment->code.' - Total Price: '.$totalPrice.' Updated.');
        $payment->total_price = $totalPrice;
    }

    public function checkSendUpdatePermissions(): array
    {
        $permissionArray = [
            SendUpdateLogStatusEnum::EF => PermissionsEnum::SEND_UPDATE_ENDO_FIN_ADD,
            SendUpdateLogStatusEnum::EN => PermissionsEnum::SEND_UPDATE_ENDO_NON_FIN_ADD,
            SendUpdateLogStatusEnum::CI => PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD,
            SendUpdateLogStatusEnum::CIR => PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD,
            SendUpdateLogStatusEnum::CPU => PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_UPLOAD_ADD,
            SendUpdateLogStatusEnum::CPD => PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_DETAILS_ADD,
        ];

        $array = [];
        foreach ($permissionArray as $key => $permission) {
            if (! auth()->user()->can($permission)) {
                $array[] = $key;
            }
        }

        return $array;
    }

    public function generateBrokerInvoiceNumberForSU($sendUpdateLog, $insuranceProviderId = null, $updateReversalBIN = false): array
    {
        info('fn:generateBrokerInvoiceNumberForSU - SendUpdateLog - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
        $response = ['status' => false, 'message' => ''];
        $generateBrokerInvoice = true;

        if (! empty($sendUpdateLog->broker_invoice_number) && ! $updateReversalBIN) {
            info('SendUpdateLog - Broker Invoice Number already exists - BIN: '.$sendUpdateLog->broker_invoice_number.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
            $generateBrokerInvoice = false;
            $response['status'] = true;
        }

        if ($sendUpdateLog?->category?->code == SendUpdateLogStatusEnum::CPD && ! empty($sendUpdateLog->reversal_broker_invoice_number)) {
            info('SendUpdateLog - Reversal Broker Invoice Number already exists - BIN: '.$sendUpdateLog->notes.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
            $generateBrokerInvoice = false;
            $response['status'] = true;
        }

        if (! $insuranceProviderId) {
            [$insuranceProviderId, $planId] = $this->getEndorsementProviderDetails($sendUpdateLog);
        }

        $insuranceProvider = InsuranceProviderRepository::find($insuranceProviderId);
        if (! $insuranceProviderId) {
            $generateBrokerInvoice = false;
            $response['message'] = 'Insurance Provider not found for Send Update Log: '.$sendUpdateLog->uuid;
            $response['status'] = true;
        } elseif (! isNonSelfBillingEnabledForInsuranceProvider($insuranceProvider)) {
            info('InsuranceProvider - Non Self Billing Not Enabled - InsuranceProviderID: '.$insuranceProvider?->id.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
            $response['message'] = 'Non Self Billing Not Enabled for Insurance Provider: '.$insuranceProvider->text;
            $response['status'] = true;
            // return $response;
        }

        $maxAttempts = 25;
        $attempts = 0;
        $reversalLog = $updateReversalBIN ? 'Reversal ' : '';

        try {
            if ($generateBrokerInvoice) {
                $currentDate = Carbon::now();
                DB::transaction(function () use ($insuranceProvider, $updateReversalBIN, $currentDate, $reversalLog, $sendUpdateLog, &$response) {
                    $invoiceBrokerSequence = BrokerInvoiceNumber::where([
                        'insurance_provider_id' => $insuranceProvider->id,
                        'date' => $currentDate->format('Y-m'),
                    ])->lockForUpdate()->first();

                    if (! $invoiceBrokerSequence) {
                        $invoiceBrokerSequence = BrokerInvoiceNumber::create([
                            'insurance_provider_id' => $insuranceProvider->id,
                            'date' => $currentDate->format('Y-m'),
                            'sequence_number' => 1,
                        ]);
                    }

                    info('InsuranceProvider - Non Self Billing Enabled - InsuranceProviderID: '.$insuranceProvider->id.' - SequenceNumber: '.$invoiceBrokerSequence->sequence_number.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $brokerInvoiceNumber = 'AFIA/'.$insuranceProvider?->code.'/'.$currentDate->format('Y').'/'.$currentDate->format('m').'/'.$invoiceBrokerSequence->sequence_number;
                    info('InsuranceProvider - '.$reversalLog.'Broker Invoice Number Generated - BIN: '.$brokerInvoiceNumber.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

                    $fieldForUpdate = $updateReversalBIN ? 'reversal_broker_invoice_number' : 'broker_invoice_number';

                    SendUpdateLog::withoutEvents(function () use ($sendUpdateLog, $fieldForUpdate, $brokerInvoiceNumber) {
                        $sendUpdateLog->update([
                            $fieldForUpdate => $brokerInvoiceNumber,
                        ]);
                    });

                    $invoiceBrokerSequence->increment('sequence_number');
                    info('InsuranceProvider - '.$reversalLog.'Broker Invoice Number Updated - BIN: '.$brokerInvoiceNumber.' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

                    $response['status'] = true;
                    $response['message'] = $reversalLog.'Broker Invoice Number Generated Successfully';
                });

                if ($sendUpdateLog?->category?->code == SendUpdateLogStatusEnum::CPD && ! $updateReversalBIN) {
                    info('InsuranceProvider - Generating Reversal Broker Invoice Number - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    // Reminder:: commit this transaction because need to get incremented sequence number for reversal BIN with same insurer
                    DB::afterCommit(function () use ($sendUpdateLog, $insuranceProvider) {
                        $this->generateBrokerInvoiceNumberForSU($sendUpdateLog, $insuranceProvider->id, true);
                    });
                }
            }

            return $response;

        } catch (Exception $exception) {
            $attempts++;
            $dbErrorCodes = [1213, 40001];
            //            Error 1213: Deadlock found when trying to get lock; try restarting transaction
            //            Error 40001: Serialization failure: Deadlock found when trying to get lock; try restarting transaction

            if (in_array($exception->getCode(), $dbErrorCodes)) {
                if ($attempts < $maxAttempts) {
                    $this->generateBrokerInvoiceNumberForSU($sendUpdateLog, $insuranceProviderId);
                } else {
                    info('InsuranceProvider - '.$reversalLog.'Broker Invoice Number Generation Failed - Max Attempts Reached - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

                    return ['status' => false, 'message' => $reversalLog.'Broker Invoice Number Generation Failed - Max Attempts Reached'];
                }
            } else {
                info('InsuranceProvider - '.$reversalLog.'Broker Invoice Number Generation Failed - Exception: '.$exception->getMessage().' - QuoteUUID: '.$sendUpdateLog->quote_uuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

                return ['status' => false, 'message' => $reversalLog.'Broker Invoice Number Generation Failed'];
            }
        }
    }

    public function getProviderDetails($quote, $quoteTypeId, $forSendUpdateCreation = false): array
    {
        $insuranceProviderId = $plan_id = null;
        $isCommercial = false;
        if ($quoteTypeId == QuoteTypeId::Car) {
            $isCommercial = app(LeadAllocationService::class)->isCommercialVehicles($quote);
        }
        if ($forSendUpdateCreation && ($quote->insly_id || $quote->insly_migrated)) {

            return [$quote?->insurance_provider_id, $plan_id];
        }
        if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health]) && ! $isCommercial) {
            $quoteType = QuoteTypes::getName($quoteTypeId)->value;
            $quoteServiceFile = getServiceObject($quoteType);
            $quoteModel = app($quoteServiceFile)->getEntityPlain($quote->id)->load(['payments', 'plan']);
            $payment = $quoteModel->payments()->mainLeadPayment()->first();

            $planRelationName = strtolower($quoteType).'Plan';
            if ($payment) {
                $payment->load($planRelationName);
            }
            $insuranceProvider = $payment->{$planRelationName}?->insuranceProvider;
            $insuranceProviderId = $insuranceProvider->id ?? null;
            $plan_id = $quoteModel->plan?->id ?? null;
        } else {
            $insuranceProviderId = $quote->insurance_provider_id ?? null;
        }

        return [$insuranceProviderId, $plan_id];
    }

    public function updateInsurerDetails($request, $sendUpdate)
    {
        $quote = app(getServiceObject($request->quoteType))->getEntityPlain($request->quoteRefId);

        if ($sendUpdate->category->code == SendUpdateLogStatusEnum::CPD ||
            ($sendUpdate->category->code == SendUpdateLogStatusEnum::EF && $sendUpdate->option?->code == SendUpdateLogStatusEnum::PPE)) {
            $insuranceProviderId = $sendUpdate->insurance_provider_id;
            $planId = $sendUpdate->plan_id ?? null;
        } else {
            @[$insuranceProviderId, $planId] = $this->getProviderDetails($quote, QuoteTypes::getIdFromValue($request->quoteType));
        }

        $bookingDetails = $this->getInvoiceDescription($sendUpdate, $quote, $request->quoteType);

        $bookingDetails = array_merge($bookingDetails, [
            'insurance_provider_id' => $insuranceProviderId,
            'plan_id' => $planId,
        ]);

        return SendUpdateLogRepository::updateInsurerDetails($sendUpdate, $bookingDetails);
    }

    public function isPolicyDetailsFilled($policyDetails, $quoteTypeId, $insuranceProviderId, $planId, $category, $quote): bool
    {
        // For CPD, all fields are mandatory, and for EF PPE, only expiry date is mandatory.
        if ($category == SendUpdateLogStatusEnum::EF) {
            return ! is_null($policyDetails['expiry_date']);
        } else {
            // if legacy lead and planId is not available, because plandId is optional.
            if (! ($quote->insly_id || $quote->insly_migrated) && ! is_null($planId)) {
                if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health])) {
                    $policyDetails['plan_id'] = $planId;
                }
            }
            $policyDetails['insurance_provider_id'] = $insuranceProviderId;

            $filledValues = array_filter($policyDetails, function ($value) {
                return ! is_null($value) && $value !== '';
            });

            if (count($policyDetails) === count($filledValues)) {
                return true;
            }
        }

        return false;
    }

    public function isEditDisabledForQueuedBooking($sendUpdateLog): bool
    {
        $sendUpdateLogStatus = $sendUpdateLog->status;
        if ($sendUpdateLogStatus == SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED) {
            return true;
        }

        if ($sendUpdateLogStatus == SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED) {
            if (auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * This method is used to check if the COMMISSION (VAT NOT APPLICABLE) is enabled or not.
     *
     * @param  $quoteType  - Life, Business etc.
     * @param  $businessTypeOfInsuranceId  - Business type of insurance id, if quote type is Business.
     */
    public function commissionVatNotApplicableEnabled($quoteType, $businessTypeOfInsuranceId): bool
    {
        return app(CentralService::class)->commissionVatNotApplicableEnabled($quoteType, $businessTypeOfInsuranceId);
    }

    public function sendUpdateStatuses(): array
    {
        return [
            SendUpdateLogStatusEnum::NEW_REQUEST,
            SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS,
            SendUpdateLogStatusEnum::TRANSACTION_DECLINE,
            SendUpdateLogStatusEnum::TRANSACTION_APPROVED,
            SendUpdateLogStatusEnum::UPDATE_ISSUED,
            SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED,
            SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED,
            SendUpdateLogStatusEnum::UPDATE_BOOKED,

        ];
    }

    /*
     * This method is used to check if the main button should be disabled or not, for Tap Payment integration.
     * @param $sendUpdateLog - Send Update Log
     * @return string
     */
    public function disableMainBtn($sendUpdateLog): string
    {
        if (in_array($sendUpdateLog->category?->code, [
            SendUpdateLogStatusEnum::EF,
            SendUpdateLogStatusEnum::CI,
            SendUpdateLogStatusEnum::CIR,
        ]) && empty($sendUpdateLog->endorsement_number) && auth()->user()->can(PermissionsEnum::TAP_BETA_ACCESS)) {
            return 'Endorsement Number is required before proceeding.';
        }

        return '';
    }
}
