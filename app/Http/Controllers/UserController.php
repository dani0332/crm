<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use App\Models\User;
use App\Services\LeadAllocationService;
use App\Services\UserService;
use App\Traits\TeamHierarchyHelpers;
use Auth;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use TeamHierarchyHelpers;

    protected $leadAllocationService;
    protected $userService;

    public function __construct(LeadAllocationService $leadAllocationService, UserService $userService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->userService = $userService;
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
            $users = DB::select('SELECT u1.id
                                        ,u1.name
                                        ,u1.email
                                        ,u2.roles
                                        ,teams.name as teamName
                                        ,u1.created_at
                                        ,u1.updated_at
                                        ,u1.is_active
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
                                            ,u1.updated_at');
            $filteredData = $users;

            if (! empty($request->email)) {
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    return $value->email == $request->email;
                });
            }
            if (! empty($request->name)) {
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    if (str_contains(strtoupper($value->name), strtoupper($request->name))) {
                        return $value;
                    }
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
        $products = $this->getAllProducts(); // get all products
        $teams = [];
        $subTeams = [];

        return view('user.add', compact('roles', 'products', 'teams', 'subTeams'));
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
            'products' => 'required',
            'teams' => 'required',
        ]);

        $user = $this->userService->createUserRecord($request);

        $this->leadAllocationService->createLeadAllocationRecord($user->id);

        $user->assignRole($request->input('roles'));
        if (isset($request->return_to_view)) {
            return redirect('admin/users/'.$user->id)->with('success', 'User has been stored');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $subTeamName = '';
        $additionalTeamNames = '';
        $managerName = implode(',', $this->getUserManagers($user->id)->pluck('name')->toArray());
        $teamName = implode(',', $this->getUserTeams($user->id)->pluck('name')->toArray());
        $productName = implode(',', $this->getUserProducts($user->id)->pluck('name')->toArray());
        if ($user->additional_team_ids != '') {
            $additionalTeamNamesArray = Team::whereIn('id', explode(',', $user->additional_team_ids))->where('type', TeamTypeEnum::Product)->pluck('name')->toArray();
            $additionalTeamNames = implode(', ', $additionalTeamNamesArray);
        }
        if ($user->sub_team_id) {
            $subTeamName = Team::find($user->sub_team_id)->name;
        }

        return view('user.show', compact('user', 'teamName', 'subTeamName', 'additionalTeamNames', 'managerName', 'productName'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        $teams = $this->getAllTeams();
        $subTeams = $this->getSubTeamsByTeamIds($teams->pluck('id'));
        $selectedAdditionalTeams = $user->additional_team_ids;
        $products = $this->getAllProducts();
        $userProductIds = $this->getUserProducts($user->id)->pluck('id')->toArray();
        $userTeamIds = $this->getUserTeams($user->id)->pluck('id')->toArray();
        $managers = $this->getManagersBasedOnTeamId($userProductIds, $user->id);
        $userManagerIds = $this->getUserManagers($user->id)->pluck('id')->toArray();

        return view('user.edit', compact(
            'user',
            'roles',
            'userRole',
            'selectedAdditionalTeams',
            'subTeams',
            'products',
            'userProductIds',
            'teams',
            'userTeamIds',
            'managers',
            'userManagerIds'
        ));
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
            'teams' => 'required',
        ]);
        // Updating user
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->password = bcrypt($request->password);
        $user->is_active = $request->is_active == 'on' ? 1 : 0;

        $this->leadAllocationService->updateUserAllocationRecord($user->id, null, null, $user->is_active);

        if (isset($request->additionalTeams)) {
            if (count((array) $request->additionalTeams) > 1) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }
        if ($request->sub_team_id != '0') {
            $user->sub_team_id = $request->sub_team_id;
        }

        $user->save();

        if ($request->manager != '0' && isset($request->manager)) {
            DB::table('user_manager')->where('user_id', $user->id)->delete();
            foreach ($request->manager as $managerId) {
                DB::table('user_manager')->insert([
                    'user_id' => $user->id,
                    'manager_id' => $managerId,
                ]);
            }
        }

        if ($request->teams != '0' && isset($request->teams)) {
            DB::table('user_team')->where('user_id', $user->id)->delete();
            foreach ($request->teams as $teamId) {
                DB::table('user_team')->insert([
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                ]);
            }
        }

        if ($request->products != '0' && isset($request->products)) {
            DB::table('user_products')->where('user_id', $user->id)->delete();
            foreach ($request->products as $productId) {
                DB::table('user_products')->insert([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }

        // Updating user roles
        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        $user->assignRole($request->input('roles'));

        if (isset($request->return_to_view)) {
            return redirect('admin/users/'.$user->id)->with('success', 'User has been updated');
        }
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

    public function getProductTeams(Request $request)
    {
        return $this->getTeamsByProductIds($request->productIds);
    }

    public function me(Request $request)
    {
        return ['name' => Auth::user()->name, 'email' => Auth::user()->email, 'id' => Auth::user()->id, 'role' => strtolower(Auth::user()->usersroles[0]->name)];
    }

    public function getSubTeams(Request $request)
    {
        if ($request->teamId == null) {
            return [];
        }

        return  $this->getSubTeamsByTeamIds($request->teamId);
    }

    public function getTeamManagers(Request $request)
    {
        if ($request->teamId == null) {
            return [];
        }

        return $this->getManagersBasedOnTeamId($request->teamId, $request->userId);
    }

    private function getManagersBasedOnTeamId($teamId, $userId = null)
    {
        $teams = Team::whereIn('id', $teamId)->get();
        if (! $teams) {
            return [];
        }

        // devising role name based on primary team name as we have to show manager name based on primary team name
        $roleNames = [];
        $combinedRoleNames = [];
        foreach ($teams as $team) {
            $teamName = $team->name;
            if ($teamName == strtoupper(quoteTypeCode::Health)) {
                $roleNames = [RolesEnum::RMManager, RolesEnum::RMDeputyManager, RolesEnum::EBPManager, RolesEnum::EBPDeputyManager, RolesEnum::HealthManager, RolesEnum::HealthDeputyManager, RolesEnum::HealthRenewalManager, RolesEnum::HealthNewBusinessManager];
            } elseif ($teamName == strtoupper(quoteTypeCode::Business)) {
                $roleNames = [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::CorplineManager, RolesEnum::CorplineDeputyManager, RolesEnum::BusinessManager, RolesEnum::BusinessDeputyManager, RolesEnum::GMRenewalManager, RolesEnum::CorplineRenewalManager, RolesEnum::GMNewBusinessManager, RolesEnum::CorplineNewBusinessManager];
            } else {
                $roleNames = [$teamName.'_MANAGER', $teamName.'_DEPUTY_MANAGER', $teamName.'_RENEWAL_MANAGER', $teamName.'_NEW_BUSINESS_MANAGER'];
            }
            foreach ($roleNames as $role) {
                array_push($combinedRoleNames, $role);
            }
        }

        return User::join('model_has_roles', 'model_has_roles.model_id', 'users.id')
            ->join('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', $combinedRoleNames)
            ->select(
                'users.id',
                DB::raw('CONCAT(users.name, " - ", roles.name) as name')
            )->get();
    }
}
