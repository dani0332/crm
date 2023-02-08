<?php

namespace App\Services;

use App\Models\GenericModel;

class BaseService
{

    /**
     * @param mixed $type
     * @return GenericModel
     */
    public function getGenericModel($type = null) : GenericModel
    {
        $type = $type ?? 'GenericModel';
        return $this->fillModel(new GenericModel(), $type);
    }

    /**
     * @param GenericModel $model
     * @param mixed $type
     * @return GenericModel
     */
    public function fillModel($model, $type)
    {
        $model->modelType = $type;
        $model->properties = $this->fillModelProperties();
        $model->skipProperties = $this->fillModelSkipProperties();
        $model->searchProperties = $this->fillModelSearchProperties();
        return $model;
    }


    public function fillModelProperties()
    {
        return [];
    }

    public function fillModelSkipProperties()
    {
        return [];
    }

    public function fillModelSearchProperties()
    {
        return [];
    }
}
