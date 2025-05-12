<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\PrivateClientConfig;
use Exception;
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
            try {
                $model->update(['pc_qualified' => 1, 'pcp_tag_version' => $configs->first()->version]);
                $customer = Customer::where([
                    'id' => $model->customer_id,
                ])->first();
                if ($customer && $customer->pcp_tag != 1) {
                    $customer->update(['pcp_tag' => 1, 'pcp_tag_version' => $configs->first()->version]);

                    return 'PCP tag applied successfully.';
                }

                return 'PCP tag already applied for this lead.';
            } catch (Exception $ex) {
                throw $ex;
            }
        }

        return 'Lead not matched PCP criteria.';
    }

    /**
     * Remove PCP tag from insured table.
     */
    public function removePcpTag()
    {
        $customers = Customer::with('customerInsured')
            ->where('pcp_tag', true)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($customers as $customer) {
            $hasMatchingPolicy = false;
            $checkedAnyValidCustomerInsured = false;
            foreach ($customer->customerInsured as $customerInsured) {
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

                $configs = $this->getActivePcpConfigs($customerInsured->quote_type_id);

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
                }
            }
            // Remove PCP tag if no matching policy was found
            if ($checkedAnyValidCustomerInsured && ! $hasMatchingPolicy) {
                $customer->update(['pcp_tag' => false]);
            }
        }

        return 'PCP tags updated.';
    }

    protected function getActivePcpConfigs(int $quoteTypeId)
    {
        return PrivateClientConfig::where([
            'status' => true,
            'quote_type_id' => $quoteTypeId,
            'active_version' => true,
        ])->whereNotNull('value')->get();
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
