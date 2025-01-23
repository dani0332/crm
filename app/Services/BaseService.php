<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\GenericModel;
use App\Models\QuoteViewCount;
use App\Models\User;
use Carbon\Carbon;
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
        $this->genericModel = $this->fillModel(new GenericModel, $type);

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
        return (new DropdownSourceService)->getDropdownSource($key, $quoteTypeId);
    }

    public function quoteDocumentEnabled($type)
    {
        return (new QuoteDocumentService)->isEnabled($type);
    }

    public function getQuoteDocuments($quoteId, $type)
    {
        return (new QuoteDocumentService)->getQuoteDocuments($quoteId, $type);
    }

    public function displaySendPolicyButton($record, $quoteDocuments, $quoteTypeId)
    {
        return (new QuoteDocumentService)->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);
    }

    public function getQuoteDocumentsForUpload($type)
    {
        return (new QuoteDocumentService)->getQuoteDocumentsForUpload($type);
    }

    public function getEmailStatus($typeId, $quoteId)
    {
        return (new EmailStatusService)->getEmailStatus($typeId, $quoteId);
    }

    public function getAdditionalContacts($customerId, $mobileNo)
    {
        return (new CustomerService)->getAdditionalContacts($customerId, $mobileNo);
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
                'quote_status_id' => $activity->quote_status_id,
                'quote_status' => $activity?->quoteStatus,
                'user_id' => $activity?->user_id,
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

    public function updateAllocationCountsForNewAdvisor($advisorAllocationRecord, $lead, $systemAssignedTypes, bool $isBuyLead = false)
    {
        if ($advisorAllocationRecord === null || $lead === null) {
            return;
        }

        // Determine if the lead was system-assigned or manually assigned
        $isSystemAssigned = in_array($lead->assignment_type, $systemAssignedTypes);

        $advisorAllocationRecord->adjustAssignmentCounts($isBuyLead, $isSystemAssigned);
    }

    public function upsertManualAllocationCount($newAdvisorId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $quoteTypeId)
    {
        // Check if $lead or $newAdvisorId is not provided
        if ($lead === null || $newAdvisorId === null) {
            return;
        }

        info('Previous assignment type is : '.$previousAssignmentType);
        // Constants for system assigned types
        $systemAssignedTypes = [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED];

        // Get the allocation record for the new advisor
        $newAdvisorAllocationRecord = app(LeadAllocationService::class)->getLeadAllocationRecordByUserId($newAdvisorId, $quoteTypeId);

        // Update allocation counts for the new advisor only if its different from previous advisor

        if ($newAdvisorId !== $previousAdvisorId) {
            // Update allocation counts for the new advisor (if applicable)
            $this->updateAllocationCountsForNewAdvisor($newAdvisorAllocationRecord, $lead, $systemAssignedTypes);
        }

        // Get the allocation record for the previous advisor (if applicable)
        if ($previousAdvisorId !== null) {
            $previousAdvisorAllocationRecord = app(LeadAllocationService::class)->getLeadAllocationRecordByUserId($previousAdvisorId, $quoteTypeId);

            // Update allocation counts for the previous advisor (if applicable)
            $this->updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes);
        }
    }

    public function updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes)
    {
        // Check if there is a previous advisor and the lead assignment date is today
        if ($previousAdvisorId !== null && Carbon::parse($oldAdvisorAssignedDate)->startOfDay() == now()->startOfDay() && $previousAdvisorAllocationRecord !== null) {
            // if someone's bought lead is re assigning then mark his buy_leas_status to disabled
            if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])) {
                $previousAdvisorAllocationRecord->buy_lead_status = false;
            }

            // Determine if the previous assignment was system-assigned
            $isSystemAssigned = in_array($previousAssignmentType, $systemAssignedTypes);

            // Update allocation counts based on assignment type (if applicable)
            if ($isSystemAssigned && $previousAdvisorAllocationRecord->auto_assignment_count > 0) {
                info('About to deduct from auto assignment count for previous advisor');
                $previousAdvisorAllocationRecord->auto_assignment_count = $previousAdvisorAllocationRecord->auto_assignment_count - 1;
            } elseif (! $isSystemAssigned && $previousAdvisorAllocationRecord->manual_assignment_count > 0) {
                info('About to deduct from manual assignment count for previous advisor');
                $previousAdvisorAllocationRecord->manual_assignment_count = $previousAdvisorAllocationRecord->manual_assignment_count - 1;
            }

            // Decrement the total allocation count (if it's greater than 0) and update timestamps
            if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])) {
                if ($previousAdvisorAllocationRecord->buy_lead_allocation_count > 0) {
                    $previousAdvisorAllocationRecord->buy_lead_allocation_count = $previousAdvisorAllocationRecord->buy_lead_allocation_count - 1;
                    $previousAdvisorAllocationRecord->updated_at = now();
                }
            } elseif ($previousAdvisorAllocationRecord->allocation_count > 0) {
                $previousAdvisorAllocationRecord->allocation_count = $previousAdvisorAllocationRecord->allocation_count - 1;
                $previousAdvisorAllocationRecord->updated_at = now();
            }

            // Save the updated allocation record
            $previousAdvisorAllocationRecord->save();
        }
    }

    public function upsertQuoteDetail($leadId, $quoteModel, $keyColumn)
    {
        return $quoteModel::updateOrCreate(
            [$keyColumn => $leadId],
            [
                'advisor_assigned_date' => now(),
                'advisor_assigned_by_id' => Auth::id(),
            ]
        );
    }

    public function updateDetailRecord($id, $detailModel, string $foreignKey)
    {
        $childRecord = $detailModel::where($foreignKey, $id)->first();
        $oldAdvisorAssignedDate = $childRecord?->advisor_assigned_date ?? null;

        $this->upsertQuoteDetail($id, $detailModel, $foreignKey);

        return $oldAdvisorAssignedDate;
    }

    public function adjustAssignmentType($lead, $userId, $quoteBatch)
    {
        $oldAssignmentType = $lead->assignment_type;
        $isReassignment = $lead->advisor_id != null ? true : false;
        $previousAdvisorId = $lead->advisor_id;

        $lead->advisor_id = $userId;
        $lead->assignment_type = $isReassignment ? AssignmentTypeEnum::MANUAL_REASSIGNED : AssignmentTypeEnum::MANUAL_ASSIGNED;
        $lead->quote_batch_id = $quoteBatch->id;
        $lead->save();

        return [$oldAssignmentType, $previousAdvisorId];
    }

    public function handleAssignment($lead, $userId, $quoteBatch, QuoteTypes $quoteType, $detailModel, $foreignKey)
    {
        [$oldAssignmentType, $previousAdvisorId] = $this->adjustAssignmentType($lead, $userId, $quoteBatch);

        $oldAdvisorAssignedDate = $this->updateDetailRecord($lead->id, $detailModel, $foreignKey);

        info("Manual assignment done for lead : {$lead->uuid} and old advisor assigned date is : {$oldAdvisorAssignedDate}");

        $this->upsertManualAllocationCount($lead->advisor_id, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $oldAssignmentType, $quoteType->id());

        $this->addOrUpdateQuoteViewCount($lead, $quoteType->id(), $userId);

        $lead->save();
    }
}
