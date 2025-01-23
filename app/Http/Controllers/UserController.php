<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Requests\InslyAdvisorRequest;
use App\Models\BusinessTypeOfInsurance;
use App\Models\InslyAdvisor;
use App\Models\Team;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\LeadAllocationService;
use App\Services\UserService;
use App\Traits\TeamHierarchyTrait;
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
                DB::raw('DATE_FORMAT(u1.updated_at, "%Y-%m-%d %H:%i") as updated_at'),
                DB::raw('DATE_FORMAT(u1.created_at, "%Y-%m-%d %H:%i") as created_at'),
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
        $departments = $this->userService->getDepartmentsList();
        $businessTypes = BusinessTypeOfInsurance::select('id as value', 'text as label')->get();

        return inertia('Admin/Users/Form', [
            'roles' => $roles,
            'products' => $products,
            'teams' => $teams,
            'departments' => $departments,
            'subTeams' => $subTeams,
            'permissions' => $permissions,
            'businessTypes' => $businessTypes,
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
                        }
                    }
                }
            }
        }

        $user->assignRole($request->input('roles'));

        // if Corpline Advisor exists, then set Business Types otherwise set it as empty
        $user->businessTypes()->sync($user->hasRole(RolesEnum::CorpLineAdvisor) ? request('businessTypes', []) : []);

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
        $user['new_updated_at'] = Carbon::createFromFormat('d-M-Y h:ia', $user->updated_at)->format('Y-m-d H:i:s');

        $subTeamName = '';
        $additionalTeamNames = '';
        $managerName = implode(',', $this->getUserManagers($user->id)->pluck('name')->toArray());
        $teamName = implode(',', $this->getUserTeams($user->id)->pluck('name')->toArray());
        $productName = implode(',', $this->getUserProducts($user->id)->pluck('name')->toArray());
        $user->roles = $user->roles->pluck('name')->toArray();
        $user->permissions = $user->permissions->pluck('name')->toArray();
        $user->businessTypes = $user->businessTypes->pluck('text')->toArray();
        $user->department = $user->department ?? '';
        $departments = implode(',', $user->departments->pluck('name')->toArray()) ?? '';

        if ($user->additional_team_ids != '') {
            $additionalTeamNamesArray = Team::whereIn('id', explode(',', $user->additional_team_ids))->where('type', TeamTypeEnum::PRODUCT)->pluck('name')->toArray();
            $additionalTeamNames = implode(', ', $additionalTeamNamesArray);
        }
        if ($user->sub_team_id) {
            $subTeamName = Team::find($user->sub_team_id)->name;
        }

        $user->load([
            'advisors' => function ($advisor) {
                $advisor->select('user_id', 'name');
            },
        ]);

        return inertia('Admin/Users/Show', [
            'user' => $user,
            'teamName' => $teamName,
            'subTeamName' => $subTeamName,
            'departments' => $departments,
            'additionalTeamNames' => $additionalTeamNames,
            'managerName' => $managerName,
            'productName' => $productName,
            'userAdvisors' => $user->advisors,
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
        $userBusinessTypeIds = $user->businessTypes->pluck('id')->toArray();
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
        $departmentIds = $this->getUserDepartments($user->id)->pluck('department_id')->toArray();
        $departments = $this->userService->getDepartmentsList();
        $businessTypes = BusinessTypeOfInsurance::select('id as value', 'text as label')->get();

        return inertia('Admin/Users/Form', [
            'user' => $user,
            'roles' => $roles,
            'userRole' => $userRole,
            'selectedAdditionalTeams' => $selectedAdditionalTeams,
            'subTeams' => $subTeams,
            'department_ids' => $departmentIds,
            'departments' => $departments,
            'products' => $products,
            'userProductIds' => $userProductIds,
            'teams' => $teams,
            'userTeamIds' => $userTeamIds,
            'managers' => $managers,
            'userManagerIds' => $userManagerIds,
            'permissions' => $permissions,
            'userPermissions' => $userPermissions,
            'businessTypes' => $businessTypes,
            'userBusinessTypeIds' => $userBusinessTypeIds,
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
        $user->department_id = $request->department_id ?? null;
        if (isset($request->password)) {
            $user->password = bcrypt($request->password);
        }
        $user->is_active = $request->is_active ? 1 : 0;

        if ($request->department_ids != null) {
            app(DepartmentService::class)->syncUserDepartments($user, $request->department_ids);
        }
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
        } else {
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

        // if Corpline Advisor exists, then set Business Types otherwise set it as empty
        $user->businessTypes()->sync($user->hasRole(RolesEnum::CorpLineAdvisor) ? request('businessTypes', []) : []);

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

    public function getTeamDepartments(Request $request)
    {
        return $this->getDepartmentsByTeamIds($request->teamIds);
    }

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
                $roleNames = [RolesEnum::RMManager, RolesEnum::RMDeputyManager, RolesEnum::EBPManager, RolesEnum::EBPDeputyManager, RolesEnum::HealthManager, RolesEnum::HealthDeputyManager, RolesEnum::HealthRenewalManager];
            } elseif ($teamName == strtoupper(quoteTypeCode::Business)) {
                $roleNames = [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::CorplineManager, RolesEnum::CorplineDeputyManager, RolesEnum::BusinessManager, RolesEnum::BusinessDeputyManager, RolesEnum::GMRenewalManager, RolesEnum::CorplineRenewalManager];
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

    /**
     * Add Insly Advisors to the user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addInslyAdvisor(InslyAdvisorRequest $request, User $user)
    {
        // Remove existing advisors associated with the user
        $user->advisors()->delete();
        $newAdvisors = [];
        // Iterate over the advisors from the request
        foreach ($request->advisors as $advisorData) {
            // Collect data for new advisors
            $newAdvisors[] = [
                'name' => $advisorData['name'],
                'user_id' => $advisorData['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        // Bulk insert new advisors
        if (! empty($newAdvisors)) {
            InslyAdvisor::insert($newAdvisors);
        }

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Advisors added successfully');
    }
}
