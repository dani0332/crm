<?php

namespace App\Providers;

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
    }
}
