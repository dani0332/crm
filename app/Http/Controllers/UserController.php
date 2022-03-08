<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Models\Team;
use App\Models\User;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users-list|users-create|users-edit|users-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:users-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:users-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filteredData = [];
            $users = DB::select("SELECT u1.id
                                        ,u1.name
                                        ,u1.email
                                        ,u2.roles
                                        ,teams.name as teamName
                                        ,u1.created_at
                                        ,u1.updated_at
                                    FROM users u1
                                    JOIN (
                                        SELECT users.id
                                            ,GROUP_CONCAT(roles.name) AS roles
                                        FROM users
                                        INNER JOIN model_has_roles ON model_has_roles.model_id = users.id
                                        INNER JOIN roles ON roles.id = model_has_roles.role_id
                                        GROUP BY users.name, users.id
                                        ) u2 ON u2.id = u1.id
                                    LEFT JOIN user_team ON user_team.user_id = u2.id
                                    LEFT JOIN teams ON teams.id = user_team.team_id
                                    GROUP BY u1.id
                                            ,u1.name
                                            ,u1.email
                                            ,u2.roles
                                            ,teams.name
                                            ,u1.created_at
                                            ,u1.updated_at");
            $filteredData = $users;
            
            if (!empty($request->email)) {
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    return $value->email == $request->email;
                });
            }
            if (!empty($request->name)) {
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    if (str_contains(strtoupper($value->name), strtoupper($request->name)))
                        return $value;
                });
            }

            return Datatables::of($filteredData)
                ->addIndexColumn()
                ->make(true);
        }

        return view('user.view');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name', 'name')->all(); // get all roles
        $teams = Team::whereNull('parent_team_id')->orderBy('name', 'asc')->get(); // get all teams
        $subTeams = []; 
        return view('user.add', compact('roles', 'teams', 'subTeams'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users',
            'roles' => 'required',
            'password' => 'required',
            'team' => 'required',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);

        if($request->sub_team_id != "0") $user->sub_team_id = $request->sub_team_id;
        
        if ($request->manager != "0") $user->manager_id = $request->manager;

        if(isset($request->additionalTeams)) {
            if(count((array)$request->additionalTeams) > 0)  $user->additional_team_ids = implode(',', $request->additionalTeams);
            else $user->additional_team_ids = $request->additionalTeams[0];
        }
        $user->team_id = $request->team;
        $user->save();

        $user->assignRole($request->input('roles'));
        if (isset($request->return_to_view))
            return redirect("admin/users/" . $user->id)->with('success', 'User has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        // getting current user's team names
        $teamQuery = DB::select("SELECT group_concat(name) as name
                                FROM teams t
                                INNER JOIN user_team ut ON ut.team_id = t.id
                                WHERE ut.user_id = " . $user->id);
        $teamName = $teamQuery[0]->name;
        return view('user.show', compact('user', 'teamName'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::pluck('name', 'name')->all(); // get all roles
        $userRole = $user->roles->pluck('name', 'name')->all(); // get all roles of current user
        $teams = Team::whereNull('parent_team_id')->orderBy('name', 'asc')->get(); // get all teams
        $subTeams = Team::whereNotNull('parent_team_id')->orderBy('name', 'asc')->get();;
        $managers = $this->getManagersBasedOnTeamId($user->team_id); // get all managers based on current user's team
        $selectedAdditionalTeams = $user->additional_team_ids; // get all additional teams of current user
        $selectedTeam = $user->team_id; // current user team
        $selectedManager = $user->manager_id; // current user manager
        return view('user.edit', compact('user', 'roles', 'userRole', 'teams', 'selectedTeam', 'managers', 'selectedManager', 'selectedAdditionalTeams', 'subTeams'));	
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => 'required|email',
            'roles' => 'required',
            'team' => 'required'
        ]);
        
        // Updating user
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        if ($request->manager != "0") $user->manager_id = $request->manager_id;
        if(isset($request->additionalTeams)) {
            if(count((array)$request->additionalTeams) > 1) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            }
            else{
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }
        $user->sub_team_id = $request->sub_team_id;
        $user->team_id = $request->team;
        $user->save();

        // Updating user roles
        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        $user->assignRole($request->input('roles'));

        if (isset($request->return_to_view))
            return redirect("admin/users/" . $user->id)->with('success', 'User has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('message', 'User has been deleted');
    }

    public function me(Request $request)
    {
        return ['name' => Auth::user()->name, 'email' => Auth::user()->email, 'id' => Auth::user()->id, 'role' => strtolower(Auth::user()->usersroles[0]->name)];
    }

    public function getSubTeams(Request $request)
    {
        $teamId = $request->query()['teamId'];
        return Team::where('parent_team_id', $teamId)->select('id', 'name')->orderBy('name', 'asc')->get();
    }

    public function getTeamManagers(Request $request)
    {
        $teamId = $request->query()['teamId'];  
        return $this->getManagersBasedOnTeamId($teamId);
    }

    public function getManagersBasedOnTeamId($teamId)
    {

        $team = Team::find($teamId);
        $teamUsers = User::Where('team_id', $teamId)->get();
        $teamName = strtoupper($team->name);
        // devicing role name based on primary team name as we have to show manager name based on primary team name
        $roleNames = [];
        if($teamName == strtoupper(quoteTypeCode::Health)){
            $roleNames = ['RM_MANAGER', 'RM_DEPUTY_MANAGER', 'EBP_MANAGER', 'EBP_DEPUTY_MANAGER', 'HEALTH_MANAGER', 'HEALTH_DEPUTY_MANAGER'];
        }
        else if($teamName == strtoupper(quoteTypeCode::Business)){
            $roleNames = ['GM_MANAGER', 'GM_DEPUTY_MANAGER', 'CORPLINE_MANAGER', 'CORPLINE_DEPUTY_MANAGER', 'BUSINESS_MANAGER', 'BUSINESS_DEPUTY_MANAGER'];
        }
        else{
            $roleNames = [$teamName . '_MANAGER', $teamName . '_DEPUTY_MANAGER'];
        }
        $teamManagers = [];
        foreach ($teamUsers as $teamUser) {            
            // getting all role of each user
            $userRoles = $teamUser->roles->pluck('name')->all();
            // checking if user has any of the roles we are looking for
            $filteredRoles = array_filter($userRoles, function ($item) use ($roleNames) {
                return in_array($item, $roleNames);
            });
            // if user has any of the roles we are looking for then add him to the list of managers
            if(count($filteredRoles) > 0){
                array_push($teamManagers, ['id' => $teamUser->id, 'name' => $teamUser->name. ' - '. implode(', ', $filteredRoles)]);
            }
        }
        return $teamManagers;
    }
}
