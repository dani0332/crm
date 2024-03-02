<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Traits\GenericQueriesAllLobs;
use App\Enums\DocumentTypeCode;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;

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
            $getRelations = $quoteObject->getRelations();
            $replicateObject = $quoteObject->replicate($modelRelationDetails['skipParentColumns']);
            $replicateObject->fill([
                'code' => $quoteObject->code.'-'.++$countChildRecords,
                'uuid' => $quoteObject->uuid.'-'.$countChildRecords,
                'quote_status_id' => QuoteStatusEnum::NewLead,
                'parent_duplicate_quote_id' => $quoteObject->code,
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

    public function getUpdateButtonStatus($sendUpdateLog, $quoteType): string
    {
        $uploadedDocuments = $this->getUploadedDocuments($sendUpdateLog);
        $requiredDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];
        // it will check is 'tax invoice' and 'tax invoice by buyer' uploaded or not.
        $check = count(array_diff($requiredDocuments, $uploadedDocuments)) > 0;

        if (in_array($sendUpdateLog->category->code, [SendUpdateLogStatusEnum::EN, SendUpdateLogStatusEnum::CPU])) {
            if ($check) {
                return SendUpdateLogStatusEnum::SUC;
            }
        } elseif (count($sendUpdateLog->details) > 0) {
            if ($check) {
                return SendUpdateLogStatusEnum::SUC;
            }
        }

        return SendUpdateLogStatusEnum::SU;
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
}
