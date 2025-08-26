<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Role;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserService extends BaseService
{
    public static function getRolesByUserId($userId)
    {
        return DB::select('select * from model_has_roles where model_id = ?', [$userId])->get();
    }

    public function getUserNameById($id)
    {
        return User::find($id)->name;
    }

    public function createUserRecord(Request $request)
    {
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->calendar_link = $request->calendar_link;
        $user->phone_calendar_link = $request->phone_calendar_link;
        $user->department_id = $request->department_id ?? null;
        $user->password = bcrypt($request->password);
        $user->is_active = true;
        if ((! empty($request->additionalTeams) && $request->sub_team_id != '0')) {
            $user->sub_team_id = $request->sub_team_id;
        }
        if (! empty($request->additionalTeams) && isset($request->additionalTeams)) {
            if (count((array) $request->additionalTeams) > 0) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }

        $user->save();

        // Handle department sync - remove all if empty or null
        if ($request->department_ids !== null && ! empty($request->department_ids)) {
            app(DepartmentService::class)->syncUserDepartments($user, $request->department_ids);
        } else {
            app(DepartmentService::class)->syncUserDepartments($user, []);
        }

        if ($request->manager != '0' && isset($request->manager)) {
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
        if ($request->products != '0') {
            DB::table('user_products')->where('user_id', $user->id)->delete();
            foreach ($request->products as $productId) {
                DB::table('user_products')->insert([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }

        // also add permissions for user
        if (isset($request->permissions)) {
            DB::table('model_has_permissions')->where('model_id', $user->id)->delete();
            foreach ($request->permissions as $permissionId) {
                DB::table('model_has_permissions')->insert([
                    'model_id' => $user->id,
                    'permission_id' => $permissionId,
                    'model_type' => 'App\Models\User',
                ]);
            }
        }

        return $user;
    }

    public function getUserById($userId)
    {
        return User::where('id', $userId)->first();
    }

    public function isAllowedToShowLeadListReport()
    {
        if (auth()->user()->hasAnyRole([RolesEnum::Admin])) {
            return true;
        } elseif (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager])) {
            if (in_array(TeamNameEnum::ORGANIC, app(User::class)->getUserTeams(auth()->user()->id)->toArray())) {
                return true;
            }
        }

        return false;
    }

    public function getDepartmentsList()
    {
        return DB::table('departments')->where('is_active', 1)->orderBy('name')->get();
    }

    /**
     * Get support users with role concatenation
     * Unified method combining fetchSupportUserList and getSupportUsers functionality
     *
     * @param  array  $options  Configuration options:
     *                          - roles: array of role names (default: [CLIENTSUPPORT, CLIENTSUPPORTLEAD])
     *                          - product_filter: specific product name/code to filter by
     *                          - department: department IDs to filter by
     *                          - line_of_business: line of business codes to filter by
     *                          - business_insurance_type: business insurance type IDs to filter by
     *                          - include_role_in_name: boolean to concatenate role with name (default: false)
     *                          - return_format: 'collection' or 'array' (default: 'collection')
     */
    public function getSupportUsers(array $options = []): Collection|array
    {
        // Default options
        $roles = $options['roles'] ?? [RolesEnum::CLIENTSUPPORT, RolesEnum::CLIENTSUPPORTLEAD];
        $includeRoleInName = $options['include_role_in_name'] ?? false;
        $returnFormat = $options['return_format'] ?? 'collection';

        // Filter to existing roles only
        $existingRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();

        if (empty($existingRoles)) {
            return $returnFormat === 'array' ? [] : collect([]);
        }

        // Build the base query using Eloquent relationships
        $query = User::query()
            ->with(['roles' => function ($query) use ($existingRoles) {
                $query->whereIn('name', $existingRoles);
            }])
            ->whereHas('roles', function ($query) use ($existingRoles) {
                $query->whereIn('name', $existingRoles);
            })
            ->where('is_active', 1);

        // Apply product filter (specific product or line of business)
        if (! empty($options['product_filter'])) {
            $productFilter = $options['product_filter'];

            // Normalize to array for consistent processing
            $productFilters = is_array($productFilter) ? $productFilter : [$productFilter];

            // Process each filter and collect all product names to search for
            $productNames = collect($productFilters)->flatMap(function ($filter) {
                // Handle QuoteTypes enum cases
                if ($filter instanceof QuoteTypes) {
                    return [$filter->value];
                }

                return [];
            })->unique()->values();

            if ($productNames->isNotEmpty()) {
                $query->whereHas('products', function ($q) use ($productNames) {
                    $q->where('type', TeamTypeEnum::PRODUCT)
                        ->whereIn('name', $productNames->toArray())
                        ->where('is_active', 1);
                });
            }
        }

        // Apply line of business filter
        if (! empty($options['line_of_business'])) {
            $lineOfBusinessFilter = is_array($options['line_of_business'])
                ? $options['line_of_business']
                : [$options['line_of_business']];

            // Convert line of business to product codes
            $productCodes = collect($lineOfBusinessFilter)
                ->map(fn ($item) => collect(QuoteTypes::getName($item)->getTeams())->pluck('value')->all())
                ->filter()->flatten()
                ->toArray();

            if (! empty($productCodes)) {
                $query->whereHas('products', function ($q) use ($productCodes) {
                    $q->where('type', TeamTypeEnum::PRODUCT)
                        ->whereIn('teams.name', $productCodes);
                });
            }
        }

        // Apply department filter
        if (! empty($options['department'])) {
            $query->whereIn('department_id', (array) $options['department']);
        }

        // Apply business insurance type filter
        if (! empty($options['business_insurance_type'])) {
            $query->whereHas('businessTypes', function ($q) use ($options) {
                $q->whereIn('business_type_of_insurance.id', (array) $options['business_insurance_type']);
            });
        }

        // Execute query and format results
        $users = $query->orderBy('name')->get();

        // Transform the results
        $result = $users->map(function ($user) use ($includeRoleInName) {
            if ($includeRoleInName && $user->roles->isNotEmpty()) {
                // Get the first matching role for concatenation
                $role = $user->roles->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name.' - '.$role->name,
                    'role' => $role->name,
                    'original_name' => $user->name,
                ];
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
            ];
        });

        return $returnFormat === 'array' ? $result->toArray() : $result;
    }
}
