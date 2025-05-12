<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\PrivateClientConfig;
use Illuminate\Support\Facades\Schema;

trait PrivateClient
{
    /**
     * Apply PCP conditions and update pcp_tag on insured table.
     */
    public function applyPcpTag(int $leadId, int $quoteTypeId)
    {
        $configs = $this->getActivePcpConfigs($quoteTypeId);
        if ($configs->isEmpty()) {
            return 'No active PCP configurations found for this quote type.';
        }

        $modelClass = QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);
        if (! class_exists($modelClass)) {
            return 'Model class not found for quote type.';
        }

        $model = (new $modelClass)->find($leadId);
        if (! $model) {
            return 'Lead not found.';
        }

        $tableColumns = $this->getCachedTableColumns($modelClass, $model->getTable());
        $hasSumInsuredCurrency = in_array('sum_insured_currency_id', $tableColumns);

        $exists = (new $modelClass)
            ->where('id', $leadId)
            ->where($this->buildConfigWhereClause($configs, $tableColumns, $hasSumInsuredCurrency, $model))
            ->exists();

        if ($exists) {
            $customerInsured = CustomerInsured::where([
                'quote_request_id' => $leadId,
                'quote_type_id' => $quoteTypeId,
            ])->first();
            if ($customerInsured) {
                $insured = Insured::find($customerInsured->insured_id);
                if ($insured && $insured->pcp_tag != 1) {
                    $insured->ref_id = $model->code;
                    $insured->update(['pcp_tag' => 1]);
                }

                return 'PCP tag applied successfully.';
            }

            return 'No customer insured found for this lead.';
        }

        return 'Lead not matched PCP criteria.';
    }

    /**
     * Remove PCP tag from insured table.
     */
    public function removePcpTag()
    {
        $insureds = Insured::with([
            'customerInsured' => function ($query) {
                $query->whereHas('pcpConfig')->with('pcpConfig');
            },
        ])
            ->where('pcp_tag', true)
            ->whereHas('customerInsured.pcpConfig')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($insureds as $insured) {
            $hasMatchingPolicy = false;
            $checkedAnyValidCustomerInsured = false;
            foreach ($insured->customerInsured as $customerInsured) {
                $quoteTypeId = $customerInsured->quote_type_id;
                if (
                    $quoteTypeId == QuoteTypes::YACHT->id() || $quoteTypeId == QuoteTypes::JETSKI->id() || $quoteTypeId == QuoteTypes::CYCLE->id()
                    || $quoteTypeId == QuoteTypes::BIKE->id() || $quoteTypeId == QuoteTypes::PET->id()
                ) {
                    $quoteTypeId = QuoteTypes::PERSONAL->id();
                }

                $modelClass = QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);

                $query = $modelClass::where('id', $customerInsured->quote_request_id);
                $model = $query->first();

                if (! $model) {
                    continue; // Try next customerInsured
                }

                $configs = PrivateClientConfig::where([
                    'status' => true,
                    'quote_type_id' => $customerInsured->quote_type_id,
                ])->orderBy('id', 'asc')->get();

                $checkedAnyValidCustomerInsured = true;
                $columns = Schema::getColumnListing($model->getTable());
                $hasSumInsuredCurrency = in_array('sum_insured_currency_id', $columns);

                $query->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                    ->whereNotNull('policy_expiry_date')
                    ->where('policy_expiry_date', '>', now())->where(function ($outerQuery) use ($configs, $model, $hasSumInsuredCurrency) {
                        foreach ($configs as $config) {
                            $field = trim($config->field_name);
                            $operator = strtolower(trim($config->operator));
                            $value = trim($config->value);
                            $currency_type_id = trim($config->currency_type_id);
                            $values = array_map('trim', explode(',', $value));

                            $outerQuery->orWhere(function ($q) use ($field, $operator, $value, $values, $model, $hasSumInsuredCurrency, $currency_type_id) {
                                match ($operator) {
                                    'in' => $q->whereIn($field, $values),
                                    'not in' => $q->whereNotIn($field, $values),
                                    'between' => count($values) === 2 ? $q->whereBetween($field, $values) : null,
                                    'not between' => count($values) === 2 ? $q->whereNotBetween($field, $values) : null,
                                    'like' => $q->where($field, 'like', "%$value%"),
                                    'not like' => $q->where($field, 'not like', "%$value%"),
                                    'is null' => $q->whereNull($field),
                                    'is not null' => $q->whereNotNull($field),
                                    '=', '!=', '<', '<=', '>', '>=' => $q->where($field, $operator, $value),
                                    default => null,
                                };

                                if ($hasSumInsuredCurrency && isset($model->sum_insured_currency_id)) {
                                    $q->where('sum_insured_currency_id', $currency_type_id);
                                }
                            });
                        }
                    });

                if ($query->exists()) {
                    $hasMatchingPolicy = true;
                    break; // No need to check other customerInsured
                } else {
                    $querytest[$customerInsured->insured_id][] = $query->toRawSql();
                }
            }
            // Remove PCP tag if no matching policy was found
            if ($checkedAnyValidCustomerInsured && ! $hasMatchingPolicy) {
                $insured->update(['pcp_tag' => false]);
            }
        }

        return 'PCP tags updated.';
    }

    protected function getActivePcpConfigs(int $quoteTypeId)
    {
        return PrivateClientConfig::where([
            'status' => true,
            'quote_type_id' => $quoteTypeId,
        ])->orderBy('id', 'asc')->get();
    }

    protected function getCachedTableColumns(string $modelClass, string $table): array
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

    protected function buildConfigWhereClause($configs, $tableColumns, $hasSumInsuredCurrency, $model)
    {
        return function ($outerQuery) use ($configs, $tableColumns, $hasSumInsuredCurrency, $model) {
            foreach ($configs as $config) {
                $field = trim($config->field_name);
                if (! in_array($field, $tableColumns)) {
                    continue;
                }

                $operator = strtolower(trim($config->operator));
                $value = trim($config->value);
                $currency_type_id = trim($config->currency_type_id);
                $values = array_map('trim', explode(',', $value));

                $outerQuery->orWhere(function ($q) use ($field, $operator, $value, $values, $hasSumInsuredCurrency, $currency_type_id, $model) {
                    match ($operator) {
                        'in' => $q->whereIn($field, $values),
                        'not in' => $q->whereNotIn($field, $values),
                        'between' => count($values) === 2 ? $q->whereBetween($field, $values) : null,
                        'not between' => count($values) === 2 ? $q->whereNotBetween($field, $values) : null,
                        'like' => $q->where($field, 'like', "%$value%"),
                        'not like' => $q->where($field, 'not like', "%$value%"),
                        'is null' => $q->whereNull($field),
                        'is not null' => $q->whereNotNull($field),
                        '=', '!=', '<', '<=', '>', '>=' => $q->where($field, $operator, $value),
                        default => null,
                    };

                    if ($hasSumInsuredCurrency && isset($model->sum_insured_currency_id)) {
                        $q->where('sum_insured_currency_id', $currency_type_id);
                    }
                });
            }
        };
    }
}
