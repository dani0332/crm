<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Repositories\UserRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Log;

class CentralService
{
    use GenericQueriesAllLobs;

    public function duplicateAllowedLobsList($quoteType, $leadCode)
    {
        $allowedLeadTypes = [
            quoteTypeCode::Home,
            quoteTypeCode::Health,
            quoteTypeCode::Life,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical,
            quoteTypeCode::Travel,
            quoteTypeCode::Car,
            quoteTypeCode::Pet,
        ];

        if (strtolower($quoteType) == strtolower(quoteTypeCode::Business)) {
            $modelType = quoteTypeCode::CORPLINE;
        }
        $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
            return $item;
        });
        foreach ($allowedLeadTypes as $leadType) {
            $leadType = strtolower($leadType);

            if ($leadType == strtolower(quoteTypeCode::CORPLINE) || $leadType = strtolower(quoteTypeCode::GroupMedical)) {
                $leadType = quoteTypeCode::Business;
            }

            $repository = $this->getRepositoryObject(ucfirst($leadType));

            $duplicateRecord = $repository::where('code', $leadCode)->first();
            if ($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
                    return $item;
                });
            }
        }

        return $allowedLeadTypes;
    }

    public function saveDuplicateLeads($data)
    {
        $lobTeams = $data['lob_team'];
        $parentType = $data['parentType'];
        $entityId = $data['entityId'];

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = quoteTypeCode::Business;
        }

        $repository = $this->getRepositoryObject($parentType);
        $parentRecord = $repository::where('id', $entityId)->first();

        if (! empty($data['lob_team_sub_selection'])) {
            $parentRecord['enquiryType'] = $data['lob_team_sub_selection'];
        } else {
            $parentRecord['enquiryType'] = 'record_only';
        }

        if (! empty($lobTeams)) {
            $dataArr = [
                'firstName' => $parentRecord->first_name,
                'lastName' => $parentRecord->last_name,
                'email' => $parentRecord->email,
                'mobileNo' => $parentRecord->mobile_no,
                'referenceUrl' => config('constants.APP_URL'),
                'source' => config('constants.SOURCE_NAME'),
            ];

            $resp = [];
            foreach ($lobTeams as $lob) {
                if (strtolower($lob) == strtolower(quoteTypeCode::CORPLINE) || strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                    $lob = quoteTypeCode::Business;
                    $dataArr['businessTypeOfInsuranceId'] = $parentRecord->business_type_of_insurance_id ?? '';

                    if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                        $dataArr['businessTypeOfInsuranceId'] = QuoteTypeId::Business;
                    }
                }

                $repository = $this->getRepositoryObject(ucfirst($lob));

                if (! class_exists($repository)) {
                    return false;
                }

                $response = in_array(quoteTypeCode::Pet, newUi()) && method_exists($repository, 'fetchCreateDuplicate') ? $repository::createDuplicate($dataArr) : Capi::request('/api/v1-save-'.strtolower($lob).'-quote', 'post', $dataArr);
                if (isset($response->message) && str_contains($response->message, 'Error')) {
                    $resp['errors'][] = 'Something went wrong while duplicating '.$lob.' quotes';
                } elseif (isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
                    $record = $repository::where('uuid', $response->quoteUID)->first();
                    if ($record) {
                        $update = [
                            'parent_duplicate_quote_id' => $parentRecord->code,
                            'advisor_id' => auth()->user()->id,
                        ];
                        if (strtolower($lob) == strtolower(quoteTypeCode::Health)) {
                            $subTeam = null;
                            if (auth()->user()->subTeam) {
                                $subTeam = auth()->user()->subTeam->name;
                            }
                            $update['health_team_type'] = $subTeam;
                        }
                        $record->update($update);
                    }
                }
            }

            return $resp;
        }
    }

    public function assignLeadToAdvisor($request)
    {
        $personalQuotes = [quoteTypeCode::Bike, quoteTypeCode::Cycle];
        $leadsIds = $request->selectTmLeadId ?? $request->entityId;

        if (str_starts_with($leadsIds, ',')) {
            $leadsIds = substr($leadsIds, 1);
        }

        $leadsIds = array_map('intval', explode(',', $leadsIds));
        $model = (in_array(ucfirst($request->modelType), $personalQuotes) && in_array(ucfirst($request->modelType), newUi())) ?
            PersonalQuote::class : (ucfirst($request->modelType).'Quote');

        if (! class_exists($model)) {
            return ['message' => 'something went wrong'];
        }

        foreach ($leadsIds as $leadId) {
            $getQuoteLead = $model::where('id', $leadId)->first();
            if (isset($getQuoteLead->quote_status_id) && $getQuoteLead->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                return ['message' => 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.'];
            }
        }

        $assignedUser = UserRepository::getUserById( (int) $request->assigned_to_id_new );
        if (! $assignedUser) {
            return ['message' => 'Selected advisor does not exist in the system!'];
        }

        return $this->processLeadAssignment($leadsIds, $assignedUser, $model);
    }

    protected function processLeadAssignment($leadsIds, $advisor, $model)
    {
        Log::info('Leads ids to assign: '.json_encode($leadsIds));
        $message = '';

        foreach ($leadsIds as $leadId) {
            $lead = $model::findOrfail($leadId);
            if ($lead) {
                $lead->advisor_id = $advisor->id;
                $lead->save();
                $this->updateChildRecord($lead->id);
            }else{
                $message .= $message . 'Manual Lead Assignment Failed for '.$model.' , selected id was '.$leadId.' <br>';
                Log::warning('Manual Lead Assignment Failed for '.$model.' , selected id was '. $leadId);
            }
        }

        return ['is_valid_response' => true, 'message' => $message];
    }

    protected function updateChildRecord($id)
    {
        $childRecord = PersonalQuoteDetail::where('personal_quote_id', $id)->first();

        if (empty($childRecord)) {
            PersonalQuoteDetail::create([
                'personal_quote_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $childRecord->advisor_assigned_by_id = auth()->user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
    }
}
