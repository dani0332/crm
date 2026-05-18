<?php

namespace App\Services;

use App\Enums\CacheKeyEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Jobs\SendManagerDeactivationAttemptEmailJob;
use App\Models\Role;
use App\Models\User;
use App\Services\Cache\CacheManager;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UserService extends BaseService
{
    public function __construct(
        private readonly HRMRequestService $hrmRequestService
    ) {}
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
        $user->employee_code = $request->employee_code ?? null;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->calendar_link = $request->calendar_link;
        $user->phone_calendar_link = $request->phone_calendar_link;
        $user->department_id = $request->department_id ?? null;
        $user->password = bcrypt($request->password);
        $user->is_active = true;
        $user->rm_category_id = (! empty($request->rm_category_id) && $request->rm_category_id > 0) ? $request->rm_category_id : null;
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

    public function getSubordinates(int $userId): Collection
    {
        /**
         * Subordinates are users whose `user_manager.manager_id` points to the manager user.
         *
         * Only active users should be considered subordinates for operational flows (e.g. deactivation
         * notifications). Inactive users should not trigger those alerts.
         *
         * This is a self-referencing many-to-many (users <-> users via user_manager). Using
         * relationship-based whereHas() queries can become fragile because Laravel aliases the
         * related `users` table in self-joins; that can lead to empty results (seen on sqlite test cases).
         *
         * A direct join against the pivot avoids self-join aliasing entirely and is stable across
         * DB engines.
         */
        return User::query()
            ->join('user_manager', 'user_manager.user_id', '=', 'users.id')
            ->where('user_manager.manager_id', $userId)
            ->activeUser()
            ->select(['users.id', 'users.name', 'users.email'])
            ->get();
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
                        ->whereIn('teams.code', $productCodes);
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

    /**
     * Fetch user codes from HRM API and update users table
     *
     * @param  array  $emails  Array of email addresses
     * @return array Returns array with success status and processed data
     */
    public function fetchAndUpdateUserCodes(): array
    {
        $emails = [];
        try {
            $emails = User::whereNull('employee_code')
                ->activeUser()
                ->pluck('email')
                ->filter(function ($email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        LoggerService::warning(static::class.'::fetchAndUpdateUserCodes - Invalid email format', [
                            'email' => $email,
                        ]);

                        return false;
                    }

                    return true;
                })
                ->values()
                ->toArray();

            // Call HRM API to get employee codes
            $employeeData = $this->hrmRequestService->getEmployeeCodes($emails);

            if ($employeeData === false) {

                return [
                    'success' => false,
                    'message' => 'Failed to fetch employee codes from HRM API',
                    'processed' => 0,
                    'updated' => 0,
                    'not_found' => count($emails),
                ];
            }

            $processed = 0;
            $updated = 0;
            $notFound = 0;
            $results = [];

            foreach ($employeeData as $employee) {
                $processed++;
                $email = $employee['email'] ?? null;
                $code = $employee['code'] ?? null;

                if (! $email) {
                    LoggerService::warning(static::class.'::fetchAndUpdateUserCodes - Employee data missing email', [
                        'employee_data' => $employee,
                    ]);

                    continue;
                }

                // Find user by email
                $user = User::where('email', $email)->first();

                if (! $user) {
                    LoggerService::info(static::class.'::fetchAndUpdateUserCodes - User not found for email', [
                        'email' => $email,
                    ]);
                    $notFound++;
                    $results[] = [
                        'email' => $email,
                        'status' => 'user_not_found',
                        'code' => $code,
                    ];

                    continue;
                }

                // Update user employee_code only if current employee_code is NULL and we have a valid code
                if ($code !== null) {
                    if ($user->employee_code === null) {
                        // Use write connection with transaction for safe database operation
                        DB::connection('mysql')->transaction(function () use ($user, $code) {
                            $user->employee_code = $code;
                            $user->setConnection('mysql')->save();
                        });

                        LoggerService::info(static::class.'::fetchAndUpdateUserCodes - Updated user employee_code', [
                            'user_id' => $user->id,
                            'email' => $email,
                            'old_employee_code' => null,
                            'new_employee_code' => $code,
                        ]);

                        $updated++;
                        $results[] = [
                            'email' => $email,
                            'user_id' => $user->id,
                            'status' => 'updated',
                            'old_employee_code' => null,
                            'new_employee_code' => $code,
                        ];
                    } else {
                        $results[] = [
                            'email' => $email,
                            'user_id' => $user->id,
                            'status' => 'already_has_employee_code',
                            'existing_employee_code' => $user->employee_code,
                            'hrm_employee_code' => $code,
                        ];
                    }
                } else {

                    $results[] = [
                        'email' => $email,
                        'user_id' => $user->id,
                        'status' => 'no_employee_code_returned',
                        'employee_code' => null,
                    ];
                }
            }

            LoggerService::info(static::class.'::fetchAndUpdateUserCodes - Process completed', [
                'processed' => $processed,
                'updated' => $updated,
                'not_found' => $notFound,
                'total_emails' => count($emails),
            ]);

            return [
                'success' => true,
                'message' => "Successfully processed {$processed} employees, updated {$updated} users",
                'processed' => $processed,
                'updated' => $updated,
                'not_found' => $notFound,
                'results' => $results,
            ];

        } catch (\Exception $e) {
            LoggerService::error(static::class.'::fetchAndUpdateUserCodes - UserService exception occurred', [
                'emails_count' => count($emails),
            ], exception: $e);

            return [
                'success' => false,
                'message' => 'An error occurred while processing user codes: '.$e->getMessage(),
                'processed' => 0,
                'updated' => 0,
                'not_found' => count($emails),
            ];
        }
    }

    /**
     * Get all users for filter dropdown
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getAllUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::select('id', 'name', 'email')
            ->activeUser()
            ->orderBy('name')
            ->get();
    }

    public function getEmployeeCode($email)
    {
        $employeeData = $this->hrmRequestService->getEmployeeCodes([$email]);

        if ($employeeData === false || empty($employeeData)) {
            return null;
        }

        foreach ($employeeData as $employee) {
            if (strtolower($employee['email'] ?? '') === strtolower($email) && ! empty($employee['code'])) {
                return $employee['code'];
            }
        }

        return null;
    }

    public function getAdvisors()
    {
        return User::select('id', 'name')
            ->whereHas('usersroles', function ($query) {
                $query->where('name', 'like', '%advisor%');
            })
            ->activeUser()
            ->get();
    }

    /**
     * Dispatch the manager deactivation attempt email job after the DB transaction commits.
     *
     * This should be triggered when a manager user is being deactivated. If the user has
     * active subordinates, we queue a notification job; otherwise we log and skip dispatch.
     */
    public function sendManagerDeactivationEmail(User $managerUser, ?int $attemptedByUserId): void
    {
        $subordinates = $this->getSubordinates($managerUser->id);

        $deactivationAttemptEmailPayload = [
            'manager_user_id' => $managerUser->id,
            'subordinate_ids' => $subordinates->pluck('id')->all(),
            'attempted_by_user_id' => (int) $attemptedByUserId,
            'subordinates_count' => $subordinates->count(),
        ];

        $logDetails = [
            'manager_user_id' => $deactivationAttemptEmailPayload['manager_user_id'],
            'subordinates_count' => $deactivationAttemptEmailPayload['subordinates_count'],
            'attempted_by_user_id' => $deactivationAttemptEmailPayload['attempted_by_user_id'],
        ];

        if ($subordinates->isNotEmpty()) {
            DB::afterCommit(function () use ($deactivationAttemptEmailPayload, $logDetails) {
                LoggerService::info('Dispatching SendManagerDeactivationAttemptEmailJob', $logDetails);

                SendManagerDeactivationAttemptEmailJob::dispatch(
                    $deactivationAttemptEmailPayload['manager_user_id'],
                    $deactivationAttemptEmailPayload['attempted_by_user_id']
                );
            });

            return;
        }

        LoggerService::info('Skipping SendManagerDeactivationAttemptEmailJob dispatch: manager has no subordinates', [
            'manager_user_id' => $managerUser->id,
        ]);
    }

    /**
     * Get claims managers (users with appropriate roles)
     */
    public function getClaimsManagers(): array
    {
        return CacheManager::remember(CacheKeyEnum::CLAIM_MANAGERS_KEY, function () {
            return User::withRole(RolesEnum::ClaimsManager)
                ->activeUser()
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Build a data URI for embedding advisor photos in PDFs. Remote URLs are fetched with the HTTP client so
     * failures do not throw; local filesystem paths are read when the file exists.
     *
     * Security (SSRF): {@see FILTER_VALIDATE_URL} accepts many schemes/hosts; a malicious stored URL could in
     * theory cause this server to request internal or metadata endpoints. In Blanka, {@see User::$profile_photo_path}
     * is populated with Google profile photo URLs (e.g. lh3.googleusercontent.com) for advisors—not arbitrary
     * user-supplied targets—so this risk is treated as mitigated at the data layer. No additional URL allowlist
     * is applied here by product decision.
     *
     * @return string|null A data URI (e.g. data:image/jpeg;base64,...) or null when the image cannot be loaded.
     */
    public function profilePhotoDataUriForPdf(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $this->profilePhotoDataUriFromRemoteUrl($path);
        }

        return $this->profilePhotoDataUriFromLocalFile($path);
    }

    /**
     * @return string|null Data URI or null when the remote image cannot be loaded.
     */
    private function profilePhotoDataUriFromRemoteUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withOptions(['allow_redirects' => true])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        $binary = $response->successful() ? $response->body() : '';

        if ($binary === '') {
            return null;
        }

        $mime = $response->header('Content-Type') ?? 'image/jpeg';
        $mime = trim(explode(';', $mime)[0]);

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * @return string|null Data URI or null when the path is not a readable file.
     */
    private function profilePhotoDataUriFromLocalFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = @mime_content_type($path);
        if ($mime === false || $mime === '') {
            $mime = 'image/png';
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

}
