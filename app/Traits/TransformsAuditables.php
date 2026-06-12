<?php

namespace App\Traits;

use App\Enums\RolesEnum;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;

trait TransformsAuditables
{
    public function transformAuditables($data): array
    {
        $data = $this->performAuditTransformation($data);

        return $this->customizeAudit($data);
    }

    protected function performAuditTransformation($data): array
    {
        if (! isset($this->auditRelationMap)) {
            return $data;
        }

        $audit = &$data['audit'];
        $transformedOld = &$data['transformedOld'];
        $transformedNew = &$data['transformedNew'];
        $auditableId = $audit->auditable_id;

        $apiModelMap = [
            'App\Models\HealthQuoteRequestMemberDetails' => CustomerMembers::class,
            'App\Models\HealthQuoteRequest' => HealthQuote::class,
        ];
        $modelName = $apiModelMap[$audit->auditable_type] ?? $audit->auditable_type;

        $model = AuditTransformLookupCache::rememberAuditable($modelName, $auditableId);

        $relationMap = $this->auditRelationMap ?? [];

        foreach ($relationMap as $foreignKey => $config) {
            $relationName = $config['relation'];
            $fieldName = $config['field'];

            // Resolve the related model class from the Eloquent relation definition
            // so we can look up old and new FK values independently, avoiding the
            // stale-read bug where both old and new would reflect the current DB state.
            $relatedModelClass = null;
            if ($model && method_exists($model, $relationName)) {
                $relatedModelClass = get_class($model->$relationName()->getRelated());
            }

            if (isset($transformedOld[$foreignKey]) && $relatedModelClass) {
                $relatedOld = AuditTransformLookupCache::rememberRelated($relatedModelClass, $transformedOld[$foreignKey]);
                $transformedOld[$relationName] = $relatedOld?->$fieldName ?? null;
            }

            if (isset($transformedNew[$foreignKey]) && $relatedModelClass) {
                $relatedNew = AuditTransformLookupCache::rememberRelated($relatedModelClass, $transformedNew[$foreignKey]);
                $transformedNew[$relationName] = $relatedNew?->$fieldName ?? null;
            }
        }

        if (! auth()->user()?->hasRole(RolesEnum::Engineering)) {
            // Remove raw FK keys (e.g. insurance_class_id) now that they've been resolved to
            // human-readable relation names; keeps the audit diff clean and avoids exposing IDs.
            $foreignKeys = array_keys($relationMap);
            foreach ($foreignKeys as $foreignKey) {
                unset($transformedOld[$foreignKey], $transformedNew[$foreignKey]);
            }

            // Strip null/empty-string entries so the audit diff only surfaces meaningful changes.
            $transformedOld = array_filter($transformedOld, fn ($value) => $value !== null && $value !== '');
        }

        return [
            'audit' => $audit,
            'transformedOld' => $transformedOld,
            'transformedNew' => $transformedNew,
            'model' => $model,
        ];
    }

    private function customizeAudit($data): array
    {
        $apiModelMap = [
            'App\Models\HealthQuoteRequestMemberDetails' => CustomerMembers::class,
            'App\Models\HealthQuoteRequest' => HealthQuote::class,
        ];

        $audit = $data['audit'] ?? null;
        $model = $audit?->auditable_type ?? null;

        if ($model) {
            $apiModel = $apiModelMap[$model] ?? $model;

            if ($apiModel && class_exists($apiModel) && method_exists($apiModel, 'customizeAuditTransformation')) {
                $data = $apiModel::customizeAuditTransformation($data);
            }
        }

        return $data;
    }

    /**
     * Clears in-memory lookup caches (auditable row + related FK). Call between tests or when data may change.
     */
    public static function flushAuditTransformLookupCaches(): void
    {
        AuditTransformLookupCache::flush();
    }
}
