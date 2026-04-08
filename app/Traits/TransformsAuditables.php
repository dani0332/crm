<?php

namespace App\Traits;

use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use Illuminate\Database\Eloquent\Model;

trait TransformsAuditables
{
    /**
     * @var array<string, Model|null>
     */
    private static array $auditTransformAuditableByKey = [];

    /**
     * @var array<string, Model|null>
     */
    private static array $auditTransformRelatedByKey = [];

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

        $model = self::auditTransformRememberAuditable($modelName, $auditableId);

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
                $relatedOld = self::auditTransformRememberRelated($relatedModelClass, $transformedOld[$foreignKey]);
                $transformedOld[$relationName] = $relatedOld?->$fieldName ?? null;
            }

            if (isset($transformedNew[$foreignKey]) && $relatedModelClass) {
                $relatedNew = self::auditTransformRememberRelated($relatedModelClass, $transformedNew[$foreignKey]);
                $transformedNew[$relationName] = $relatedNew?->$fieldName ?? null;
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

    /**
     * @param  class-string<Model>  $modelName
     */
    private static function auditTransformRememberAuditable(string $modelName, mixed $auditableId): ?Model
    {
        if ($auditableId === null || $auditableId === '') {
            return null;
        }

        $key = $modelName.'|'.$auditableId;

        if (! array_key_exists($key, self::$auditTransformAuditableByKey)) {
            self::$auditTransformAuditableByKey[$key] = app($modelName)->newQuery()->whereKey($auditableId)->first();
        }

        return self::$auditTransformAuditableByKey[$key];
    }

    /**
     * @param  class-string<Model>  $relatedModelClass
     */
    private static function auditTransformRememberRelated(string $relatedModelClass, mixed $id): ?Model
    {
        if ($id === null || $id === '' || ! class_exists($relatedModelClass)) {
            return null;
        }

        $key = $relatedModelClass.'|'.$id;

        if (! array_key_exists($key, self::$auditTransformRelatedByKey)) {
            self::$auditTransformRelatedByKey[$key] = $relatedModelClass::query()->find($id);
        }

        return self::$auditTransformRelatedByKey[$key];
    }

    /**
     * Clears in-memory lookup caches (auditable row + related FK). Call between tests or when data may change.
     */
    public static function flushAuditTransformLookupCaches(): void
    {
        self::$auditTransformAuditableByKey = [];
        self::$auditTransformRelatedByKey = [];
    }
}
