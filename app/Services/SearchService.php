<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerMembers;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\UserManager;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;

class SearchService extends BaseService
{
    use TeamHierarchyTrait;

    private mixed $filteredQuoteTypes = [];

    public function getSearchLeads($isEndorsementList = false, $isExport = false)
    {
        if (! empty(request()->except('list'))) {
            $baseTable = $isEndorsementList ? 'send_update_logs' : 'personal_quotes';
            if ($isEndorsementList) {
                $query = DB::table($baseTable)
                    ->select([
                        'send_update_logs.code',
                        'personal_quotes.first_name',
                        'personal_quotes.last_name',
                        DB::raw(' "" as company_name'),
                        'send_update_logs.quote_type_id',
                        'quote_type.code as quote_type',
                        'business_type_of_insurance.text as business_insurance_type',
                        'personal_quotes.business_type_of_insurance_id',
                        'send_update_logs.created_at',
                        'personal_quotes.policy_expiry_date',
                        'personal_quotes.policy_number',
                        'cat_lookup.text as category',
                        'opt_lookup.text as option',
                        'send_update_logs.notes',
                        'send_update_logs.status',
                    ])
                    ->join('quote_type', 'send_update_logs.quote_type_id', 'quote_type.id')
                    ->join('personal_quotes', 'personal_quotes.id', 'send_update_logs.personal_quote_id')
                    ->join('lookups as cat_lookup', 'send_update_logs.category_id', 'cat_lookup.id')
                    ->leftJoin('lookups as opt_lookup', 'send_update_logs.option_id', 'opt_lookup.id')
                    ->leftJoin('business_type_of_insurance', function ($query) {
                        $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                        $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
                    });
                SendUpdateLog::applySendUpdateEntityMappingJoin($query);
            } else {
                $personalQuoteTypes = [QuoteTypeId::Bike, QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski];
                $baseTableAgainstQuoteTypes = [
                    QuoteTypeId::Car => 'car_quote_request',
                    QuoteTypeId::Home => 'home_quote_request',
                    QuoteTypeId::Health => 'health_quote_request',
                    QuoteTypeId::Life => 'life_quote_request',
                    QuoteTypeId::Business => 'business_quote_request',
                    QuoteTypeId::Travel => 'travel_quote_request',
                ];

                $selectColumns = [
                    'personal_quotes.code',
                    'personal_quotes.uuid',
                    'personal_quotes.first_name',
                    'personal_quotes.last_name',
                    'personal_quotes.quote_type_id',
                    'quote_type.code as quote_type',
                    'business_type_of_insurance.text as business_insurance_type',
                    'personal_quotes.business_type_of_insurance_id',
                    'personal_quotes.created_at',
                    'personal_quotes.policy_expiry_date',
                    'personal_quotes.policy_number',
                    'quote_status.text as quote_status',
                ];

                $baseQuery = DB::table($baseTable)
                    ->join('quote_type', 'personal_quotes.quote_type_id', 'quote_type.id')
                    ->leftJoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
                    ->leftJoin('business_type_of_insurance', function ($query) {
                        $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                        $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
                    });

                $this->searchQuoteQueryFilters($baseQuery, request());
                PersonalQuote::applyQuoteRequestEntityMappingJoin($baseQuery, request(), $this->filteredQuoteTypes);

                $filteredCompanyCases = '';
                $filteredQuoteTypes = is_array($this->filteredQuoteTypes) ? $this->filteredQuoteTypes : [];

                if (empty($this->filteredQuoteTypes) && is_array($this->filteredQuoteTypes)) {
                    $filteredQuoteTypes = [
                        QuoteTypeId::Car,
                        QuoteTypeId::Home,
                        QuoteTypeId::Health,
                        QuoteTypeId::Life,
                        QuoteTypeId::Business,
                        QuoteTypeId::Bike,
                        QuoteTypeId::Yacht,
                        QuoteTypeId::Travel,
                        QuoteTypeId::Pet,
                        QuoteTypeId::Cycle,
                        QuoteTypeId::Jetski,
                    ];
                }

                foreach ($filteredQuoteTypes as $quoteType) {
                    $quoteRequestTable = in_array($quoteType, $personalQuoteTypes) ? 'personal' : $baseTableAgainstQuoteTypes[$quoteType];
                    $filteredCompanyCases .= ' WHEN personal_quotes.quote_type_id = '.$quoteType.' THEN '.$quoteRequestTable.'_entity.company_name';
                }

                if (! empty($filteredCompanyCases)) {
                    $selectColumns[] = DB::raw('CASE '.$filteredCompanyCases.' ELSE "N/A" END as company_name');
                } else {
                    $selectColumns[] = DB::raw('"N/A" as company_name');
                }

                $baseQuery->select($selectColumns);
            }

            $baseQuery->orderBy($baseTable.'.'.(request()->sortBy ?? 'updated_at'), request()->sortType ?? 'desc');

            if ($isExport) {
                return $baseQuery->get();
            }

            //            dd($query->limit(10)->get()->toArray());

            return $baseQuery->simplePaginate(15)->withQueryString();

            //            $baseTable = $isEndorsementList ? 'send_update_logs' : 'personal_quotes';
            //            if ($isEndorsementList) {
            //                $baseTable = 'send_update_logs';
            //                $query = SendUpdateLog::with([
            //                    'quoteType',
            //                    'category',
            //                    'option',
            //                    'sendUpdatePayments',
            //                    'insuranceProvider',
            //                    'personalQuote' => function ($personalQuote) {
            //                        $personalQuote->with([
            //                            'businessTypeOfInsurance',
            //                            'quoteRequestEntityMapping' => function ($quoteRequestEntityMapping) {
            //                                $quoteRequestEntityMapping->with('entity');
            //                            },
            //                        ]);
            //                        $this->getQuoteRelationWithEntityMapping($personalQuote);
            //                    },
            //                ]);
            //                $this->searchQuerySendUpdateLogsFilters($query, request());
            //            } else {
            //                $baseTable = 'personal_quotes';
            //                $query = PersonalQuote::with([
            //                    'quoteType',
            //                    'quoteStatus',
            //                    'businessTypeOfInsurance',
            //                    'quotePayments' => function ($quotePayment) {
            //                        $quotePayment->with('insuranceProvider');
            //                    },
            //                    'quoteRequestEntityMapping' => function ($quoteRequestEntityMapping) {
            //                        $quoteRequestEntityMapping->with('entity');
            //                    },
            //                ]);
            //                $this->getQuoteRelationWithEntityMapping($query);
            //                $this->searchQueryMainLeadFilters($query, request());
            //                // Reminder:: If User hasn't Admin Role then display only respective leads
            //                if (! auth()->user()->hasRole(RolesEnum::Admin)) {
            //                    $query->where('advisor_id', auth()->id());
            //                }
            //            }
            //
            //            $query->orderBy($baseTable.'.'.(request()->sortBy ?? 'updated_at'), request()->sortType ?? 'desc');
            //
            //            if ($isExport) {
            //                return $query->get();
            //            }
            //
            //            return $query->simplePaginate(15)->withQueryString();
        }

        return [];
    }

