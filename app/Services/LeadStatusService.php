<?php

namespace App\Services;

use App\Models\LeadStatus;
use App\Models\QuoteStatus;
use Illuminate\Http\Request;
use DB;
class LeadStatusService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = "select ls.id, ls.uuid, ls.text from quote_status ls";
    }
	public function saveLeadStatus(Request $request)
	{
        $existingStatus = QuoteStatus::where('text', $request->text)->first();
        if($existingStatus != null){
            return "Error: name already exists";
        }
        $leadStatus = new QuoteStatus();
        $leadStatus->code = $request->text;
        $leadStatus->text = $request->text;
        $leadStatus->is_active = true;
        $leadStatus->save();
        return QuoteStatus::find($leadStatus->id)->uuid;
	}

    public function updateLeadStatus(Request $request, $id)
	{
        QuoteStatus::where('uuid',$id)->update(
            ['text'=>$request->text]
        );

        if (isset($request->return_to_view))
            return redirect("quote/teams/" . $id)->with('success', 'Lead Status has been updated');
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
        return DB::select($this->query.' where ls.uuid = "'. $id.'"');
    }

    public function getEntityPlain($id){
        return QuoteStatus::where('uuid',$id);
    }

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "text" => "input|text|required|title",
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'text':
                $title = "Status Name";
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
        return ['text'];
    }
}
