<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\PrivateClientConfig;
use App\Services\Logger\LoggerService;
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

    /**
     * Apply PCP conditions and update pcp_tag on customer profile.
     */
    public function applyPcpTag(string $leadUuid, int $quoteTypeId): bool
    {
        // Early validation
        $configs = $this->getActivePcpConfigs($quoteTypeId);
        if ($configs->isEmpty()) {
            LoggerService::warning('No active PCP configurations found.', extra: [
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

        // Check if lead matches PCP criteria
        if (! $this->doesLeadMatchPcpCriteria($model, $configs, $modelClass)) {
            LoggerService::warning('Lead not matched PCP criteria.', extra: [
                'tag_version_criteria' => $configs->toArray(),
            ]);

            return false;
        }

        // Apply PCP tags
        return $this->applyPcpTagsToLeadAndCustomer($model, $configs);
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

    private function doesLeadMatchPcpCriteria($model, $configs, string $modelClass): bool
    {
        $tableColumns = $this->getCachedTableColumns($modelClass, $model->getTable());
        $whereClause = $this->buildConfigWhereClause($configs, $tableColumns, $model);

        dd((new $modelClass)->where('uuid', $model->uuid)
            ->where($whereClause)
            ->toRawSql());

        return (new $modelClass)->where('uuid', $model->uuid)
            ->where($whereClause)
            ->exists();
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

    private function applyPcpTagsToLeadAndCustomer($model, $configs): bool
    {
        try {
            return DB::transaction(function () use ($configs, $model) {
                $pcpTagVersion = $configs->first()->version;
                $updateResults = $this->updateLeadAndPersonalQuote($model, $pcpTagVersion);
                $customerUpdateResult = $this->updateCustomer($model, $pcpTagVersion);

                $this->logUpdateResults($updateResults, $customerUpdateResult, $configs);

                return true;
            });
        } catch (Exception $ex) {
            LoggerService::error('Error applying PCP tag.', exception: $ex);

            return false;
        }
    }

    private function updateLeadAndPersonalQuote($model, int $pcpTagVersion): array
    {
        $wasLeadUpdated = false;

        if (is_null($model->pc_qualified)) {
            $updateData = ['pc_qualified' => 1, 'pcp_tag_version' => $pcpTagVersion];

            $model->update($updateData);
            PersonalQuote::where('uuid', $model->uuid)->update($updateData);

            $wasLeadUpdated = true;
            LoggerService::info('PC qualified tag applied successfully on lead.');
        }

        return ['wasUpdated' => $wasLeadUpdated, 'version' => $model->pcp_tag_version];
    }

    private function updateCustomer($model, int $pcpTagVersion): array
    {
        $customer = Customer::where('id', $model->customer_id)->first();
        $wasCustomerUpdated = false;

        if ($customer && ! $customer->pcp_tag) {
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

        return [
            'customer' => $customer,
            'wasUpdated' => $wasCustomerUpdated,
            'version' => $customer?->pcp_tag_version,
        ];
    }

    private function logUpdateResults(array $leadResult, array $customerResult, $configs): void
    {
        if (! $leadResult['wasUpdated']) {
            LoggerService::warning('PC qualified tag already applied on lead.', extra: [
                'applied_tag_version' => $leadResult['version'],
            ]);
        }

        $customer = $customerResult['customer'];
        if ($customer && ! $customerResult['wasUpdated']) {
            LoggerService::warning('PCP tag already applied on customer.', extra: [
                'customer_id' => $customer->id,
                'customer_name' => trim($customer->first_name.' '.$customer->last_name),
                'email' => $customer->email,
                'applied_tag_version' => $customerResult['version'],
            ]);
        }
    }

    private function getActivePcpConfigs(int $quoteTypeId)
    {
        return PrivateClientConfig::where([
            'status' => true,
            'quote_type_id' => $quoteTypeId,
            'active_version' => true,
        ])->whereNotNull('value')->get();
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

                // Check if field exists in main table
                $isInMainTable = in_array($field, $tableColumns);

                // Static handling for sub_area_id
                if ($field === 'sub_area_id') {
                    $operator = strtolower(trim($config->operator));
                    $value = trim($config->value);
                    $values = array_map('trim', explode(',', $value));

                    $outerQuery->orWhere(function ($q) use ($values, $operator) {
                        $q->whereHas('homeQuote', function ($query) use ($values, $operator) {
                            match ($operator) {
                                self::OPERATOR_IN => $query->whereIn('sub_area_id', $values),
                                self::OPERATOR_NOT_IN => $query->whereNotIn('sub_area_id', $values),
                                self::OPERATOR_BETWEEN => count($values) === 2 ? $query->whereBetween('sub_area_id', $values) : null,
                                self::OPERATOR_NOT_BETWEEN => count($values) === 2 ? $query->whereNotBetween('sub_area_id', $values) : null,
                                self::OPERATOR_LIKE => $query->where('sub_area_id', 'like', "%{$values[0]}%"),
                                self::OPERATOR_NOT_LIKE => $query->where('sub_area_id', 'not like', "%{$values[0]}%"),
                                self::OPERATOR_IS_NULL => $query->whereNull('sub_area_id'),
                                self::OPERATOR_IS_NOT_NULL => $query->whereNotNull('sub_area_id'),
                                '=', '!=', '<', '<=', '>', '>=' => $query->where('sub_area_id', $operator, $values[0]),
                                default => null,
                            };
                        });
                    });

                    continue;
                }

                if (! $isInMainTable) {
                    continue;
                }

                $hasSumInsuredCurrency = in_array('sum_insured_currency_id', $tableColumns);
                $operator = strtolower(trim($config->operator));
                $value = trim($config->value);
                $currency_type_id = trim($config->currency_type_id);
                $values = array_map('trim', explode(',', $value));

                $outerQuery->orWhere(function ($q) use ($field, $operator, $value, $values, $currency_type_id, $model, $hasSumInsuredCurrency, $isInMainTable) {
                    if ($isInMainTable) {
                        match ($operator) {
                            self::OPERATOR_IN => $q->whereIn($field, $values),
                            self::OPERATOR_NOT_IN => $q->whereNotIn($field, $values),
                            self::OPERATOR_BETWEEN => count($values) === 2 ? $q->whereBetween($field, $values) : null,
                            self::OPERATOR_NOT_BETWEEN => count($values) === 2 ? $q->whereNotBetween($field, $values) : null,
                            self::OPERATOR_LIKE => $q->where($field, 'like', "%$value%"),
                            self::OPERATOR_NOT_LIKE => $q->where($field, 'not like', "%$value%"),
                            self::OPERATOR_IS_NULL => $q->whereNull($field),
                            self::OPERATOR_IS_NOT_NULL => $q->whereNotNull($field),
                            '=', '!=', '<', '<=', '>', '>=' => $q->where($field, $operator, $value),
                            default => null,
                        };
                    }

                    if ($hasSumInsuredCurrency && ! is_null($model->sum_insured_currency_id) && ! empty($model->sum_insured_currency_id)) {
                        $q->where('sum_insured_currency_id', $currency_type_id);
                    }
                });
            }
        };
    }
}
