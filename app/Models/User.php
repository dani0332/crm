<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use Auth;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use HasRoles;
    use TwoFactorAuthenticatable;
    use Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function usersroles()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id');
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = env("DATETIME_FORMAT");
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = env("DATETIME_FORMAT");
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getTeamUserIds()
    {
        $userRoles = Auth::user()->usersroles()->get();
        $isManager = false;
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), 'manager')) {
                $isManager = true;
            }
        }
        if ($isManager) {
            $userIds = User::where('manager_id', $this->id)->get()->pluck('id');
            return $userIds->implode(',');
        } else {
            return 0;
        }
    }

    public function isManagerOrDeputy()
    {
        $userRoles = Auth::user()->usersroles()->get();
        $isManagerORDeputy = false;
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), 'manager') || str_contains(strtolower($userRole->name), 'deputy')) {
                $isManagerORDeputy = true;
            }
        }
        return $isManagerORDeputy;
    }

    public function isRenewalUser()
    {
        return Auth::user()->hasAnyRole(["CAR_RENEWAL_ADVISOR", "CAR_RENEWAL_MANAGER"]);
    }

    public function isRenewalAdvisor()
    {
        return Auth::user()->hasAnyRole(["CAR_RENEWAL_ADVISOR","TRAVEL_RENEWAL_ADVISOR","HEALTH_RENEWAL_ADVISOR","HOME_RENEWAL_ADVISOR","LIFE_RENEWAL_ADVISOR","GM_RENEWAL_ADVISOR","CORPLINE_RENEWAL_ADVISOR"]);
    }
    public function isRenewalManager()
    {
        return Auth::user()->hasAnyRole(["CAR_RENEWAL_MANAGER","TRAVEL_RENEWAL_MANAGER","HEALTH_RENEWAL_MANAGER","HOME_RENEWAL_MANAGER","LIFE_RENEWAL_MANAGER","GM_RENEWAL_MANAGER","CORPLINE_RENEWAL_MANAGER"]);
    }
    public function isAdvisor()
    {
        $userRoles = Auth::user()->usersroles()->get();
        $isAdvisor = false;
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), 'advisor')) {
                $isAdvisor = true;
            }
        }
        return $isAdvisor;
    }

    public function isSpecificTeamAdvisor($teamType)
    {
        $userRoles = Auth::user()->usersroles()->get();
        $isAdvisor = false;
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), strtolower($teamType).'_advisor')) {
                $isAdvisor = true;
            }
        }
        return $isAdvisor;
    }

    public function isAdmin()
    {
        return Auth::user()->hasRole("ADMIN");
    }

    public function getUserTeams($userId)
    {
        $userTeamIds = UserTeams::where('user_id', $userId)->get()->pluck('team_id');
        return Team::whereIn('id', $userTeamIds)->get()->pluck('name');
    }

    public function processGetDSL($filters = [])
    {

        if (Auth::user()->hasRole('production_approval_manager')) {

            $users =  User::select(['id', 'name'])->whereHas(
                'roles',
                function ($q) {
                    $q->where('name', 'pa');
                }
            )
                ->get();
            return $users;
        }

        if (Auth::user()->hasRole('advisor') || Auth::user()->hasRole('ADMIN')) {

            $users =  User::select(['id', 'name'])->whereHas(
                'roles',
                function ($q) use ($filters) {
                    foreach ($filters as $key => $value) {
                        $q->where($key, $value);
                    }
                }
            )
                ->get();
            return $users;
        }

        return self::with(array('usersroles' => function ($query) {
            $query->where('name', 'admin');
        }))->get();

        // return User::whereHas(
        //     'usersroles', function($q){
        //         $q->where('name', 'admin');
        //     }
        // )->get();
    }
}
