<?php

namespace App\Traits;

use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\PrivateClientConfig;
use Illuminate\Support\Facades\Schema;

trait PrivateClient
{
    /**
     * Apply PCP conditions and update pcp_tag on insured table.
     *
     * @param  array  $quoteTypeId
     */
    public function applyPcpTag(int $leadId, int $quoteTypeId)
    {
        $configs = PrivateClientConfig::where(['status' => true, 'quote_type_id' => $quoteTypeId])
            ->orderBy('id', 'asc')
            ->get();

        if ($configs->isEmpty()) {
            return 'No active PCP configurations found for this quote type.';
        }

        $modelClass = QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);
        $query = (new $modelClass)->newQuery()->where('id', $leadId);

        $model = $query->first();
        if (! $model) {
            return 'Lead not found.';
        }

        $hasSumInsuredCurrency = false;
        try {
            $columns = Schema::getColumnListing($model->getTable());
            $hasSumInsuredCurrency = in_array('sum_insured_currency_id', $columns);
        } catch (\Exception $e) {
        }

        $exists = $query->where(function ($outerQuery) use ($configs, $model, $hasSumInsuredCurrency) {
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
        })->exists();

        if ($exists) {
            $customerInsured = CustomerInsured::where(['quote_request_id' => $leadId, 'quote_type_id' => $quoteTypeId])->first();
            if ($customerInsured) {
                $insuredId = $customerInsured->insured_id;
                $insured = Insured::find($insuredId);
                if ($insured && $insured->pcp_tag != 1) {
                    $insured->pcp_tag = 1;
                    $insured->save();
                }

                return 'PCP tag applied successfully.';
            }

            return 'No customer insured found for this lead.';
        }

        return 'Lead not matched PCP criteria.';
    }
}
