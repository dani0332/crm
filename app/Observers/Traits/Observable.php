<?php

namespace App\Observers\Traits;

use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

trait Observable
{
    protected function getChangeSet(Model $model): array
    {
        $changes = [];

        $ignoreList = ['updated_at', 'created_at', 'deleted_at', 'password'];

        foreach ($model->getDirty() as $attribute => $value) {
            if (in_array($attribute, $ignoreList)) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $model->getOriginal($attribute),
                'new' => $value,
            ];
        }

        return $changes;
    }

    protected function printChangeLog(Model $model)
    {
        $changes = $this->getChangeSet($model);

        $logData = [
            'Model' => get_class($model),
            'Changes' => $changes,
        ];

        if ($model->uuid) {
            $logData['Ref ID'] = $model->uuid;
        }

        LoggerService::info('Observer Change Set', $logData);
    }
}
