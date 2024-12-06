<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\CustomerMembers;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Models\User;
use App\Models\UserManager;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;

class SearchService extends BaseService
{
    use TeamHierarchyTrait;

    public function getSearchLeads($isEndorsementList = false, $isExport = false)
    {
        if (! empty(request()->except('list'))) {
            $baseTable = $isEndorsementList ? 'send_update_logs' : 'personal_quotes';
            if ($isEndorsementList) {
                $baseTable = 'send_update_logs';
                $query = SendUpdateLog::with([
                    'quoteType',
                    'category',
                    'option',
                    'sendUpdatePayments',
                    'insuranceProvider',
                    'personalQuote' => function ($personalQuote) {
                        $personalQuote->with([
                            'businessTypeOfInsurance',
                            'quoteRequestEntityMapping' => function ($quoteRequestEntityMapping) {
                                $quoteRequestEntityMapping->with('entity');
                            },
                        ]);
                        $this->getQuoteRelationWithEntityMapping($personalQuote);
                    },
                ]);
                $this->searchQuerySendUpdateLogsFilters($query, request());
            } else {
                $baseTable = 'personal_quotes';
                $query = PersonalQuote::with([
                    'quoteType',
                    'quoteStatus',
                    'businessTypeOfInsurance',
                    'quotePayments' => function ($quotePayment) {
                        $quotePayment->with('insuranceProvider');
                    },
                    'quoteRequestEntityMapping' => function ($quoteRequestEntityMapping) {
                        $quoteRequestEntityMapping->with('entity');
                    },
                ]);
                $this->getQuoteRelationWithEntityMapping($query);
                $this->searchQueryMainLeadFilters($query, request());
                // Reminder:: If User hasn't Admin Role then display only respective leads
                if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                    $query->where('advisor_id', auth()->id());
                }
            }

            $query->orderBy($baseTable.'.'.(request()->sortBy ?? 'updated_at'), request()->sortType ?? 'desc');

            if ($isExport) {
                return $query->get();
            }

            return $query->simplePaginate(15)->withQueryString();
        }

        return [];
    }

    private function getQuoteRelationWithEntityMapping($query): void
    {
        $query->with([
            'carQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($carQREMapping) {
                    $carQREMapping->with('entity');
                }]);
            },
            'homeQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($homeQREMapping) {
                    $homeQREMapping->with('entity');
                }]);
            },
            'healthQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($healthQREMapping) {
                    $healthQREMapping->with('entity');
                }]);
            },
            'lifeQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($lifeQREMapping) {
                    $lifeQREMapping->with('entity');
                }]);
            },
            'businessQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($businessQREMapping) {
                    $businessQREMapping->with('entity');
                }]);
            },
            'travelQuoteRequest' => function ($query) {
                $query->with(['quoteRequestEntityMapping' => function ($travelQREMapping) {
                    $travelQREMapping->with('entity');
                }]);
            },
        ]);
    }

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
