<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\PersonalQuote;
use App\Models\Role;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;

class SearchService extends BaseService
{
    use TeamHierarchyTrait;

    public $paymentsDateFilters = ['payment_due_date', 'payment_date'];

    public function getSearchLeads($isEndorsementList = false, $isExport = false)
    {
        if (! empty(request()->except('list'))) {
            $baseTable = $isEndorsementList ? 'send_update_logs' : 'personal_quotes';
            $selectColumns = [
                'personal_quotes.code',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'personal_quotes.quote_type_id',
                'quote_type.code as quote_type',
                'business_type_of_insurance.text as business_insurance_type',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.created_at',
                'personal_quotes.policy_expiry_date',
                'personal_quotes.policy_number',
            ];

            if ($isEndorsementList) {
                $suSelectColumns = [
                    'send_update_logs.code',
                    'send_update_logs.uuid',
                    'send_update_logs.quote_type_id',
                    'send_update_logs.created_at',
                    'personal_quotes.uuid as quote_uuid',
                    'cat_lookup.text as category',
                    'opt_lookup.text as option',
                    'send_update_logs.notes',
                    'send_update_logs.status',
                ];

                $baseQuery = DB::table($baseTable)
                    ->join('quote_type', 'send_update_logs.quote_type_id', 'quote_type.id')
                    ->join('personal_quotes', 'personal_quotes.id', 'send_update_logs.personal_quote_id')
                    ->join('lookups as cat_lookup', 'send_update_logs.category_id', 'cat_lookup.id')
                    ->leftJoin('lookups as opt_lookup', 'send_update_logs.option_id', 'opt_lookup.id')
                    ->leftJoin('business_type_of_insurance', function ($query) {
                        $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                        $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
                    });

                $selectColumns = array_merge($selectColumns, $suSelectColumns);
                PersonalQuote::applyRequestTableJoins($baseQuery, request());
                $this->searchQuoteQueryFilters($baseQuery, request(), $isEndorsementList);
                $selectColumns = $this->getFilteredCompanyCases(request(), $selectColumns);

            } else {
                $baseQuery = DB::table($baseTable)
                    ->join('quote_type', 'personal_quotes.quote_type_id', 'quote_type.id')
                    ->leftJoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
                    ->leftJoin('business_type_of_insurance', function ($query) {
                        $query->on('business_type_of_insurance.id', 'personal_quotes.business_type_of_insurance_id');
                        $query->where('personal_quotes.quote_type_id', QuoteTypeId::Business);
                    });
                PersonalQuote::applyRequestTableJoins($baseQuery, request());
                $this->searchQuoteQueryFilters($baseQuery, request());
                $selectColumns = array_merge($selectColumns, ['personal_quotes.uuid', 'quote_status.text as quote_status']);
                $selectColumns = $this->getFilteredCompanyCases(request(), $selectColumns);
            }

            if ($this->isManagerialRole()) {
                $baseQuery->whereIn('personal_quotes.advisor_id', $this->getAdvisorsByManagers());
            }

            $baseQuery->when($this->isAdvisorRole(), function ($query) {
                $query->where('personal_quotes.advisor_id', auth()->id());
            });

            $baseQuery->orderBy($baseTable.'.'.(request()->sortBy ?? 'updated_at'), request()->sortType ?? 'desc');

            if ($isExport) {
                $excelExportColumns = [];
                if (! request()->has('insured_name')) {
                    $baseQuery->leftJoin('customer', 'personal_quotes.customer_id', 'customer.id');
                    $excelExportColumns = array_merge($excelExportColumns, ['customer.first_name as customer_first_name', 'customer.last_name as customer_last_name']);
                }

                if (! request()->has('department')) {
                    $baseQuery->leftJoin('users', 'personal_quotes.advisor_id', 'users.id');
                }

                if (! (request()->has('date_type') && in_array(request()->date_type, $this->paymentsDateFilters)) && ! request()->has('payment_status')
                    && ! request()->has('insurer_tax_invoice_number') && ! request()->has('insurer_commission_tax_invoice_number')) {
                    if ($isEndorsementList) {
                        $baseQuery->leftJoin('payments', 'send_update_logs.id', 'payments.send_update_log_id');
                    } else {
                        $baseQuery->leftJoin('payments', 'personal_quotes.code', 'payments.code');
                    }
                }

                if ($isEndorsementList) {
                    $baseQuery->leftJoin('insurance_provider', 'send_update_logs.insurance_provider_id', 'insurance_provider.id');
                } else {
                    $baseQuery->leftJoin('insurance_provider', 'payments.insurance_provider_id', 'insurance_provider.id');
                }

                $excelExportColumns = array_merge($excelExportColumns, ['payments.total_price', 'insurance_provider.text as insurance_provider']);
                $baseQuery->select(array_merge($selectColumns, $excelExportColumns));

                return $baseQuery->get();
            }

            $baseQuery->select($selectColumns);

            return $baseQuery->simplePaginate(15)->withQueryString();
        }

        return [];
    }

