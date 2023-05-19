<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Facades\Capi;
use App\Traits\GenericQueriesAllLobs;

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
                $repository = $this->getRepositoryObject(ucfirst($lob));

                if (! class_exists($repository)) {
                    return false;
                }
                if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                    $dataArr['business_type_of_insurance_id'] = 5;
                }

                $response = method_exists($repository, 'DuplicateLob') ? $repository::DuplicateLob($lob, $dataArr) : Capi::request('/api/v1-save-'.strtolower($lob).'-quote', 'post', $dataArr);
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
}
