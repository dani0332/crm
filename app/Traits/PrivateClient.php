<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\Insured;
use App\Models\PrivateClientConfig;
use Exception;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;

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
        $customers = Customer::where('pcp_tag', true)->get();
        $updateCustomers = [];

        foreach ($customers as $customer) {
            $activeQualifiedLeadsCount = 0;

            $personalQuoteCount = $customer->personalQuote()
                ->where('pc_qualified', true)
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->count();

            $activeQualifiedLeadsCount += $personalQuoteCount;

            if ($activeQualifiedLeadsCount === 0) {
                $updateCustomers[] = $customer->id;
            }
        }
        if (count($updateCustomers) > 0) {
            Customer::whereIn('id', $updateCustomers)->update(['pcp_tag' => false]);

            return 'PCP tag updated. '.implode(', ', $updateCustomers).' customer(s) had their PCP tag removed.';
        } else {
            return 'No customer found that had PCP Tag';
        }
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