    private function searchQuoteQueryFilters($query, $request)
    {
        if (request()->has('code')) {
            $personalQuote = PersonalQuote::where('code', request()->code)->first();
            if (! $personalQuote) {
                return [];
            }
            $query->where('personal_quotes.code', request()->code);
            $this->filteredQuoteTypes[] = $personalQuote->quote_type_id;
        }

        if (request()->has('insured_name') && ! isset(request()->code)) {
            $customers = Customer::where(DB::raw("CONCAT(insured_first_name, ' ', insured_last_name)"), 'like', '%'.request()->insured_name.'%')->pluck('id');
            if (! $customers) {
                return [];
            }

            $query->join('customer', 'personal_quotes.customer_id', 'customer.id');
            $query->where(DB::raw("CONCAT(insured_first_name, ' ', insured_last_name)"), 'like', '%'.request()->insured_name.'%');

            $personalQuoteTypeIds = PersonalQuote::whereIn('customer_id', $customers)->pluck('quote_type_id')->unique();
            $this->filteredQuoteTypes = array_merge(array_values($personalQuoteTypeIds->toArray()), $this->filteredQuoteTypes);
        }

        if (request()->has('member_name') || request()->has('company_name')) {
            $applyFilteredDataMembers = $applyFilteredDataCompany = [];

            if (request()->has('member_name') && ! isset(request()->code)) {
                $customerMembers = CustomerMembers::where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.request()->member_name.'%')->get();
                $applyFilteredDataMembers = array_reduce($customerMembers->toArray(), function ($result, $item) {
                    if (! isset($result[$item['quote_type']])) {
                        $result[$item['quote_type']] = [];
                    }
                    $result[$item['quote_type']][] = $item['quote_id'];

                    return $result;
                }, []);
            }

            if (request()->has('company_name') && ! isset(request()->code)) {
                $getEntities = DB::table('entities')->join('quote_request_entity_mapping', 'entities.id', 'quote_request_entity_mapping.entity_id')
                    ->where('entities.company_name', 'like', '%'.request()->company_name.'%')
                    ->select(['quote_request_entity_mapping.quote_type_id', 'quote_request_entity_mapping.quote_request_id'])
                    ->get();

                $applyFilteredDataCompany = array_reduce($getEntities->toArray(), function ($result, $item) {
                    $key = ($item->quote_type_id > QuoteTypeId::Jetski) ? QuoteTypes::getClassObject(QuoteTypeId::Business) : QuoteTypes::getClassObject($item->quote_type_id);
                    if (! isset($result[$key])) {
                        $result[$key] = [];
                    }
                    $result[$key][] = $item->quote_request_id;

                    return $result;
                }, []);
            }
            $quoteTypeIds = [];
            $quoteTypesMapping = [
                CarQuote::class => QuoteTypeId::Car,
                HomeQuote::class => QuoteTypeId::Home,
                HealthQuote::class => QuoteTypeId::Health,
                LifeQuote::class => QuoteTypeId::Life,
                BusinessQuote::class => QuoteTypeId::Business,
                TravelQuote::class => QuoteTypeId::Travel,
                PersonalQuote::class => [QuoteTypeId::Bike, QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski],
            ];

            if (! empty($applyFilteredDataMembers) && ! empty($applyFilteredDataCompany)) {
                $commonQuoteTypes = array_intersect_key($applyFilteredDataMembers, $applyFilteredDataCompany);
                $applyFilteredData = [];
                foreach ($commonQuoteTypes as $key => $values) {
                    $applyFilteredData[$key] = array_intersect($values, $applyFilteredDataCompany[$key]);
                }
                $applyFilteredData = array_filter($applyFilteredData, function ($value) {
                    return ! empty($value);
                });
            } else {
                $applyFilteredData = request()->has('member_name') ? $applyFilteredDataMembers : $applyFilteredDataCompany;
            }

            $query->where(function ($query) use ($quoteTypesMapping, $applyFilteredData, &$quoteTypeIds) {
                if (! empty($applyFilteredData)) {
                    foreach ($quoteTypesMapping as $quoteClass => $quoteTypeId) {
                        $query->when(in_array($quoteClass, array_keys($applyFilteredData)), function ($query) use ($quoteTypeId, &$quoteTypeIds, $quoteClass, $applyFilteredData) {
                            if (is_array($quoteTypeId)) {
                                $quoteTypeIds = array_merge($quoteTypeIds, $quoteTypeId);
                            } else {
                                $this->filteredQuoteTypes[] = $quoteTypeId;
                                $quoteTypeIds[] = $quoteTypeId;
                            }

                            $quoteRequestTable = ($quoteClass == PersonalQuote::class) ? 'personal_quotes' : str_replace('quote', '_quote', strtolower(class_basename($quoteClass))).'_request';
                            $query->orWhereIn($quoteRequestTable.'.id', $applyFilteredData[$quoteClass]);
                        });
                    }
                } else {
                    $quoteTypeIds = [];
                    $this->filteredQuoteTypes = false;
                    $query->where('personal_quotes.id', 0);
                }
            });

            $query->whereIn('personal_quotes.quote_type_id', $quoteTypeIds);
        }

