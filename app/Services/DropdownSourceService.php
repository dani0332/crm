<?php

namespace App\Services;

use App\Models\Emirate;
use App\Models\HealthCoverFor;
use App\Models\LeadStatus;
use App\Models\MartialStatus;
use App\Models\Nationality;
use App\Models\User;

class DropdownSourceService extends BaseService
{

	public function getDropdownSource($type)
	{
        $data = '';
        switch ($type) {
            case 'marital_status_id':
                $data = MartialStatus::select('id', 'text')->get();
                break;
            case 'nationality_id':
                $data = Nationality::select('id', 'text')->get();
                break;
            case 'lead_status':
                $data = LeadStatus::select('id, text')->get();
                break;
            case 'cover_for_id':
                $data = HealthCoverFor::select('id','text')->get();
                break;
            case 'emirate_of_your_visa_id':
                $data = Emirate::select('id','text')->get();
                break;
            case 'team_users':
                $data = User::select('id','name')->get();
                break;
            default:
                break;
        }
        return $data;
	}
}