    private function isManagerialRole(): bool
    {
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HOME->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::BUSINESS->value,
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ];

        // Get manager roles based on quote types
        $managerRoles = collect($lobs)
            ->map(fn ($lob) => strtoupper($lob).'_MANAGER')
            ->toArray();

        $managers = Role::whereIn('name', $managerRoles)->get();

        return $managers->isNotEmpty() && auth()->user()->hasAnyRole($managerRoles);
    }

    private function isAdvisorRole(): bool
    {
        $advisorRoles = [
            RolesEnum::CarAdvisor,
            RolesEnum::HomeAdvisor,
            RolesEnum::HealthAdvisor,
            RolesEnum::LifeAdvisor,
            RolesEnum::BusinessAdvisor,
            RolesEnum::BikeAdvisor,
            RolesEnum::YachtAdvisor,
            RolesEnum::TravelAdvisor,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleAdvisor,
            RolesEnum::JetskiAdvisor,
        ];

        return auth()->user()->hasAnyRole($advisorRoles);
    }

    private function getFilteredCompanyCases($request, $selectColumns): array
    {
        if ($request->has('company_name') && $request->has('line_of_business')) {
            $selectColumns[] = 'entities.company_name';
        } else {
            $selectColumns[] = DB::raw('"N/A" as company_name');
        }

        return $selectColumns;
    }

    private function searchQuoteQueryFilters($query, $request, $isSendUpdateFilter = false): void
    {
        if (request()->has('code')) {
            $query->where('personal_quotes.code', request()->code);
        }

        if (request()->has('insured_name') && ! isset(request()->code)) {
            $query->join('customer', 'personal_quotes.customer_id', 'customer.id');
            $query->where(DB::raw("CONCAT(customer.insured_first_name, ' ', customer.insured_last_name)"), 'like', '%'.request()->insured_name.'%');
        }

        if (request()->has('member_first_name') || request()->has('member_last_name')) {
            $resolveQuoteTypeObject = QuoteTypes::getQuoteTypeIdToClass(request()->line_of_business);
            $isPersonalQuote = $resolveQuoteTypeObject == PersonalQuote::class;
            $query->join('customer_members', function ($query) use ($isPersonalQuote) {
                if ($isPersonalQuote) {
                    $query->on('personal_quotes.id', 'customer_members.quote_id');
                } else {
                    $quoteTypes = [
                        QuoteTypeId::Car => 'car_quote_request',
                        QuoteTypeId::Home => 'home_quote_request',
                        QuoteTypeId::Health => 'health_quote_request',
                        QuoteTypeId::Life => 'life_quote_request',
                        QuoteTypeId::Business => 'business_quote_request',
                        QuoteTypeId::Travel => 'travel_quote_request',
                    ];
                    $query->on($quoteTypes[request()->line_of_business].'.id', 'customer_members.quote_id');
                }
            });
            $query->when(request()->has('member_first_name') || request()->has('member_last_name'), function ($query) {
                if (request()->has('member_first_name')) {
                    $query->where('customer_members.first_name', 'like', '%'.request()->member_first_name.'%');
                }
                if (request()->has('member_last_name')) {
                    $query->where('customer_members.last_name', 'like', '%'.request()->member_last_name.'%');
                }
            });
        }

        if (request()->has('company_name')) {
            $resolveQuoteTypeObject = QuoteTypes::getQuoteTypeIdToClass(request()->line_of_business);
            $isPersonalQuote = $resolveQuoteTypeObject == PersonalQuote::class;
            $query->join('quote_request_entity_mapping', function ($query) use ($isPersonalQuote) {
                if ($isPersonalQuote) {
                    $query->on('personal_quotes.id', 'quote_request_entity_mapping.quote_request_id');
                } else {
                    $quoteTypes = [
                        QuoteTypeId::Car => 'car_quote_request',
                        QuoteTypeId::Home => 'home_quote_request',
                        QuoteTypeId::Health => 'health_quote_request',
                        QuoteTypeId::Life => 'life_quote_request',
                        QuoteTypeId::Business => 'business_quote_request',
                        QuoteTypeId::Travel => 'travel_quote_request',
                    ];
                    $query->on($quoteTypes[request()->line_of_business].'.id', 'quote_request_entity_mapping.quote_request_id');
                }
                $query->where('quote_request_entity_mapping.quote_type_id', request()->line_of_business);
            });
            $query->join('entities', 'quote_request_entity_mapping.entity_id', 'entities.id');
            $query->where('entities.company_name', 'like', '%'.request()->company_name.'%');
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

        if (request()->has('su_code')) {
            if (! $isSendUpdateFilter) {
                $query->join('send_update_logs', 'personal_quotes.id', 'send_update_logs.personal_quote_id');
            }
            $query->where('send_update_logs.code', request()->su_code);
        }

        if (request()->has('date_type') && request()->has('date_range')) {
            $baseTableDateFilters = ['created_at', 'policy_booking_date', 'policy_start_date', 'policy_expiry_date', 'transaction_approved_at'];
            $startDate = date('Y-m-d 00:00:00', strtotime($request->date_range[0]));
            $endDate = date('Y-m-d 23:59:59', strtotime($request->date_range[1]));

            if (in_array($request->date_type, $this->paymentsDateFilters)) {
                if ($isSendUpdateFilter) {
                    $query->join('payments', 'send_update_logs.id', 'payments.send_update_log_id');
                } else {
                    $query->join('payments', 'personal_quotes.code', 'payments.code');
                }
                if ($request->date_type == 'payment_date') {
                    $query->join('payment_status_log', 'payments.paymentable_id', 'payment_status_log.quote_request_id');
                    $query->where('payment_status_log.current_payment_status_id', PaymentStatusEnum::PAID);
                    $query->whereBetween('payment_status_log.created_at', [$startDate, $endDate]);
                } else {
                    $query->whereBetween('payments.'.$request->date_type, [$startDate, $endDate]);
                }
            } elseif (in_array($request->date_type, $baseTableDateFilters)) {
                if ($isSendUpdateFilter && $request->date_type == 'created_at') {
                    $query->whereBetween('send_update_logs.'.$request->date_type, [$startDate, $endDate]);
                } else {
                    $query->whereBetween('personal_quotes.'.$request->date_type, [$startDate, $endDate]);
                }
            }
        }

        if (request()->has('quote_status') && ! isset(request()->code)) {
            $query->where('personal_quotes.quote_status_id', request()->quote_status);
        }

        if (request()->has('payment_status') && ! isset(request()->code)) {
            if (request()->has('date_type') && ! in_array($request->date_type, $this->paymentsDateFilters)) {
                if ($isSendUpdateFilter) {
                    $query->join('payments', 'send_update_logs.id', 'payments.send_update_log_id');
                } else {
                    $query->join('payments', 'personal_quotes.code', 'payments.code');
                }
            }
            $query->whereIn('payments.payment_status_id', request()->payment_status);
        }

        if (request()->has('line_of_business') && ! isset(request()->code)) {
            $query->whereIn('personal_quotes.quote_type_id', (array) request()->line_of_business);
        }

        if (request()->has('business_insurance_type') && ! isset(request()->code)) {
            $query->whereIn('personal_quotes.business_type_of_insurance_id', request()->business_insurance_type);
        }

        if (request()->has('currently_insured_with') && ! isset(request()->code)) {
            $query->whereIn('personal_quotes.insurance_provider_id', $request->currently_insured_with);
        }

        if (request()->has('department') && ! isset(request()->code)) {
            $query->join('users', 'personal_quotes.advisor_id', 'users.id');
            $query->whereIn('users.department_id', request()->department);
        }

        if (request()->has('advisors') && ! isset(request()->code)) {
            $query->whereIn('personal_quotes.advisor_id', request()->advisors);
        }

        if ((request()->has('insurer_tax_invoice_number') || request()->has('insurer_commission_tax_invoice_number'))
            && ! isset(request()->code) && ! request()->has('payment_status')
            && ! (request()->has('date_type') && in_array(request()->date_type, $this->paymentsDateFilters))
        ) {

            if (! request()->has('su_code') && ! $isSendUpdateFilter) {
                $query->join('payments', 'personal_quotes.code', 'payments.code');

                if (request()->has('insurer_tax_invoice_number')) {
                    $query->where('payments.insurer_tax_number', request()->insurer_tax_invoice_number);
                }

                if (request()->has('insurer_commission_tax_invoice_number')) {
                    $query->where('payments.insurer_commmission_invoice_number', request()->insurer_commission_tax_invoice_number);
                }
            } else {
                $query->leftJoin('payments', 'send_update_logs.id', 'payments.send_update_log_id');

                if (request()->has('insurer_tax_invoice_number')) {
                    $query->where('send_update_logs.insurer_tax_invoice_number', request()->insurer_tax_invoice_number);
                }

                if (request()->has('insurer_commission_tax_invoice_number')) {
                    $query->where('send_update_logs.insurer_commission_invoice_number', request()->insurer_commission_tax_invoice_number);
                }
            }
        }

        if (request()->has('update_status') && ! isset(request()->su_code)) {
            $formattedUpdateStatuses = array_map(function ($string) {
                return strtoupper(str_replace(' ', '_', $string));
            }, $request->update_status);
            $query->whereIn('send_update_logs.status', $formattedUpdateStatuses);
        }

        if (request()->has('send_update_type') && ! isset(request()->su_code)) {
            $query->whereIn('send_update_logs.category_id', $request->send_update_type);
        }
    }
}