        if (request()->has('policy_number') && ! isset(request()->code)) {
            $query->where('personal_quotes.policy_number', 'like', '%'.request()->policy_number.'%');
        }

        if (request()->has('mobile_no') && ! isset(request()->code)) {
            $query->where('personal_quotes.mobile_no', request()->mobile_no);
        }

        if (request()->has('email') && ! isset(request()->code)) {
            $query->where('personal_quotes.email', request()->email);
        }

        if (request()->has('quote_status') && ! isset(request()->code)) {
            $query->where('personal_quotes.quote_status_id', request()->quote_status);
        }

        //        $query->when($request->payment_status, function ($query) use ($request) {
        //            $query->whereHas('quotePayments', function ($quotePayment) use ($request) {
        //                $quotePayment->whereIn('payment_status_id', $request->payment_status);
        //            });
        //        });

        if (request()->has('line_of_business') && ! isset(request()->code)) {
            $this->filteredQuoteTypes = request()->line_of_business;
            $valuesToRemove = [QuoteTypeId::GroupMedicalBusiness, QuoteTypeId::CorplineBusiness];
            if (array_intersect($valuesToRemove, $this->filteredQuoteTypes)) {
                $this->filteredQuoteTypes = array_diff($this->filteredQuoteTypes, $valuesToRemove);
            }

            if (! request()->has('business_insurance_type')) {
                $query->whereIn('personal_quotes.quote_type_id', $this->filteredQuoteTypes);
            }
        }

