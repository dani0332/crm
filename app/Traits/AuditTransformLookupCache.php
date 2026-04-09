<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Request-scoped resolution cache for audit transformation (auditable rows + related FK lookups).
 * Populated during {@see TransformsAuditables::performAuditTransformation} and cleared automatically
 * at the end of each HTTP / console command lifecycle and after each queue job attempt.
 */
final class AuditTransformLookupCache
{
    /**
     * @var array<string, Model|null>
     */
    private static array $auditableByKey = [];

    /**
     * @var array<string, Model|null>
     */
    private static array $relatedByKey = [];

    /**
     * @param  class-string<Model>  $modelName
     */
    public static function rememberAuditable(string $modelName, mixed $auditableId): ?Model
    {
        if ($auditableId === null || $auditableId === '') {
            return null;
        }

        $key = $modelName.'|'.$auditableId;

        if (! array_key_exists($key, self::$auditableByKey)) {
            self::$auditableByKey[$key] = app($modelName)->newQuery()->whereKey($auditableId)->first();
        }

        return self::$auditableByKey[$key];
    }

    /**
     * @param  class-string<Model>  $relatedModelClass
     */
    public static function rememberRelated(string $relatedModelClass, mixed $id): ?Model
    {
        if ($id === null || $id === '' || ! class_exists($relatedModelClass)) {
            return null;
        }

        $key = $relatedModelClass.'|'.$id;

        if (! array_key_exists($key, self::$relatedByKey)) {
            self::$relatedByKey[$key] = $relatedModelClass::query()->find($id);
        }

        return self::$relatedByKey[$key];
    }

    /**
     * Clears in-memory lookup caches. Invoked automatically between request/job lifecycles;
     * may also be called from tests or long-running loops when data may change mid-run.
     */
    public static function flush(): void
    {
        self::$auditableByKey = [];
        self::$relatedByKey = [];
    }
}
