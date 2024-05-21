<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Team;
use App\Models\User;
use App\Services\LeadAllocationService;
use App\Services\UserService;
use App\Traits\TeamHierarchyTrait;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use TeamHierarchyTrait;

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
        $query = DB::table('users as u1')
            ->select([
                'u1.id',
                'u1.name',
                'u1.email',
                DB::raw('(SELECT GROUP_CONCAT(roles.name) FROM users INNER JOIN model_has_roles ON model_has_roles.model_id = users.id INNER JOIN roles ON roles.id = model_has_roles.role_id WHERE users.id = u1.id GROUP BY users.name) as roles'),
                'teams.name as teamName',
                'u1.created_at',
                'u1.updated_at',
                'u1.is_active',
            ])
            ->leftJoin('user_team', 'user_team.user_id', '=', 'u1.id')
            ->leftJoin('teams', 'teams.id', '=', 'user_team.team_id');

        if ($request->has('email')) {
            $query->where('u1.email', $request->email);
        }

        if ($request->has('name')) {
            $query->where('u1.name', 'LIKE', '%'.$request->name.'%');
        }

        $users = $query->groupBy('u1.id')->simplePaginate();

        return inertia('Admin/Users/Index', [
            'users' => $users,
        ]);
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
        $permissions = Permission::orderBy('name')->get();

        return inertia('Admin/Users/Form', [
            'roles' => $roles,
            'products' => $products,
            'teams' => $teams,
            'subTeams' => $subTeams,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBusinessQuoteType($type)
    {
        switch ($type) {
            case QuoteTypes::CORPLINE->value:
                return QuoteTypes::BUSINESS->value;
                break;
            case QuoteTypes::GROUP_MEDICAL->value:
                return QuoteTypes::BUSINESS->value;
                break;
            default:
                return QuoteTypes::BUSINESS->value;
                break;
        }

    }
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
        $products = $this->getAllProducts();
        if (! empty($request->products)) {
            $products_types = collect($products)->whereIn('id', $request->products)->values()->all();
            if (! empty($products_types)) {
                foreach ($products_types as $key => $type) {
                    if (in_array(ucfirst($type->name), [QuoteTypes::CORPLINE->value, QuoteTypes::GROUP_MEDICAL->value])) {
                        $quoteTypeName = $this->getBusinessQuoteType(ucfirst($type->name));
                    } else {
                        $quoteTypeName = $type->name;
                    }
                    $quoteTypeId = QuoteTypes::getIdFromValue(ucfirst($quoteTypeName)) ?? null;
                    if (! empty($quoteTypeId)) {
                        $isLead = $this->leadAllocationService->getLeadAllocationRecordByUserId($user->id, $quoteTypeId);
                        if (empty($isLead)) {
                            $this->leadAllocationService->createLeadAllocationRecord($user->id, (object) ['quoteTypeId' => $quoteTypeId]);
                        } else {
                            $this->leadAllocationService->createLeadAllocationRecord($user->id);
                        }
                    }
                }
            }
        }

        $user->assignRole($request->input('roles'));

        return redirect(route('users.show', $user->id))->with('success', $user->name.' with a email '.$user->email.' '.'has been store');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {   
        $user['new_created_at'] = Carbon::createFromFormat('d-M-Y h:ia', $user->created_at)->format('Y-m-d H:i:s');
        $user['new_updated_at'] = Carbon::createFromFormat('d-M-Y h:ia', $user->created_at)->format('Y-m-d H:i:s');

        $subTeamName = '';
        $additionalTeamNames = '';
        $managerName = implode(',', $this->getUserManagers($user->id)->pluck('name')->toArray());
        $teamName = implode(',', $this->getUserTeams($user->id)->pluck('name')->toArray());
        $productName = implode(',', $this->getUserProducts($user->id)->pluck('name')->toArray());
        $user->roles = $user->roles->pluck('name')->toArray();
        $user->permissions = $user->permissions->pluck('name')->toArray();
        if ($user->additional_team_ids != '') {
            $additionalTeamNamesArray = Team::whereIn('id', explode(',', $user->additional_team_ids))->where('type', TeamTypeEnum::PRODUCT)->pluck('name')->toArray();
            $additionalTeamNames = implode(', ', $additionalTeamNamesArray);
        }
        if ($user->sub_team_id) {
            $subTeamName = Team::find($user->sub_team_id)->name;
        }

        return inertia('Admin/Users/Show', [
            'user' => $user,
            'teamName' => $teamName,
            'subTeamName' => $subTeamName,
            'additionalTeamNames' => $additionalTeamNames,
            'managerName' => $managerName,
            'productName' => $productName,
        ]);
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
        $userProductIds = $this->getUserProducts($user->id)->pluck('id')->toArray();
        $teams = $this->getTeamsByProductIds($userProductIds);
        $subTeams = $this->getSubTeamsByTeamIds($teams->pluck('id'));

        $selectedAdditionalTeams = null;
        if (isset($user->additional_team_ids)) {
            $selectedAdditionalTeams = array_map('intval', explode(',', $user->additional_team_ids));
        }

        $products = $this->getAllProducts();

        $userTeamIds = $this->getUserTeams($user->id)->pluck('id')->toArray();
        $managers = $this->getManagersBasedOnTeamId($userTeamIds, $user->id);
        $userManagerIds = $this->getUserManagers($user->id)->pluck('id')->toArray();
        $permissions = Permission::orderBy('name')->get();
        $userPermissions = $user->getDirectPermissions()->pluck('id')->toArray();

        return inertia('Admin/Users/Form', [
            'user' => $user,
            'roles' => $roles,
            'userRole' => $userRole,
            'selectedAdditionalTeams' => $selectedAdditionalTeams,
            'subTeams' => $subTeams,
            'products' => $products,
            'userProductIds' => $userProductIds,
            'teams' => $teams,
            'userTeamIds' => $userTeamIds,
            'managers' => $managers,
            'userManagerIds' => $userManagerIds,
            'permissions' => $permissions,
            'userPermissions' => $userPermissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'roles' => 'required',
            'teams' => 'required',
            'permissions' => 'nullable|array',
        ]);

        // Updating user
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->calendar_link = $request->calendar_link;
        $user->phone_calendar_link = $request->phone_calendar_link;
        if (isset($request->password)) {
            $user->password = bcrypt($request->password);
        }
        $user->is_active = $request->is_active ? 1 : 0;

        /*
         * temp fix: health lead allocation is using team_id to target health product
         * this needs to be updated with new team/product structure
         */
        $products = $this->getAllProducts();
        if (! empty($request->products)) {
            $products_types = collect($products)->whereIn('id', $request->products)->values()->all();
            if (! empty($products_types)) {
                foreach ($products_types as $key => $type) {
                    if (in_array(ucfirst($type->name), [QuoteTypes::CORPLINE->value, QuoteTypes::GROUP_MEDICAL->value])) {
                        $quoteTypeName = $this->getBusinessQuoteType(ucfirst($type->name));
                    } else {
                        $quoteTypeName = $type->name;
                    }
                    $quoteTypeId = QuoteTypes::getIdFromValue(ucfirst($quoteTypeName)) ?? null;
                    if (! empty($quoteTypeId)) {
                        $isLead = $this->leadAllocationService->getLeadAllocationRecordByUserId($user->id, $quoteTypeId);
                        if (empty($isLead)) {
                            $this->leadAllocationService->createLeadAllocationRecord($user->id, (object) ['quoteTypeId' => $quoteTypeId]);
                        } else {
                            $this->leadAllocationService->updateUserAllocationRecord($user->id, null, null, $user->is_active, $quoteTypeId);
                        }
                    }
                }
            }
        }

        if (! empty($request->additionalTeams) && isset($request->additionalTeams)) { 
            if (count((array) $request->additionalTeams) > 1) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }else {
            $user->additional_team_ids = null;
        }

        if (! empty($request->sub_team_id) && $request->sub_team_id != '0') {
            $user->sub_team_id = $request->sub_team_id;
        }

        $user->save();
        if (isset($request->manager) && $request->manager != '0') {
            DB::table('user_manager')->where('user_id', $user->id)->delete();
            foreach ($request->manager as $managerId) {
                DB::table('user_manager')->insert([
                    'user_id' => $user->id,
                    'manager_id' => $managerId,
                ]);
            }
        }

        if ($request->teams != '0') {
            DB::table('user_team')->where('user_id', $user->id)->delete();
            foreach ($request->teams as $teamId) {
                DB::table('user_team')->insert([
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                ]);
            }
        }

        if (isset($request->products) && $request->products != '0') {
            DB::table('user_products')->where('user_id', $user->id)->delete();
            foreach ($request->products as $productId) {
                DB::table('user_products')->insert([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }

        $permissions = (! empty($request->permissions) && count($request->permissions)) ? $request->permissions : [];
        $user->syncPermissions($permissions);

        // Updating user roles
        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        $user->assignRole($request->input('roles'));

        return redirect(route('users.show', $user->id))->with('success', 'User has been updated');
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

    //Scheduled to delete 1st April 2024
    // public function me(Request $request)
    // {
    //     return ['name' => Auth::user()->name, 'email' => Auth::user()->email, 'id' => Auth::user()->id, 'role' => strtolower(Auth::user()->usersroles[0]->name)];
    // }

    public function getSubTeams(Request $request)
    {
        if ($request->teamId == null) {
            return [];
        }

        return $this->getSubTeamsByTeamIds($request->teamId);
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

    public function updateUserStatus(Request $request)
    {
        $currentDateTime = Carbon::now();
        $startDateTime = Carbon::parse('18:30:00'); // 6:30 PM
        $endDateTime = Carbon::parse('08:59:00')->addDay(); // 8:59 AM of the next day
        $user = User::find(auth()->user()->id);
        $user->status = $request->user_status == true ? UserStatusEnum::ONLINE : UserStatusEnum::MANUAL_OFFLINE;
        $user->update();
        if (
            ($currentDateTime->isWeekday() && $currentDateTime->between($startDateTime, $endDateTime))
            || ($currentDateTime->isWeekend())
        ) {
            // Current time is within the specified range on weekdays or any time on Saturday and Sunday
            echo "Current time is between 6:30 PM and 8:59 AM of the next day, and it's a weekday or weekend.";
        } else {
            // Current time is outside the specified range or it's not a weekday or weekend
            echo "Current time is outside the specified range or it's not a weekday or weekend.";
        }
    }
}
