<?php

namespace App\Traits;

use App\Enums\quoteTypeCode;

trait CentralTrait
{
    use GenericQueriesAllLobs;
    public function fetchDuplicateAllowedLobs($modelType,$leadCode)
    {
       
       return [
            quoteTypeCode::Home, 
            quoteTypeCode::Health,
            quoteTypeCode::Life, 
            quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical,
            quoteTypeCode::Travel,
            quoteTypeCode::Car,
            quoteTypeCode::Pet,
        ];
       
    }

    public function fetchSaveDuplicateLeads($data)
    {
        $lobTeams = $data['lob_team'];
        $parentType = $data['parentType'];
        $entityId = $data['entityId'];

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = 'Business';
        }

        $parentRecord= $this->getQuoteObject($parentType,   $entityId );

        if (!empty($data['lob_team_sub_selection'])) {
            $parentRecord['enquiryType'] =$data['lob_team_sub_selection'];
        } else {
            $parentRecord['enquiryType'] = 'record_only';
        }
        if (! empty($lobTeams)) {
            foreach ($lobTeams as $lobTeam) {
                $this->createDuplicateRecord($lobTeam, $parentRecord);
            }
        }
  
    }
}
