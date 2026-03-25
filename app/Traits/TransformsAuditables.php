<?php

namespace App\Traits;

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

        $relationMap = $this->auditRelationMap ?? [];

        $relationsToLoad = [];
        $fieldsToCheck = array_unique(array_merge(array_keys($transformedOld), array_keys($transformedNew)));

        foreach ($fieldsToCheck as $field) {
            if (isset($relationMap[$field])) {
                $relationsToLoad[] = $relationMap[$field]['relation'];
            }
        }

        $apiModelMap = [
            'App\Models\HealthQuoteRequestMemberDetails' => CustomerMembers::class,
            'App\Models\HealthQuoteRequest' => HealthQuote::class,
        ];
        $modelName = $apiModelMap[$audit->auditable_type] ?? $audit->auditable_type;

        $model = app($modelName)->where('id', $auditableId)
            ->when(! empty($relationsToLoad), fn ($query) => $query->with($relationsToLoad))
            ->first();

        foreach ($relationMap as $foreignKey => $config) {
            $relationName = $config['relation'];
            $fieldName = $config['field'];

            if (isset($transformedOld[$foreignKey]) && $model && $model->$relationName) {
                $transformedOld[$relationName] = $model->$relationName?->$fieldName ?? null;
            }

            if (isset($transformedNew[$foreignKey]) && $model && $model->$relationName) {
                $transformedNew[$relationName] = $model->$relationName?->$fieldName ?? null;
            }
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
}
