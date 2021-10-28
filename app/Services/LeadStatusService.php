<?php

namespace App\Services;

use App\Models\LeadStatus;
use Illuminate\Http\Request;

class LeadStatusService extends BaseService
{

	public function saveLeadStatus(Request $request)
	{
        $leadStatus = new LeadStatus();
        $leadStatus->name = $request->name;
        $leadStatus->save();
	}

    public function updateLeadStatus(Request $request, $id)
	{
        $leadStatus = LeadStatus::find($id);
        $leadStatus->name = $request->name;
        $leadStatus->save();

        if (isset($request->return_to_view))
            return redirect("quote/teams/" . $leadStatus->id)->with('success', 'Lead Status has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "name" => "input|text|required|title",
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'name':
                $title = "Lead Status Name";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            'create' => '',
            'list' => '',
        ];
    }
    public function fillModelSearchProperties(){
        return ['name'];
    }
}
