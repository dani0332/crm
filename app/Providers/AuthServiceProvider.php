<?php

namespace App\Providers;

use App\Models\Team;
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
            $userRoles = $user->usersroles()->get(); // get all roles of user
            $userTeam = Team::where('id', $user->team_id)->first(); // get team of user

            $isAllowed = false;
            foreach ($userRoles as $userRole) {
                if (str_contains(strtolower($userRole->name), 'wcu')) {
                    if ($lead->wcu_id == $user->id) {
                        $isAllowed = true; // WCU advisor can view lead until lead is assigned to him
                    }
                }
                if (str_contains(strtolower($userRole->name), 'manager') && str_contains(strtolower($userRole->name), strtolower($userTeam->name))) {
                    $isAllowed = true; // Manager can view all leads from his team
                }
                if (str_contains(strtolower($userRole->name), 'advisor') && str_contains(strtolower($userRole->name), strtolower($userTeam->name))) {
                    if ($lead->advisor_id == $user->id) {
                        $isAllowed = true; // advisor can view his own leads from his team
                    }
                }
                if (str_contains(strtolower($userRole->name), 'admin')) {
                    $isAllowed = true; // admin can view all leads
                }
            }

            return $isAllowed;
        });
    }
}
