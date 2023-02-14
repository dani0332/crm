<?php

namespace App\Services;

use App\Models\GenericModel;
use App\Services\DropdownSourceService;

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


    public function dropdownSource($properties, $quoteTypeId)
    {
        $dropdownSource = [];
        foreach ($properties as $key => $value) {
            $data = (new DropdownSourceService())->getDropdownSource($key, $quoteTypeId);
            if ($data) {
                $dropdownSource[$key] = $data->toArray();
            }
        }
        return $dropdownSource;
    }
}
