<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;

class Teams extends BaseModel
{
    protected $table = 'teams';
    protected $filter = [];

    use HasFactory;

    public $access = [

        'write' => ['admin', 'production_approval_manager'],
        'update' => ['admin', 'production_approval_manager'],
        'delete' => ['admin', 'production_approval_manager'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => ['lead_id', 'user_id'],
            'invoicing' => [],
            'production_approval_manager' => ['lead_id', 'user_id'],

        ],
        'list' => [
            'pa' => ['lead_id', 'user_id'],
            'advisor' => ['id', 'lead_id', 'user_id'],
            'oe' => ['id', 'lead_id', 'user_id'],
            'admin' => ['lead_id', 'user_id'],
            'invoicing' => ['lead_id', 'user_id'],
            'production_approval_manager' => ['lead_id', 'user_id'],
        ],
    ];

    public function user_id()
    {
        $filter = Session::get('teams_filter');
        if (Arr::exists($filter, 'user_id')) {
            return $this->hasOne(User::class, 'id', 'user_id')
                ->where('id', $filter['user_id']);
        }

        return $this->hasOne(User::class, 'id', 'user_id')->select(['id', 'email', 'name']);
    }

    public function pa_id()
    {
        $filter = Session::get('teams_filter');
        if (Arr::exists($filter, 'code')) {
            return $this->hasMany(CarQuote::class, 'pa_id', 'user_id')
                ->where('code', $filter['code']);
        }

        return $this->hasMany(CarQuote::class, 'pa_id', 'user_id');
    }

    public function relations()
    {
        return ['user_id', 'pa_id', 'pa_id.quote_status_id'];
    }

    private function validateUser($userId, $quoteId)
    {
        $response = self::where([
            ['lead_id', '=', Auth::user()->id],
            ['user_id', '=', $userId],
        ])
            ->join('car_quote_request', 'teams.user_id', '=', 'car_quote_request.pa_id')
            ->where('car_quote_request.id', $quoteId)
            ->first();

        return $response;
    }

    public function saveForm($request, $update = false)
    {
        if (Auth::user()->hasRole('production_approval_manager')) {
            if ($request->form_id) {
                $quoteId = $request->form_id;
                $user = $request->input('user');
                $modelInstance = CarQuote::where('id', $quoteId)->first();

                if ($request->action === 'assignme' && $modelInstance) {
                    if (! self::where('user_id', Auth::user()->id)->exists()) {
                        $newTeamObj = new self;
                        $newTeamObj->user_id = Auth::user()->id;
                        $newTeamObj->lead_id = Auth::user()->id;
                        $newTeamObj->save();

                        $modelInstance->pa_id = Auth::user()->id;
                        $modelInstance->save();
                    }
                } elseif ($this->validateUser($user, $quoteId)) {
                    switch ($request->action) {
                        case 'unassign':
                            $modelInstance->pa_id = null;
                            $modelInstance->save();
                            break;

                        default:
                            break;
                    }
                }
            } else {
                $userId = $request->input('user_id');
                if (! self::where('user_id', $userId)->exists()) {
                    $getUser = User::select(['id', 'name'])->whereHas(
                        'roles',
                        function ($q) {
                            $q->where('name', 'pa');
                        }
                    )
                        ->where('users.id', $userId)
                        ->get();

                    if ($getUser) {
                        $request->request->add(['lead_id' => Auth::user()->id]);

                        return parent::saveForm($request, $update);
                    }
                } else {
                    return $this->APIController->respondData(['title' => 'Cannot add to team', 'message' => 'Already added with team.'], 400);
                }
            }
        }

        return $this->APIController->respondData(['message' => 'Invalid request'], 500);
    }
}