        if (request()->has('business_insurance_type') && ! isset(request()->code)) {
            $this->filteredQuoteTypes = [QuoteTypeId::Business];
            $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
            $query->whereIn('personal_quotes.business_type_of_insurance_id', request()->business_insurance_type);
        }

        if (request()->has('advisors') && ! isset(request()->code)) {
            $query->whereIn('personal_quotes.advisor_id', request()->advisors);
        }
    }

    //    private function getQuoteRelationWithEntityMapping($query): void
    //    {
    //        $query->with([
    //            'carQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($carQREMapping) {
    //                    $carQREMapping->with('entity');
    //                }]);
    //            },
    //            'homeQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($homeQREMapping) {
    //                    $homeQREMapping->with('entity');
    //                }]);
    //            },
    //            'healthQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($healthQREMapping) {
    //                    $healthQREMapping->with('entity');
    //                }]);
    //            },
    //            'lifeQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($lifeQREMapping) {
    //                    $lifeQREMapping->with('entity');
    //                }]);
    //            },
    //            'businessQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($businessQREMapping) {
    //                    $businessQREMapping->with('entity');
    //                }]);
    //            },
    //            'travelQuoteRequest' => function ($query) {
    //                $query->with(['quoteRequestEntityMapping' => function ($travelQREMapping) {
    //                    $travelQREMapping->with('entity');
    //                }]);
    //            },
    //        ]);
    //    }

    private function filterAgainstEntityCompanyName($query, $request): void
    {
        $query->where(function ($query) use ($request) {
            $query->whereHas('carQuoteRequest', function ($carQuoteRequest) use ($request) {
                $carQuoteRequest->whereHas('quoteRequestEntityMapping', function ($carQREMapping) use ($request) {
                    $carQREMapping->where('quote_type_id', QuoteTypeId::Car);
                    $carQREMapping->whereHas('entity', function ($entity) use ($request) {
                        $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                    });
                });
            })
                ->orWhereHas('homeQuoteRequest', function ($homeQuoteRequest) use ($request) {
                    $homeQuoteRequest->whereHas('quoteRequestEntityMapping', function ($homeQREMapping) use ($request) {
                        $homeQREMapping->where('quote_type_id', QuoteTypeId::Home);
                        $homeQREMapping->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                    });
                })
                ->orWhereHas('healthQuoteRequest', function ($healthQuoteRequest) use ($request) {
                    $healthQuoteRequest->whereHas('quoteRequestEntityMapping', function ($healthQREMapping) use ($request) {
                        $healthQREMapping->where('quote_type_id', QuoteTypeId::Health);
                        $healthQREMapping->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                    });
                })
                ->orWhereHas('lifeQuoteRequest', function ($lifeQuoteRequest) use ($request) {
                    $lifeQuoteRequest->whereHas('quoteRequestEntityMapping', function ($lifeQREMapping) use ($request) {
                        $lifeQREMapping->where('quote_type_id', QuoteTypeId::Life);
                        $lifeQREMapping->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                    });
                })
                ->orWhereHas('businessQuoteRequest', function ($businessQuoteRequest) use ($request) {
                    $businessQuoteRequest->whereHas('quoteRequestEntityMapping', function ($businessQREMapping) use ($request) {
                        $businessQREMapping->whereIn('quote_type_id', [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical]);
                        $businessQREMapping->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                    });
                })
                ->orWhereHas('travelQuoteRequest', function ($travelQuoteRequest) use ($request) {
                    $travelQuoteRequest->whereHas('quoteRequestEntityMapping', function ($travelQREMapping) use ($request) {
                        $travelQREMapping->where('quote_type_id', QuoteTypeId::Travel);
                        $travelQREMapping->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                    });
                })
                ->orWhereHas('quoteRequestEntityMapping', function ($travelQREMapping) use ($request) {
                    $travelQREMapping->whereIn('quote_type_id', [QuoteTypeId::Bike, QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski])
                        ->whereHas('entity', function ($entity) use ($request) {
                            $entity->where('company_name', 'like', '%'.$request->company_name.'%');
                        });
                });
        });

    }

    protected function searchQueryMainLeadFilters($query, $request): void
    {
        $query->when($request->code, function ($query) {
            $query->where('code', request()->code);
        });

        $query->when($request->insured_name, function ($query) use ($request) {
            $query->whereHas('customer', function ($customer) use ($request) {
                $customer->where(DB::raw("CONCAT(insured_first_name, ' ', insured_last_name)"), 'like', '%'.$request->insured_name.'%');
            });
        });

        $query->when($request->member_name, function ($query) use ($request) {
            $quoteCodeAgainstMembers = CustomerMembers::withWhereHas('quote')
                ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$request->member_name.'%')
                ->get()
                ->pluck('quote.code');

            $query->whereIn('code', $quoteCodeAgainstMembers);
        });

        $query->when($request->company_name, function ($query) use ($request) {
            $this->filterAgainstEntityCompanyName($query, $request);
        });

        $query->when($request->policy_number, function ($query) use ($request) {
            $query->where('policy_number', 'like', '%'.$request->policy_number.'%');
        });

        $query->when($request->mobile_no, function ($query) use ($request) {
            $query->where('mobile_no', $request->mobile_no);
        });

        $query->when($request->email, function ($query) use ($request) {
            $query->where('email', $request->email);
        });

        $query->when(($request->su_code), function ($query) use ($request) {
            $query->whereHas('sendUpdateLogs', function ($sendUpdateLog) use ($request) {
                $sendUpdateLog->where('code', $request->su_code);
            });
        });

        $query->when(($request->date_type && $request->date_range), function ($query) use ($request) {
            $paymentsDateFilters = ['payment_due_date', 'payment_date'];
            $baseTableDateFilters = ['created_at', 'policy_booking_date', 'policy_start_date', 'policy_expiry_date', 'transaction_approved_at'];
            $startDate = date('Y-m-d 00:00:00', strtotime($request->date_range[0]));
            $endDate = date('Y-m-d 23:59:59', strtotime($request->date_range[1]));

            if (in_array($request->date_type, $paymentsDateFilters)) {
                $query->whereHas('quotePayments', function ($quotePayment) use ($request, $startDate, $endDate) {
                    if ($request->date_type == 'payment_date') {
                        $quotePayment->whereHas('paymentStatusLogs', function ($paymentStatusLog) use ($startDate, $endDate) {
                            $paymentStatusLog->where('current_payment_status_id', PaymentStatusEnum::PAID);
                            $paymentStatusLog->whereBetween('created_at', [$startDate, $endDate]);
                        });
                    } else {
                        $quotePayment->whereBetween($request->date_type, [$startDate, $endDate]);
                    }
                });
            } elseif (in_array($request->date_type, $baseTableDateFilters)) {
                $query->whereBetween($request->date_type, [$startDate, $endDate]);
            }
        });

        $query->when($request->quote_status, function ($query) use ($request) {
            $query->whereIn('quote_status_id', $request->quote_status);
        });

        $query->when($request->payment_status, function ($query) use ($request) {
            $query->whereHas('quotePayments', function ($quotePayment) use ($request) {
                $quotePayment->whereIn('payment_status_id', $request->payment_status);
            });
        });

        $query->when($request->line_of_business, function ($query) use ($request) {
            $query->whereIn('quote_type_id', $request->line_of_business);
        });

        $query->when($request->business_insurance_type, function ($query) use ($request) {
            $query->where('quote_type_id', QuoteTypeId::Business);
            $query->whereIn('business_type_of_insurance_id', $request->business_insurance_type);
        });

        $query->when($request->currently_insured_with, function ($query) use ($request) {
            $query->whereHas('quotePayments', function ($personalQuote) use ($request) {
                $personalQuote->whereIn('insurance_provider_id', $request->currently_insured_with);
            });
        });

        $query->when($request->department, function ($query) use ($request) {
            $query->whereHas('advisor', function ($advisor) use ($request) {
                $advisor->whereIn('department_id', $request->department);
            });
        });

        $query->when($request->advisors, function ($query) use ($request) {
            $query->whereIn('advisor_id', $request->advisors);
        });

        $query->when(($request->insurer_tax_invoice_number), function ($query) use ($request) {
            $query->whereHas('sendUpdateLogs', function ($sendUpdateLog) use ($request) {
                $sendUpdateLog->where('insurer_tax_invoice_number', $request->insurer_tax_invoice_number);
            });
        });

        $query->when(($request->insurer_commission_tax_invoice_number), function ($query) use ($request) {
            $query->whereHas('sendUpdateLogs', function ($sendUpdateLog) use ($request) {
                $sendUpdateLog->where('insurer_commission_invoice_number', $request->insurer_commission_tax_invoice_number);
            });
        });
    }

    protected function searchQuerySendUpdateLogsFilters($query, $request): void
    {
        $query->when($request->code, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('code', $request->code);
            });
        });

        $query->when($request->insured_name, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->whereHas('customer', function ($customer) use ($request) {
                    $customer->where(DB::raw("CONCAT(insured_first_name, ' ', insured_last_name)"), 'like', '%'.$request->insured_name.'%');
                });
            });
        });

        $query->when($request->member_name, function ($query) use ($request) {
            $quoteCodeAgainstMembers = CustomerMembers::withWhereHas('quote')
                ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$request->member_name.'%')
                ->get()
                ->pluck('quote.code');

            $query->whereHas('personalQuote', function ($personalQuote) use ($quoteCodeAgainstMembers) {
                $personalQuote->whereIn('code', $quoteCodeAgainstMembers);
            });
        });

        $query->when($request->company_name, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $this->filterAgainstEntityCompanyName($personalQuote, $request);
            });
        });

        $query->when($request->policy_number, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('policy_number', 'like', '%'.$request->policy_number.'%');
            });
        });

        $query->when($request->mobile_no, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('mobile_no', $request->mobile_no);
            });
        });

        $query->when($request->email, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('email', $request->email);
            });
        });

        $query->when($request->su_code, function ($query) use ($request) {
            $query->where('code', $request->su_code);
        });

        $query->when(($request->date_type && $request->date_range), function ($query) use ($request) {
            $paymentsDateFilters = ['payment_due_date', 'payment_date'];
            $baseTableDateFilters = ['created_at', 'policy_booking_date', 'policy_start_date', 'policy_expiry_date', 'transaction_approved_at'];
            $startDate = date('Y-m-d 00:00:00', strtotime($request->date_range[0]));
            $endDate = date('Y-m-d 23:59:59', strtotime($request->date_range[1]));

            if (in_array($request->date_type, $paymentsDateFilters)) {
                $query->whereHas('sendUpdatePayments', function ($sendUpdatePayments) use ($request, $startDate, $endDate) {
                    if ($request->date_type == 'payment_date') {
                        $sendUpdatePayments->whereHas('paymentStatusLogs', function ($paymentStatusLog) use ($startDate, $endDate) {
                            $paymentStatusLog->where('current_payment_status_id', PaymentStatusEnum::PAID);
                            $paymentStatusLog->whereBetween('created_at', [$startDate, $endDate]);
                        });
                    } else {
                        $sendUpdatePayments->whereBetween($request->date_type, [$startDate, $endDate]);
                    }
                });
            } elseif (in_array($request->date_type, $baseTableDateFilters)) {
                if ($request->date_type == 'created_at') {
                    $query->whereBetween($request->date_type, [$startDate, $endDate]);
                } else {
                    $query->whereHas('personalQuote', function ($personalQuote) use ($request, $startDate, $endDate) {
                        $personalQuote->whereBetween($request->date_type, [$startDate, $endDate]);
                    });
                }
            }
        });

        $query->when($request->quote_status, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('quote_status_id', $request->quote_status);
            });
        });

        $query->when($request->payment_status, function ($query) use ($request) {
            $query->whereHas('sendUpdatePayments', function ($sendUpdatePayment) use ($request) {
                $sendUpdatePayment->where('payment_status_id', $request->payment_status);
            });
        });

        $query->when($request->line_of_business, function ($query) use ($request) {
            $query->whereIn('quote_type_id', $request->line_of_business);
        });

        $query->when($request->business_insurance_type, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('quote_type_id', QuoteTypeId::Business);
                $personalQuote->where('business_type_of_insurance_id', $request->business_insurance_type);
            });
        });

        $query->when($request->currently_insured_with, function ($query) use ($request) {
            $query->whereIn('insurance_provider_id', $request->currently_insured_with);
        });

        $query->when($request->department, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->whereHas('advisor', function ($advisor) use ($request) {
                    $advisor->whereIn('department_id', $request->department);
                });
            });
        });

        $query->when($request->advisors, function ($query) use ($request) {
            $query->whereHas('personalQuote', function ($personalQuote) use ($request) {
                $personalQuote->where('advisor_id', $request->advisors);
            });
        });

        $query->when($request->insurer_tax_invoice_number, function ($query) use ($request) {
            $query->where('insurer_tax_invoice_number', $request->insurer_tax_invoice_number);
        });

        $query->when($request->insurer_commission_tax_invoice_number, function ($query) use ($request) {
            $query->where('insurer_commission_invoice_number', $request->insurer_commission_tax_invoice_number);
        });

        $query->when($request->update_status, function ($query) use ($request) {
            $formattedUpdateStatuses = array_map(function ($string) {
                return strtoupper(str_replace(' ', '_', $string));
            }, $request->update_status);
            $query->whereIn('status', $formattedUpdateStatuses);
        });

        $query->when($request->send_update_type, function ($query) use ($request) {
            $query->whereIn('category_id', $request->send_update_type);
        });
    }

    public function getAdvisorsList()
    {
        $usersByTeamProduct = $this->usersByTeamProduct();
        if (! auth()->user()->hasAnyRole([
            RolesEnum::SeniorManagement,
            RolesEnum::Admin,
            RolesEnum::Engineering,
        ])) {
            $usersReportToLoggedInUser = UserManager::where('manager_id', auth()->user()->id)
                ->whereIn('user_id', $usersByTeamProduct)->pluck('user_id')->toArray();
        }

        return User::whereIn('id', $usersByTeamProduct)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }
}
