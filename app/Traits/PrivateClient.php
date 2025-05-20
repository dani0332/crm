<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\PrivateClientConfig;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;

trait PrivateClient
{
    /**
     * Apply PCP conditions and update pcp_tag on insured table.
     */
    public function applyPcpTag(string $leadUuid, int $quoteTypeId)
    {
        $configs = $this->getActivePcpConfigs($quoteTypeId);
        if ($configs->isEmpty()) {
            LoggerService::warning('No active PCP configurations found.', extra: [
                'quoteTypeId' => $quoteTypeId,
            ]);

            return false;
        }

        $modelClass = QuoteTypes::getQuoteTypeIdToClass($quoteTypeId);
        if (! class_exists($modelClass)) {
            LoggerService::warning('Model class not found.', extra: [
                'quoteTypeId' => $quoteTypeId,
            ]);

            return false;
        }

        $model = (new $modelClass)->where('uuid', $leadUuid)->first();
        if (! $model) {
            LoggerService::warning('Lead not found.', extra: [
                'quoteTypeId' => $quoteTypeId,
                'leadId' => $leadUuid,
            ]);

            return false;
        }

        $tableColumns = $this->getCachedTableColumns($modelClass, $model->getTable());
        $hasSumInsuredCurrency = in_array('sum_insured_currency_id', $tableColumns);

        $exists = (new $modelClass)
            ->where('uuid', $leadUuid)
            ->where($this->buildConfigWhereClause($configs, $tableColumns, $hasSumInsuredCurrency, $model))
            ->exists();

        if ($exists) {
            try {
                $pcpTagVersion = $configs->first()->version;
                if (is_null($model->pc_qualified)) {
                    $model->pc_qualified = 1;
                    $model->pcp_tag_version = $pcpTagVersion;
                    $model->save();

                    PersonalQuote::where('uuid', $model->uuid)->update(['pc_qualified' => 1, 'pcp_tag_version' => $pcpTagVersion]);
                    LoggerService::info('PC qualified tag applied successfully on lead.', extra: [
                        'tag_version_criteria' => json_decode($configs),
                    ]);
                } else {
                    LoggerService::info('PC qualified tag already applied on lead.');
                }
                $customer = Customer::where([
                    'id' => $model->customer_id,
                ])->first();
                if ($customer && $customer->pcp_tag != true) {
                    $customer->ref_id = $model->code;
                    $customer->update(['pcp_tag' => true, 'pcp_tag_version' => $pcpTagVersion]);

                    LoggerService::info('PCP tag applied successfully on customer.', extra: [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->first_name.' '.$customer->last_name,
                        'email' => $customer->email,
                        'tag_version_criteria' => json_decode($configs),
                    ]);

                    return true;
                }

                LoggerService::warning('PCP tag already applied on customer.', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->first_name.' '.$customer->last_name,
                    'email' => $customer->email,
                    'tag_version_criteria' => json_decode($configs),
                ]);

                return false;
            } catch (Exception $ex) {
                LoggerService::error('Error applying PCP tag.', exception: $ex);
                throw $ex;
            }
        }

        LoggerService::warning('Lead not matched PCP criteria.', extra: [
            'tag_version_criteria' => json_decode($configs),
        ]);

        return false;
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
        if ($customers->isEmpty()) {
            LoggerService::info('No customers found with PCP tag.');

            return;
        }

        foreach ($customers as $customer) {

            LoggerService::info('Starting to check active qualified leads for customer', extra: [
                'customer_id' => $customer->id,
                'customer_name' => $customer->first_name.' '.$customer->last_name,
                'email' => $customer->email,
            ]);

            $activeQualifiedLeads = $customer->personalQuote()
                ->where('pc_qualified', true)
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->exists();

            if (! $activeQualifiedLeads) {
                LoggerService::info('No active qualified leads found for customer', extra: [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->first_name.' '.$customer->last_name,
                    'email' => $customer->email,
                ]);
                try {
                    $customer->update([
                        'pcp_tag' => false,
                        'updated_at' => now(),
                    ]);

                    LoggerService::info('PCP tag removed successfully from customer', extra: [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->first_name.' '.$customer->last_name,
                        'email' => $customer->email,
                    ]);

                    return true;
                } catch (\Exception $ex) {
                    LoggerService::error('Error removing PCP tag.', exception: $ex);
                    throw $ex;
                }
            }
            LoggerService::info('Active qualified leads found for customer', extra: [
                'customer_id' => $customer->id,
            ]);
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
