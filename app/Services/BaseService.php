<?php

namespace App\Services;

use App\Models\GenericModel;
use App\Enums\QuoteStatusEnum;
use App\Services\CustomerService;
use App\Services\EmailStatusService;
use App\Services\QuoteDocumentService;
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

    public function quoteDocumentEnabled($type)
    {
        return (new QuoteDocumentService())->isEnabled($type);
    }

    public function getQuoteDocuments($quoteId, $type)
    {
        return (new QuoteDocumentService())->getQuoteDocuments($quoteId, $type);
    }

    public function displaySendPolicyButton($record, $quoteDocuments, $quoteTypeId)
    {
        return (new QuoteDocumentService())->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);
    }

    public function getQuoteDocumentsForUpload($type)
    {
        return (new QuoteDocumentService())->getQuoteDocumentsForUpload($type);
    }

    public function getEmailStatus($typeId, $quoteId)
    {
        return (new EmailStatusService())->getEmailStatus($typeId, $quoteId);
    }

    public function getAdditionalContacts($customerId, $mobileNo)
    {
     return (new CustomerService())->getAdditionalContacts($customerId, $mobileNo);
    }
}
