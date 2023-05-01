<?php

namespace App\Traits;

use App\Enums\quoteTypeCode;
use App\Models\LifeQuote;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function fetchDuplicateAllowedLobsList($leadCode)
    {
       // dd($this->limit(10)->get()->toArray(), $this->model() == LifeQuote::class);
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
        dd($data, $this->save($data));
        return ;

        $quote = $this->where('uuid', $data['uuid'])->first();

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
