<?php

namespace App\Traits;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Facades\Capi;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function fetchDuplicateAllowedLobsList($leadCode)
    {
        $modelType = $this->model();
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

        if (strtolower($modelType) == strtolower(quoteTypeCode::Business)) {
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
            $repository = 'App\\Repositories\\'.ucfirst($leadType).'QuoteRepository';
            $duplicateRecord = $repository::where('code', $leadCode)->first();
            if ($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
                    return $item;
                });
            }
        }

        return $allowedLeadTypes;
    }

    public function fetchSaveDuplicateLeads($data)
    {
        $lobTeams = $data['lob_team'];
        $parentType = $data['parentType'];
        $entityId = $data['entityId'];

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = 'Business';
        }
        $repository = 'App\\Repositories\\'.ucfirst($parentType).'QuoteRepository';
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
            foreach ($lobTeams as $lob) {
                $repository = 'App\\Repositories\\'.ucfirst($lob).'QuoteRepository';

                if (! class_exists($repository)) {
                    return false;
                }
                if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                    $dataArr['business_type_of_insurance_id'] = 5;
                }

                $response = Capi::request('/api/v1-save-'.strtolower($lob).'-quote', 'post', $dataArr);
                if (isset($response->message) && str_contains($response->message, 'Error')) {
                    return false;
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
        }
    }
}
