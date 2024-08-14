<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BikeQuote;
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
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
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
            ])) {
                return true;
            }
        }

        return false;
    }

    public function getInvoiceDescription($sendUpdateLog, $quote, $quoteType, $payments): array
    {
        if ($sendUpdateLog->category->code == SendUpdateLogStatusEnum::CPD || $payments->isEmpty()) {
            $insuranceProviderId = $sendUpdateLog->insurance_provider_id;
        } else {
            $insuranceProviderId = $payments->first()->insurance_provider_id;
        }
        $sendUpdateLogCategory = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        $insuranceProviderCode = InsuranceProviderRepository::where('id', $insuranceProviderId)->value('code');
        if (empty($sendUpdateLog->broker_invoice_number)) {
            $insuranceProviderLeadCount = Payment::where('insurance_provider_id', $insuranceProviderId)->count();

            $brokerInvoiceNumber = $insuranceProviderCode.'.'.(++$insuranceProviderLeadCount);
            if (SendUpdateLog::where('broker_invoice_number', $brokerInvoiceNumber)->whereNot('uuid', $sendUpdateLog->uuid)->exists()) {
                $brokerInvoiceNumber = $insuranceProviderCode.'.'.(++$insuranceProviderLeadCount);
            }
        } else {
            $brokerInvoiceNumber = $sendUpdateLog->broker_invoice_number;
        }

        if (empty($sendUpdateLog->invoice_description)) {
            if ($quoteType == quoteTypeCode::Business && $quote->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $invoiceDescription = $insuranceProviderCode.'-'.quoteTypeCode::GroupMedical.'-'.$quote->policy_number;
            } else {
                $invoiceDescription = $insuranceProviderCode.'-'.$quoteType.'-'.$quote->policy_number;
            }

            if ($sendUpdateLogCategory == SendUpdateLogStatusEnum::EF) {
                $invoiceDescription = 'E.'.$invoiceDescription;
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
            'broker_invoice_number' => $brokerInvoiceNumber,
            'invoice_description' => $invoiceDescription,
            'reversal_invoice_description' => $reversalInvoiceDescription ?? '',
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

        if (in_array($sendUpdateLog->option?->code, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB])) {

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
            ! in_array($option, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATIB])
        ) {
            return SendUpdateLogStatusEnum::SNBU;
        }

        if (($category == SendUpdateLogStatusEnum::EF && $sendUpdateLog->status == SendUpdateLogStatusEnum::TRANSACTION_APPROVED) ||
            ($isPolicyCertOrScheduleUploaded && ! $sendUpdateLog->is_booking_filled)
        ) {
            return SendUpdateLogStatusEnum::SUC;
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
            $payments->load(['paymentSplits', 'paymentStatus', 'paymentMethod', 'insuranceProvider', 'paymentStatusLog', 'paymentSplits.paymentStatus', 'paymentSplits.documents', 'paymentSplits.paymentMethod', 'paymentSplits.verifiedByUser']);
            if ($quoteType == quoteTypeCode::Travel) {
                $payments->load(['travelPlan']);
            }
        }

        return $payments;
    }

    public function updatePaymentDetails($payment, $sendUpdateLog, $ignoreDiscount = false)
    {
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
            'commission_vat' => $sendUpdateLog->vat_on_commission,
        ];

        if (! $ignoreDiscount) {
            $sendUpdatePaymentDetails['discount_value'] = $sendUpdateLog->discount;
        }

        return $payment->update($sendUpdatePaymentDetails);
    }

    public function sendUpdateToSage($sendUpdateRequest, $sendUpdateLog)
    {
        $categoryCode = $sendUpdateLog->category?->code;
        $quoteModel = $this->getModelObject($sendUpdateRequest->quoteType);
        $quote = $quoteModel::where('id', $sendUpdateRequest->quoteRefId)->first();

        if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            info('Book Update - Sending Update to Sage300 - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
            $sageResponse = app(SageApiService::class)->handleDocumentsToSage(
                $sendUpdateRequest, $quote, [
                    'type' => SageEnum::PT_SEND_UPDATE,
                    'send_update_type' => SageEnum::SUT_NORMAL,
                    'category' => $categoryCode,
                    'option' => $sendUpdateLog->option->code,
                    'send_update_log' => $sendUpdateLog,
                    'authDetails' => auth()->user(),
                ]
            );

            return $sageResponse;
        }

        if ($categoryCode == SendUpdateLogStatusEnum::CPD) {
            info('Book Update - Sending Update to Sage300 - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid.'- Reverse Insurer Tax Invoice Number: '.$sendUpdateRequest->reversalInvoice);
            $sageResponse = app(SageApiService::class)->handleDocumentsToSage(
                $sendUpdateRequest, $quote, [
                    'type' => SageEnum::PT_SEND_UPDATE,
                    'send_update_type' => SageEnum::SUT_REVE_CORR,
                    'category' => $categoryCode,
                    'send_update_log' => $sendUpdateLog,
                    'reverse_invoice' => $sendUpdateRequest->reversalInvoice,
                    'authDetails' => auth()->user(),
                ]
            );

            return $sageResponse;
        }

        info('Book Update - Skipping Sage APIs for Send Update - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

        return ['status' => true];
    }

    public function updatesMoveToLead($sendUpdateRequest, $sendUpdateLog)
    {
        $categoryCode = $sendUpdateLog->category?->code;
        $optionCode = $sendUpdateLog->option?->code;
        $quoteModel = $this->getModelObject($sendUpdateRequest->quoteType);
        $quote = $quoteModel::where('id', $sendUpdateRequest->quoteRefId)->with(['payments' => function ($query) {
            $query->whereNull('send_update_log_id');
        }])->first();
        $currentDate = now();

        try {
            DB::beginTransaction();

            $payment = Payment::where('send_update_log_id', $sendUpdateLog->id)->first();
            if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::CPD])) {
                if ($payment) {
                    info('Book Update - Updating Payment Details for Main Lead - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $payment->update([
                        'paymentable_id' => $quote->id,
                        'paymentable_type' => ltrim($quoteModel, '\\'),
                    ]);
                }

                // Cases for Endorsment Financial Start
                if ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::PPE) {
                    info('Book Update - Updating Renewal Expiry Date for Main Lead - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $quote->update(['renewal_expiry_date' => $sendUpdateLog->expiry_date]);
                }

                if ($sendUpdateRequest->quoteType == quoteTypeCode::Car && $categoryCode == SendUpdateLogStatusEnum::EF) {
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
                // Cases for Endorsment Financial End

                // Cases for Cancel Inception and Cancel Inception Reissue Start
                if ($categoryCode === SendUpdateLogStatusEnum::CIR) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyCancelledReissued,
                        // 'quote_status_id' => QuoteStatusEnum::PolicyCancelled, // Below code overrides status, it should be PolicyCancelledReissued not PolicyCancelled
                        'quote_batch_id' => null,
                    ]);
                    (new AllocationService())->deductLeadAllocationCount($quoteModel, $sendUpdateRequest->quoteUuid);
                } elseif ($categoryCode == SendUpdateLogStatusEnum::CI || ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::MPC)) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
                    ]);
                }
                // Cases for Cancel Inception and Cancel Inception Reissue End

                // Cases for Correct Policy Details Start
                if ($categoryCode == SendUpdateLogStatusEnum::CPD && (
                    $sendUpdateRequest->reversalInvoice == $quote->payments->value('insurer_tax_number')
                )) {
                    info('Book Update - Updating Policy Details for Main Lead - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);
                    $quote->update([
                        'policy_number' => $sendUpdateLog->policy_number,
                        'policy_start_date' => $sendUpdateLog->start_date,
                        'renewal_expiry_date' => $sendUpdateLog->expiry_date,
                        'policy_booking_date' => $currentDate,
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
            logger()->error('Book Update - Error while moving updates to main lead - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid.' - Exception: '.$exception->getMessage());

            return ['status' => false, 'message' => 'Update not booked'];
        }

        info('Book Update - Update booked successfully - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdateLog->uuid);

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

        return $carQuote->plan->carAddons->toArray();
    }

    public function sendUpdateToCustomerEmail($sendUpdateLog, $action): array
    {
        $quoteTypeId = $sendUpdateLog->quote_type_id;
        $quoteType = QuoteTypeId::getOptions()[$quoteTypeId];
        $quoteModel = $this->getModelObject($quoteType);
        $quote = $quoteModel::where('uuid', $sendUpdateLog->quote_uuid)->first();
        $insuranceProviderText = $quote?->insuranceProvider?->text ?? $quote?->plan?->insuranceProvider?->text ?? '';
        $optionCode = $sendUpdateLog->option?->code;
        $categoryCode = $sendUpdateLog->category->code;

        if (in_array($categoryCode, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::EN]) && $optionCode != SendUpdateLogStatusEnum::MPC) {
            $update = $sendUpdateLog?->option->text;
        } elseif (in_array($categoryCode, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR]) || ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::MPC)) {
            $update = quoteStatusCode::POLICY_CANCELLED;
        }

        if (! in_array($quoteTypeId, [QuoteTypeId::Jetski, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            $documents = $sendUpdateLog->documents->whereIn('document_type_code', [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE])->toArray();
        } elseif (in_array($quoteTypeId, [QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            if ($action == SendUpdateLogStatusEnum::ACTION_SNBU) {
                $documents = $sendUpdateLog->documents->whereIn('document_type_code', [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                    DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE])->toArray();
            } elseif ($action == SendUpdateLogStatusEnum::ACTION_SUC) {
                $documents = $sendUpdateLog->documents->whereIn('document_type_code', [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
                    DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE])->toArray();
            } elseif ($action == SendUpdateLogStatusEnum::ACTION_SU) {
                $documents = $sendUpdateLog->documents->where('document_type_code', DocumentTypeCode::SEND_UPDATE_TAX_INVOICE)->toArray();
            }
        }

        $emailData = (object) [
            'clientFullName' => $quote->first_name.' '.$quote->last_name,
            'policyNumber' => $quote->policy_number,
            'carQuoteId' => $sendUpdateLog->code,
            'currentInsurer' => $insuranceProviderText,
            'policyUpdate' => $update ?? '',
            'customerEmail' => $quote->email,
            'advisor' => (object) [
                'landLine' => $quote->advisor->landline_no ?? '',
                'email' => $quote->advisor->email ?? '',
                'name' => $quote->advisor->name ?? '',
                'mobileNo' => $quote->advisor->mobile_no ?? '',
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
            ])) {
            return false;
        }

        return true;
    }

    public function getSendUpdateDocuments($category): array
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
                    $documentTypesByCategory[$documentCategory][$key]['is_required'] = (int) true;
                }
            }
        }

        return $documentTypesByCategory;
    }

    public function sendUpdatePriceAndDiscount($sendUpdateLog, $payment): void
    {
        $this->updatePriceAndDiscount($sendUpdateLog, $payment);
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
}
