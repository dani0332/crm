<?php

namespace App\Traits;

use App\Enums\CarRegistrationType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\PrivateClientConfigService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait PrivateClient
{
    private const OPERATOR_IN = 'in';
    private const OPERATOR_NOT_IN = 'not in';
    private const OPERATOR_BETWEEN = 'between';
    private const OPERATOR_NOT_BETWEEN = 'not between';
    private const OPERATOR_LIKE = 'like';
    private const OPERATOR_NOT_LIKE = 'not like';
    private const OPERATOR_IS_NULL = 'is null';
    private const OPERATOR_IS_NOT_NULL = 'is not null';
    private const PCP_CHUNK_SIZE = 100;

    private function isLOBEligibleForPCP(int $quoteTypeId)
    {
        return in_array($quoteTypeId, [
            QuoteTypeId::Car,
            QuoteTypeId::Home,
            QuoteTypeId::Health,
            QuoteTypeId::Life,
            QuoteTypeId::Yacht,
        ]);
    }

    /**
     * Apply PCP conditions and update pcp_tag on customer profile.
     */
    public function applyPcpTag(string $leadUuid, int $quoteTypeId): bool
    {
        if (! $this->isLOBEligibleForPCP($quoteTypeId)) {
            LoggerService::warning('LOB not eligible for PCP yet.', extra: [
                'quoteTypeId' => $quoteTypeId,
            ]);

            return false;
        }

        $modelClass = $quoteTypeId === QuoteTypeId::Yacht || $quoteTypeId === QuoteTypeId::Home ? PersonalQuote::class : QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);
        if (! class_exists($modelClass)) {
            LoggerService::warning('Model class not found.', extra: [
                'quoteTypeId' => $quoteTypeId,
            ]);

            return false;
        }

        // Find the lead model
        $model = $this->findLeadModel($modelClass, $leadUuid, $quoteTypeId);
        if (! $model) {
            return false;
        }
        $configs = null;

        $quoteType = QuoteTypes::getName($quoteTypeId);
        if ($quoteType && $quoteType instanceof QuoteTypes) {
            $configs = app(PrivateClientConfigService::class)->evaluateConfig($quoteType, $model->nationality_id);
        }

        if (empty($configs)) {
            LoggerService::warning('no configration found for this quoteType.', extra: [
                'quoteType' => $quoteType,
            ]);

            return false;
        }

        // Check if lead matches PCP criteria
        if (! $this->doesLeadMatchPcpCriteria($model, $configs, $modelClass, $quoteTypeId)) {
            LoggerService::warning('Lead not matched PCP criteria.', extra: [
                'tag_version_criteria' => $configs->toArray(),
            ]);

            return false;
        }

        $version = $configs->first()->version;

        LoggerService::info('PCP tag version '.$version.' found for '.$leadUuid);

        // Apply PCP tags
        return $this->applyPcpTagsToLeadAndCustomer($model, $version);
    }

    public function reEvaluatePrivateClient(array $data): array
    {
        $quoteTypeId = (int) $data['quote_type_id'];

        if (! $this->isLOBEligibleForPCP($quoteTypeId)) {
            LoggerService::warning('LOB not eligible for PCP re-evaluation.', extra: [
                'quoteTypeId' => $quoteTypeId,
            ]);

            return [
                'processed' => 0,
                'tagged' => 0,
                'untagged' => 0,
                'failures' => [
                    [
                        'quote_type_id' => $quoteTypeId,
                        'reason' => 'ineligible_quote_type',
                    ],
                ],
            ];
        }

        $quoteType = QuoteTypes::getName($quoteTypeId);
        $modelClass = $quoteTypeId === QuoteTypeId::Yacht || $quoteTypeId === QuoteTypeId::Home ? PersonalQuote::class : QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);

        if (! $quoteType || ! class_exists($modelClass)) {
            LoggerService::warning('Unable to resolve quote type for PCP re-evaluation.', extra: [
                'quoteTypeId' => $quoteTypeId,
                'modelClass' => $modelClass,
            ]);

            return [
                'processed' => 0,
                'tagged' => 0,
                'untagged' => 0,
                'failures' => [
                    [
                        'quote_type_id' => $quoteTypeId,
                        'reason' => 'model_not_found',
                    ],
                ],
            ];
        }

        $dateFormat = 'Y-m-d';
        $createdAtRange = null;
        $createdAtInput = $data['created_at'] ?? null;

        if (is_array($createdAtInput) && isset($createdAtInput['start'], $createdAtInput['end'])) {
            try {
                $start = Carbon::createFromFormat($dateFormat, trim((string) $createdAtInput['start']))->startOfDay();
                $end = Carbon::createFromFormat($dateFormat, trim((string) $createdAtInput['end']))->endOfDay();

                $createdAtRange = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                ];
            } catch (Exception $exception) {
                LoggerService::warning('Invalid created_at filter value received for PCP re-evaluation.', extra: [
                    'start' => $createdAtInput['start'],
                    'end' => $createdAtInput['end'],
                ], exception: $exception);
            }
        }

        $hasPcpAssignedFilter = array_key_exists('is_pcp_assigned', $data);
        $filters = [
            'lead_uuids' => $data['lead_uuids'] ?? null,
            'is_policy_booked' => $data['is_policy_booked'] ?? null,
            'is_pcp_assigned' => $hasPcpAssignedFilter ? $data['is_pcp_assigned'] : null,
            'has_pcp_assigned_filter' => $hasPcpAssignedFilter,
            'created_at' => $createdAtRange,
        ];

        LoggerService::info('Starting PCP re-evaluation.', extra: [
            'quoteTypeId' => $quoteTypeId,
            'filters' => array_filter($filters, fn ($value) => $value !== null),
        ]);

        $results = [
            'processed' => 0,
            'tagged' => 0,
            'untagged' => 0,
            'failures' => [],
        ];

        $modelInstance = new $modelClass;
        $tableColumns = $this->getCachedTableColumns($modelClass, $modelInstance->getTable());

        $query = $modelInstance->newQuery();

        $selectedColumns = array_values(array_intersect($tableColumns, [
            'id',
            'uuid',
            'customer_id',
            'nationality_id',
            'quote_status_id',
            'pc_qualified',
            'pcp_tag_version',
            'policy_expiry_date',
            'quote_type_id',
            'created_at',
            'code',
        ]));

        if (! empty($selectedColumns)) {
            $query->select($selectedColumns);
        }

        if (in_array('quote_type_id', $tableColumns)) {
            $query->where('quote_type_id', $quoteTypeId);
        }

        if (! empty($filters['lead_uuids'])) {
            $query->whereIn('uuid', $filters['lead_uuids']);
        }

        if ($filters['is_policy_booked'] !== null && in_array('quote_status_id', $tableColumns)) {
            $bookedStatus = QuoteStatusEnum::PolicyBooked;
            if ($filters['is_policy_booked']) {
                $query->where('quote_status_id', $bookedStatus);
            }
            // If is_policy_booked is false, do not apply any filter on quote_status_id.
        }

        if ($filters['created_at'] !== null && in_array('created_at', $tableColumns)) {
            $query->whereBetween('created_at', [
                $filters['created_at']['start'],
                $filters['created_at']['end'],
            ]);
        }

        // Apply PCP assigned filter only if 'is_pcp_assigned' is present in filters and 'pc_qualified' column exists in the table
        if ($filters['has_pcp_assigned_filter'] && in_array('pc_qualified', $tableColumns)) {
            $query->where(function ($pcpQuery) use ($filters) {
                if ($filters['is_pcp_assigned']) {
                    $pcpQuery->where('pc_qualified', true);
                } else {
                    $pcpQuery->where(function ($nestedQuery) {
                        $nestedQuery->whereNull('pc_qualified')->orWhere('pc_qualified', false);
                    });
                }
            });
        }

        LoggerService::sql('Re-evaluation Query:', $query);

        // TODO: N+1 here; cache configs per quote type/customer/lead in class props and reuse instead of querying each loop iteration.
        $query->orderBy('id')->chunkById(self::PCP_CHUNK_SIZE, function ($leads) use (&$results, $quoteType, $modelClass, $quoteTypeId) {
            foreach ($leads as $lead) {
                $results['processed']++;

                try {
                    $configs = app(PrivateClientConfigService::class)->evaluateConfig($quoteType, $lead->nationality_id);

                    if (empty($configs)) {
                        LoggerService::warning('No PCP configuration found for quote type.', extra: [
                            'quoteTypeId' => $quoteTypeId,
                            'leadUuid' => $lead->uuid,
                        ]);

                        $results['failures'][] = [
                            'lead_uuid' => $lead->uuid,
                            'reason' => 'configuration_not_found',
                        ];

                        continue;
                    }

                    $matchesCriteria = $this->doesLeadMatchPcpCriteria($lead, $configs, $modelClass, $quoteTypeId);
                    $version = $configs->first()?->version;
                    $pcpUpdated = $matchesCriteria
                        ? $this->applyPcpTagsToLeadAndCustomer($lead, $version)
                        : $this->applyPcpTagsToLeadAndCustomer($lead, $version, true);

                    if (! $pcpUpdated) {
                        $results['failures'][] = [
                            'lead_uuid' => $lead->uuid,
                            'reason' => 'pcp_update_failed',
                        ];

                        continue;
                    }

                    $matchesCriteria ? $results['tagged']++ : $results['untagged']++;
                } catch (\Throwable $ex) {
                    LoggerService::error('Error while re-evaluating PCP.', extra: [
                        'quoteTypeId' => $quoteTypeId,
                        'leadUuid' => $lead->uuid,
                    ], exception: $ex);

                    $results['failures'][] = [
                        'lead_uuid' => $lead->uuid,
                        'reason' => 'exception',
                    ];
                }
            }
        });

        LoggerService::info('Completed PCP re-evaluation.', extra: [
            'quoteTypeId' => $quoteTypeId,
            'summary' => $results,
        ]);

        return $results;
    }

    private function findLeadModel(string $modelClass, string $leadUuid, int $quoteTypeId)
    {
        $model = (new $modelClass)->where('uuid', $leadUuid);

        if ($quoteTypeId === QuoteTypeId::Home) {
            $model->with('homeQuote');
        }
        $model = $model->first();

        if (! $model) {
            LoggerService::warning('Lead not found.', extra: [
                'quoteTypeId' => $quoteTypeId,
                'leadId' => $leadUuid,
            ]);
        }

        return $model;
    }

    /**
     * Apply quote type specific conditions to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyQuoteTypeSpecificConditions($query, int $quoteTypeId)
    {
        $conditions = $this->getQuoteTypeConditions($quoteTypeId);

        LoggerService::info('conditions', ['conditions' => $conditions]);

        foreach ($conditions as $condition) {
            $query->where($condition['column'], $condition['operator'], $condition['value']);
        }

        return $query;
    }

    /**
     * Get conditions specific to quote type
     */
    private function getQuoteTypeConditions(int $quoteTypeId): array
    {
        $conditions = [];

        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
                $conditions[] = [
                    'column' => 'registration_type',
                    'operator' => '=',
                    'value' => CarRegistrationType::PERSONAL,
                ];
                break;
        }

        return $conditions;
    }

    private function doesLeadMatchPcpCriteria($model, $configs, string $modelClass, int $quoteTypeId): bool
    {
        $tableColumns = $this->getCachedTableColumns($modelClass, $model->getTable());

        LoggerService::info('tableColumns', ['tableColumns' => $tableColumns]);

        $whereClause = $this->buildConfigWhereClause($configs, $tableColumns, $model);

        // Log the configs that will be used to build the where clause for debugging
        LoggerService::info('PCP configs for where clause', [
            'configs' => $configs->map(function ($config) {
                return [
                    'field_name' => $config->field_name,
                    'operator' => $config->operator,
                    'value' => $config->value,
                    'currency_type_id' => $config->currency_type_id ?? null,
                ];
            })->toArray(),
        ]);

        $query = (new $modelClass)->where('uuid', $model->uuid)
            ->where($whereClause);

        $this->applyQuoteTypeSpecificConditions($query, $quoteTypeId);

        LoggerService::sql('doesLeadMatchPcpCriteria', $query);

        return $query->exists();
    }

    /**
     * Remove PCP tag from customer table.
     *
     * Gather active qualifiers, count leads where PC-Qualified = Yes AND
     * lead_status not in Policy Cancelled AND
     * Expiry Date > Today's date (the expiry date has NOT passed).
     *
     * Outcome:
     * If count > 0 → keep the Private Client tag.
     * If count = 0 → remove the tag and write an audit-log entry on the Contact-Person profile.
     */
    public function removePcpTag()
    {
        Customer::where('pcp_tag', true)
            ->whereDoesntHave('personalQuote', function ($query) {
                $query->where('pc_qualified', true)
                    ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                    ->whereNotNull('policy_expiry_date')
                    ->where('policy_expiry_date', '>', now());
            })
            ->chunk(100, function ($customers) {

                foreach ($customers as $customer) {

                    $customerLogObject = [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->first_name.' '.$customer->last_name,
                        'email' => $customer->email,
                    ];

                    LoggerService::info('Removing PCP tag as no active qualified leads were found.', extra: $customerLogObject);

                    try {
                        $customer->update(['pcp_tag' => false]);

                        LoggerService::info('PCP tag removed successfully.', extra: $customerLogObject);
                    } catch (\Exception $ex) {
                        LoggerService::error('Error removing PCP tag. Continuing with next customer.', extra: $customerLogObject, exception: $ex);
                        // Do not throw — continue with next customer
                    }
                }
            });
    }

    private function applyPcpTagsToLeadAndCustomer($model, ?int $pcpTagVersion, bool $shouldRemove = false): bool
    {
        try {
            return DB::transaction(function () use ($pcpTagVersion, $model, $shouldRemove) {
                LoggerService::info($shouldRemove ? 'Removing PCP tag from lead and customer.' : 'Applying PCP tag to lead and customer.', extra: [
                    'leadUuid' => $model->uuid,
                    'pcpTagVersion' => $pcpTagVersion,
                ]);

                $updateResults = $this->updateLeadAndPersonalQuote($model, $pcpTagVersion, $shouldRemove);

                $customerUpdateResult = $this->updateCustomer($model, $pcpTagVersion, $shouldRemove);

                $this->logUpdateResults($updateResults, $customerUpdateResult, $shouldRemove);

                return true;
            });
        } catch (Exception $ex) {
            LoggerService::error($shouldRemove ? 'Error removing PCP tag.' : 'Error applying PCP tag.', exception: $ex);

            return false;
        }
    }

    private function updateLeadAndPersonalQuote($model, ?int $pcpTagVersion, bool $shouldRemove = false): array
    {
        $wasLeadUpdated = false;

        $updateData = $shouldRemove
            ? ['pc_qualified' => false, 'pcp_tag_version' => null]
            : ['pc_qualified' => true, 'pcp_tag_version' => $pcpTagVersion];

        $shouldUpdateLead = $shouldRemove
            ? ($model->pc_qualified !== false || ! is_null($model->pcp_tag_version))
            : ($model->pc_qualified !== true || $model->pcp_tag_version !== $pcpTagVersion);

        if ($shouldUpdateLead) {

            $model->update($updateData);
            PersonalQuote::where('uuid', $model->uuid)
                ->get()
                ->each
                ->update($updateData);

            $wasLeadUpdated = true;
            LoggerService::info($shouldRemove ? 'PCP tag removed on lead.' : 'PC qualified tag applied successfully on lead.', extra: [
                'leadUuid' => $model->uuid,
            ]);
        }

        return [
            'wasUpdated' => $wasLeadUpdated,
            'version' => $shouldRemove ? null : $pcpTagVersion,
            'shouldRemove' => $shouldRemove,
        ];
    }

    private function updateCustomer($model, ?int $pcpTagVersion, bool $shouldRemove = false): array
    {
        $customer = Customer::where('id', $model->customer_id)
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'pcp_tag',
                'pcp_tag_version',
            ])
            ->first();
        $wasCustomerUpdated = false;
        $retainedExistingTag = false;

        if ($customer) {
            if ($shouldRemove) {
                $hasQualifiedQuotes = $this->customerHasActiveQualifiedQuotes((int) $customer->id);

                if (! $hasQualifiedQuotes && $customer->pcp_tag) {
                    $customer->update([
                        'pcp_tag' => false,
                        'pcp_tag_version' => null,
                    ]);

                    $wasCustomerUpdated = true;
                    LoggerService::info('PCP tag removed successfully on customer.', extra: [
                        'customer_id' => $customer->id,
                        'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                        'email' => $customer->email,
                    ]);
                } elseif ($hasQualifiedQuotes) {
                    $retainedExistingTag = true;
                    LoggerService::info('PCP tag retained on customer because other qualified leads exist.', extra: [
                        'customer_id' => $customer->id,
                        'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                        'email' => $customer->email,
                    ]);
                }
            } elseif (! $customer->pcp_tag || $customer->pcp_tag_version !== $pcpTagVersion) {
                $customer->update([
                    'ref_id' => $model->code,
                    'pcp_tag' => true,
                    'pcp_tag_version' => $pcpTagVersion,
                ]);

                $wasCustomerUpdated = true;
                LoggerService::info('PCP tag applied successfully on customer.', extra: [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                    'email' => $customer->email,
                ]);
            }
        }

        return [
            'customer' => $customer,
            'wasUpdated' => $wasCustomerUpdated,
            'version' => $shouldRemove ? null : $customer?->pcp_tag_version,
            'shouldRemove' => $shouldRemove,
            'retainedExistingTag' => $retainedExistingTag,
        ];
    }

    private function logUpdateResults(array $leadResult, array $customerResult, bool $shouldRemove = false): void
    {
        if (! $leadResult['wasUpdated'] && ! $shouldRemove) {
            LoggerService::warning('PC qualified tag already applied on lead.', extra: [
                'applied_tag_version' => $leadResult['version'],
            ]);
        }

        $customer = $customerResult['customer'];
        if ($shouldRemove) {
            if ($customer && $customerResult['wasUpdated']) {
                LoggerService::info('PCP tag removed from customer record.', extra: [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                    'email' => $customer->email,
                ]);
            } elseif ($customer && ($customerResult['retainedExistingTag'] ?? false)) {
                LoggerService::info('Skipped removing PCP tag because active qualified leads still exist for customer.', extra: [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                    'email' => $customer->email,
                ]);
            } elseif ($customer && ! $customerResult['wasUpdated']) {
                LoggerService::warning('PCP tag already removed on customer.', extra: [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                    'email' => $customer->email,
                ]);
            }

            return;
        }

        if ($customer && ! $customerResult['wasUpdated']) {
            LoggerService::warning('PCP tag already applied on customer.', extra: [
                'customer_id' => $customer->id,
                'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                'email' => $customer->email,
                'applied_tag_version' => $customerResult['version'],
            ]);
        }
    }

    private function customerHasActiveQualifiedQuotes(int $customerId): bool
    {
        return PersonalQuote::where('customer_id', $customerId)
            ->where('pc_qualified', true)
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->whereNotNull('policy_expiry_date')
            ->where('policy_expiry_date', '>', now())
            ->exists();
    }

    private function getCachedTableColumns(string $modelClass, string $table): array
    {
        if (! isset($this->columnsCache[$modelClass])) {
            try {
                $this->columnsCache[$modelClass] = Schema::getColumnListing($table);
            } catch (\Exception $e) {
                $this->columnsCache[$modelClass] = [];
            }
        }

        return $this->columnsCache[$modelClass];
    }

    private function buildConfigWhereClause($configs, $tableColumns, $model)
    {
        return function ($outerQuery) use ($configs, $tableColumns, $model) {
            foreach ($configs as $config) {
                $field = trim($config->field_name);
                $isInMainTable = in_array($field, $tableColumns);

                if ($field === 'sub_area_id') {
                    $this->handleSubAreaIdCondition($outerQuery, $config);

                    continue;
                }

                if (! $isInMainTable) {
                    continue;
                }

                $this->handleMainTableCondition($outerQuery, $config, $model, $tableColumns);
            }
        };
    }

    private function handleSubAreaIdCondition($outerQuery, $config)
    {
        $operator = strtolower(trim($config->operator));
        if (empty($operator)) {
            return;
        }

        $value = trim($config->value);
        $values = array_map('trim', explode(',', $value));

        $outerQuery->orWhere(function ($q) use ($values, $operator) {
            $q->whereHas('homeQuote', function ($query) use ($values, $operator) {
                $this->applyOperatorCondition($query, 'sub_area_id', $operator, $values);
            });
        });
    }

    private function handleMainTableCondition($outerQuery, $config, $model, $tableColumns)
    {
        $field = trim($config->field_name);
        $operator = strtolower(trim($config->operator));
        $value = trim($config->value);
        $currency_type_id = trim($config->currency_type_id);
        $values = array_map('trim', explode(',', $value));
        $hasSumInsuredCurrency = in_array('policy_sum_assured_currency_id', $tableColumns);

        $outerQuery->orWhere(function ($q) use ($field, $operator, $value, $values, $currency_type_id, $model, $hasSumInsuredCurrency) {
            $this->applyOperatorCondition($q, $field, $operator, $values, $value);

            if ($hasSumInsuredCurrency && ! is_null($model->policy_sum_assured_currency_id) && ! empty($model->policy_sum_assured_currency_id)) {
                $q->where('policy_sum_assured_currency_id', $currency_type_id);
            }
        });
    }

    private function applyOperatorCondition($query, $field, $operator, $values, $value = null)
    {
        match ($operator) {
            self::OPERATOR_IN => $query->whereIn($field, $values),
            self::OPERATOR_NOT_IN => $query->whereNotIn($field, $values),
            self::OPERATOR_BETWEEN => count($values) === 2 ? $query->whereBetween($field, $values) : null,
            self::OPERATOR_NOT_BETWEEN => count($values) === 2 ? $query->whereNotBetween($field, $values) : null,
            self::OPERATOR_LIKE => $query->where($field, 'like', "%$value%"),
            self::OPERATOR_NOT_LIKE => $query->where($field, 'not like', "%$value%"),
            self::OPERATOR_IS_NULL => $query->whereNull($field),
            self::OPERATOR_IS_NOT_NULL => $query->whereNotNull($field),
            '=', '!=', '<', '<=', '>', '>=' => $query->where($field, $operator, $value),
            default => null,
        };
    }
}
