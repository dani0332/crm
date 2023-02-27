<?php

namespace App\Services;

use App\Models\User;
use App\Models\GenericModel;
use App\Enums\QuoteStatusEnum;
use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use App\Services\ActivitiesService;
use App\Services\EmailStatusService;
use Illuminate\Support\Facades\Auth;
use App\Services\QuoteDocumentService;
use App\Services\DropdownSourceService;

class BaseService
{
    protected $genericModel;

    public function __construct()
    {
        $this->genericModel = $this->getGenericModel();
    }

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
        $this->genericModel = $model;
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
            $data = $this->dropdownValues($key, $quoteTypeId);
            if ($data) {
                $dropdownSource[$key] = $data->toArray();
            }
        }
        return $dropdownSource;
    }

    public function dropdownValues($key, $quoteTypeId)
    {
        return (new DropdownSourceService())->getDropdownSource($key, $quoteTypeId);
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


    public function audits($auditableId, $auditableType)
    {
        return DB::table('audits')
        ->select('audits.*', 'users.name')
        ->join('users', 'audits.user_id', 'users.id')
        ->where('auditable_id', $auditableId)
        ->where('auditable_type', $auditableType)
        ->get();
    }


    public function getFieldsToUpdate($skipProperties): array
    {
        return $this->getSkipProperties($skipProperties, 'update');
    }

    public function getFieldsToCreate($skipProperties): array
    {
        return $this->getSkipProperties($skipProperties, 'create');
    }

    public function getSkipProperties(string $fieldName, $skipType = false) : array
    {
        if (!$skipType) {
            return data_get($this->genericModel, $fieldName, []);
        }
        $skipped = explode(",", data_get($this->genericModel, $fieldName.'.' . $skipType, ''));
        $skipped = array_map('trim', $skipped);
        $fields = [];
        $properties = $this->genericModel->properties;
        foreach ($properties as $key => $property) {
            if (!in_array($key, $skipped)) {
                $fields[$key] = $property;
            }
        }
        return $fields;
    }

    public function getFieldsToShow(): array
    {
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            return $this->getSkipProperties('renewalSkipProperties', 'show');
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            return $this->getSkipProperties('newBusinessSkipProperties', 'show');
        }

        return $this->getSkipProperties('skipProperties', 'show');
    }

    public function getActivityByLeadId($id, $type)
    {
        $activitiesData =  (app(ActivitiesService::class))->getActivityByLeadId($id, $type);
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
            ];
            array_push($activities, $updatedActivity);
        }
        return $activities;
    }
}
