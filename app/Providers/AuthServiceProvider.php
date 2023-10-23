<?php

namespace App\Providers;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('view-lead-allocation', function ($user) {
            $userRoles = $user->usersroles()->get();
            $isAllowed = false;
            foreach ($userRoles as $userRole) {
                if (str_contains(strtolower($userRole->name), 'lead_allocation')) {
                    $isAllowed = true;
                }
            }

            return $isAllowed;
        });

        Gate::define('view-lead', function ($user, $lead) {
            return true;
        });

        Gate::define(PermissionsEnum::ViewTeamsFilters, function ($user) {
            return $user->hasAnyRole([RolesEnum::Admin, RolesEnum::CarManager]);
        });

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $user->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering]);
        });
    }
}
