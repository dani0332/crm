<?php

namespace App\Services;

use App\Models\LeadStatus;
use Illuminate\Http\Request;
use DB;
class LeadStatusService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = "select ls.id, ls.name from lead_status ls";
    }
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

    public function getGridData($searchProperties, $request){
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = 'ls';
                    $this->query = $this->query. $count > 0 ? ' and' : ' where '.$suffix.'.'.$item.'='."'".$request[$item]."'";
                    $count++;
                }
            }
        }

        return DB::select($this->query);
    }

    public function getEntity($id){
        return DB::select($this->query.' where ls.id = '. $id);
    }

    public function getEntityPlain($id){
        return LeadStatus::find($id);
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
