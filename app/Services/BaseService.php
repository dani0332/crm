<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\GenericModel;
use App\Models\QuoteViewCount;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaseService
{
    protected $genericModel;

    public function __construct()
    {
        $this->genericModel = $this->getGenericModel();
    }

    /**
     * @param  mixed  $type
     */
    public function getGenericModel($type = null): GenericModel
    {
        $type = $type ?? 'GenericModel';
        $this->genericModel = $this->fillModel(new GenericModel(), $type);

        return $this->genericModel;
    }

    /**
     * @param  GenericModel  $model
     * @param  mixed  $type
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

    public function getSkipProperties(string $fieldName, $skipType = false): array
    {
        if (! $skipType) {
            return data_get($this->genericModel, $fieldName, []);
        }
        $skipped = explode(',', data_get($this->genericModel, $fieldName.'.'.$skipType, ''));
        $skipped = array_map('trim', $skipped);
        $fields = [];
        $properties = $this->genericModel->properties;
        foreach ($properties as $key => $property) {
            if (! in_array($key, $skipped)) {
                $fields[$key] = $property;
            }
        }

        return $fields;
    }

    public function fieldsToDisplay($fieldsToDisplay, $quote)
    {
        $crudService = app(CrudService::class);
        $fields = [];

        foreach ($fieldsToDisplay as $property => $field) {
            if (str_contains($field, 'static')) {
                $options = $this->getStaticFields($field);
                $fields[$property]['title'] = ucwords(str_replace('_', ' ', $property));
                $fields[$property]['value'] = '';
                foreach ($options as $option) {
                    if ($property == 'is_smoker') {
                        $fields[$property]['value'] = $quote->$property == 1 ? 'Yes' : 'No';
                    } elseif ($option['text'] == $quote->$property) {
                        $fields[$property]['value'] = $option['text'];
                    } elseif ($property == 'is_ecommerce') {
                        $fields[$property]['value'] = $quote->$property == 1 ? 'Yes' : 'No';
                    }
                }
            } elseif (str_contains($field, 'select')) {
                $fields[$property]['title'] = $crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
                $name = $property.'_text';
                $fields[$property]['value'] = $quote->$name ?? '';
            } elseif (str_contains($field, 'title')) {
                $fields[$property]['title'] = $crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
                $fields[$property]['value'] = $quote->$property ?? '';
            } else {
                $fields[$property]['title'] = ucwords(str_replace('_', ' ', $property));
                $fields[$property]['value'] = $quote->$property ?? '';
            }
        }

        return $fields;
    }

    public function getStaticFields($field, $property = null)
    {
        if (! is_array($field)) {
            $field = explode('|', $field);
        }

        $options = array_filter($field, function ($item) {
            return str_contains($item, ',');
        });
        $options = array_map(function ($item) {
            return explode(',', $item);
        }, $options);
        $options = Arr::first($options);
        $options = array_map(function ($item) {
            return ['id' => $item, 'text' => $item];
        }, $options);

        if ($property == 'is_smoker') {
            $options = [
                ['id' => 1, 'text' => 'Yes'],
                ['id' => 0, 'text' => 'No'],
            ];
        }

        return $options;
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
        $activitiesData = (app(ActivitiesService::class))->getActivityByLeadId($id, $type);
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
                'is_cold' => $activity->is_cold,
            ];
            array_push($activities, $updatedActivity);
        }

        return $activities;
    }

    public function getRenewalAdvisors()
    {
        $crudService = app()->make(CRUDService::class);
        $renewalAdvisors = [];
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $renewalAdvisors = $crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $renewalAdvisors = $crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }

        return $renewalAdvisors;
    }

    public function fillData()
    {
        $crudService = app()->make(CRUDService::class);
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            return $crudService->fillRenewalData($this->genericModel);
        }

        if (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            return $crudService->fillNewBusinessData($this->genericModel);
        }
    }

    public function sortMetaArray($sourceArray, $token)
    {
        $sorted = [];
        foreach ($sourceArray as $key => $value) {
            if (preg_match('/'.$token.'(\d+)/', $value, $matches)) {
                $sorted[$key] = $matches[1];
            } else {
                $sorted[$key] = PHP_INT_MAX;
            }
        }
        asort($sorted);
        $result = [];
        foreach ($sorted as $key => $value) {
            $result[$key] = $sourceArray[$key];
        }

        return $result;
    }

    public function updatePaymentStatus($quote)
    {
        if (
            $quote->quote_status_id == QuoteStatusEnum::TransactionApproved &&
            ($quote->payment_status_id == PaymentStatusEnum::DRAFT || $quote->payment_status_id == null)
        ) {

            $quote->payment_status_id = PaymentStatusEnum::CAPTURED;
            $quote->payment_status_date = now();
            $quote->paid_at = now();
            $quote->save();
        }

    }

    public function addOrUpdateQuoteViewCount($record, $quoteTypeId, $userId = null)
    {
        $userId = $userId ?: Auth::user()->id;

        if ($record->advisor_id != null && $record->advisor_id == $userId) {
            $quoteViewCount = QuoteViewCount::updateOrCreate(
                ['quote_id' => $record->id, 'user_id' => $userId, 'quote_type_id' => $quoteTypeId],
                [],
            );

            if ($quoteViewCount->wasRecentlyCreated) {
                $quoteViewCount->visit_count = 1;
                $quoteViewCount->save();
            } else {
                $quoteViewCount->increment('visit_count');
            }
        }
    }
}
