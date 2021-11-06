<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use App\Models\CarQuote;
use App\Models\FtcQuoteStatusHistory;
use App\Transformers\TeamsTransformer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Auth;


class Teams extends BaseModel
{
    protected $table = 'teams';
    protected $filter = [];
    use HasFactory;

    public $access = [

        'write' => ['admin','production_approval_manager'],
        'update' => ['admin','production_approval_manager'],
        'delete' => ['admin','production_approval_manager'],
        'access' => [
            "pa" => [],
            "advisor" => [ ],
            "admin" => [ 'lead_id', 'user_id' ],
            "invoicing" => [ ],
            "production_approval_manager" => [ 'lead_id', 'user_id']

        ],
        "list" => [
            "pa" => [ 'lead_id', 'user_id' ],
            "advisor" => [ 'id', 'lead_id', 'user_id'],
            "admin" => [  'lead_id', 'user_id'],
            "invoicing" => [  'lead_id', 'user_id'],
            "production_approval_manager" => [  'lead_id', 'user_id']
        ]
    ];

    public function user_id()
    {
        $filter = Session::get('teams_filter');
        if(Arr::exists($filter, 'user_id')){
            return $this->hasOne(User::class, 'id', 'user_id')
                ->where('id', $filter["user_id"]);
        }
        return $this->hasOne(User::class, 'id', 'user_id')->select(['id', 'email','name']);
    }

    public function pa_id()
    {
        $filter = Session::get('teams_filter');
        if(Arr::exists($filter, 'code')){
            return $this->hasMany(CarQuote::class, 'pa_id', 'user_id')
                ->where('code', $filter["code"]);
        }
        return $this->hasMany(CarQuote::class, 'pa_id', 'user_id');;
    }

    public function relations() {
        return ["user_id","pa_id","pa_id.quote_status_id"];
    }

    public function processGetDSL($filters) {

        Session::put('teams_filter', $filters);
        $responseArray =  self::processGetBaseDSL(['lead_id' => Auth::user()->id], false);
        $response = [];

        if(count($responseArray->toArray()) > 0){
            foreach ($responseArray->toArray() as &$rec) {
                if(isset($rec["user_id"])){
                    foreach($rec["pa_id"] as &$value) {
                        $response[] = ["status" =>$value["quote_status_id"]["text"], "id" =>$value["id"], "pa_id" => $rec["user_id"]["id"], "pa_email" => $rec["user_id"]["email"], "pa_name" => $rec["user_id"]["name"], "code" => $value["code"] , "customer_name" => $value["first_name"]." ".$value["last_name"] ];
                    }
                 }
            }
        }
        return $response;
    }

    private function validateUser($userId,$quoteId) {
        $response = Teams::where([
            ['lead_id', '=', Auth::user()->id],
            ['user_id', '=', $userId],
        ])
        ->join('car_quote_request', 'teams.user_id', '=', 'car_quote_request.pa_id')
        ->where('car_quote_request.id',$quoteId)
        ->first();
        return $response;
    }

    public function saveForm($request, $update = false) {

        if(Auth::user()->hasRole('production_approval_manager')){

            if($request->form_id) {

                $quoteId = $request->form_id;
                $user = $request->input('user');
                $notes = '';
                $modelInstance = CarQuote::where('id', $quoteId)->first();

                if($request->action === 'assignme' && $modelInstance) {

                    if(!self::where('user_id',Auth::user()->id)->exists()) {

                        $notes = 'assign me production agent';

                        $newTeamObj = new Teams;
                        $newTeamObj->user_id = Auth::user()->id;
                        $newTeamObj->lead_id = Auth::user()->id;
                        $newTeamObj->save();

                        $modelInstance->pa_id = Auth::user()->id;
                        $modelInstance->save();
                    }
                }
                else if ($this->validateUser($user,$quoteId)) {

                    switch($request->action) {
                        case 'unassign':
                            $notes = 'Unassign production agent';
                            $modelInstance->pa_id = null;
                            $modelInstance->save();
                            break;
                        case 'reassign':
                            $notes = 'reassign  production agent';
                            break;
                    }
                }

                $ftcModel = new FtcQuoteStatusHistory;
                $ftcModel->quote_status_id = $modelInstance->quote_status_id;
                $ftcModel->car_quote_id = $modelInstance->id;
                $ftcModel->notes = $notes;
                return $ftcModel->save();

            }else{


                $userId = $request->input('user_id');
                if(!self::where('user_id',$userId)->exists()) {
                    $getUser =  User::select(['id', 'name'])->whereHas(
                        'roles', function($q){
                            $q->where('name', 'pa');
                        }
                    )
                    ->where('users.id',$userId)
                    ->get();

                    if($getUser) {
                        $request->request->add(['lead_id' => Auth::user()->id]);
                        return parent::saveForm($request, $update );
                    }
                }else{
                    return $this->APIController->respondData(["title" => "Cannot add to team", "message" => "Already added with team."], 400);
                }
            }
        }

        return $this->APIController->respondData(["message" => "Invalid request"], 500);
    }
}
