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

    private function applyPcpTagsToLeadAndCustomer($model, $pcpTagVersion): bool
    {
        try {
            return DB::transaction(function () use ($pcpTagVersion, $model) {
                LoggerService::info('Applying PCP tag to lead and customer.', extra: [
                    'leadUuid' => $model->uuid,
                    'pcpTagVersion' => $pcpTagVersion,
                ]);
                $updateResults = $this->updateLeadAndPersonalQuote($model, $pcpTagVersion);

                $customerUpdateResult = $this->updateCustomer($model, $pcpTagVersion);

                $this->logUpdateResults($updateResults, $customerUpdateResult);

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

    private function logUpdateResults(array $leadResult, array $customerResult): void
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
