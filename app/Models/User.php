<?php

namespace App\Models;

use App\Enums\RolesEnum;
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
        return Auth::user()->hasAnyRole([RolesEnum::CarRenewalAdvisor, RolesEnum::CarRenewalManager]);
    }

    public function isRenewalAdvisor()
    {
        return Auth::user()->hasAnyRole([RolesEnum::CarRenewalAdvisor, RolesEnum::TravelRenewalAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HomeRenewalAdvisor, RolesEnum::LifeRenewalAdvisor, RolesEnum::GMRenewalAdvisor, RolesEnum::CorpLineRenewalAdvisor, RolesEnum::PetRenewalAdvisor]);
    }
    public function isRenewalManager()
    {
        return Auth::user()->hasAnyRole([RolesEnum::CarRenewalManager, RolesEnum::TravelRenewalManager, RolesEnum::HealthRenewalManager, RolesEnum::HomeRenewalManager, RolesEnum::LifeRenewalManager, RolesEnum::GMRenewalManager, RolesEnum::CorpLineRenewalManager, RolesEnum::PetRenewalManager]);
    }
    public function isNewBusinessManager()
    {
        return Auth::user()->hasAnyRole([RolesEnum::HealthNewBusinessManager, RolesEnum::TravelNewBusinessManager, RolesEnum::HomeNewBusinessManager, RolesEnum::LifeNewBusinessManager, RolesEnum::GMNewBusinessManager, RolesEnum::CorpLineNewBusinessManager, RolesEnum::PetNewBusinessManager]);
    }
    public function isNewBusinessAdvisor()
    {
        return Auth::user()->hasAnyRole([RolesEnum::CarNewBusinessAdvisor, RolesEnum::TravelNewBusinessAdvisor, RolesEnum::HealthNewBusinessAdvisor, RolesEnum::HomeNewBusinessAdvisor, RolesEnum::LifeNewBusinessAdvisor, RolesEnum::GMNewBusinessAdvisor, RolesEnum::CorpLineNewBusinessAdvisor, RolesEnum::PetNewBusinessAdvisor]);
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
            if (str_contains(strtolower($userRole->name), strtolower($teamType) . '_advisor')) {
                $isAdvisor = true;
            }
        }
        return $isAdvisor;
    }

    public function isAdmin()
    {
        return Auth::user()->hasRole(RolesEnum::Admin);
    }

    public function getUserTeams($userId)
    {
        $userTeamIds = UserTeams::where('user_id', $userId)->get()->pluck('team_id');
        return Team::whereIn('id', $userTeamIds)->get()->pluck('name');
    }

    public function processGetDSL($filters = [])
    {

        if (Auth::user()->hasAnyRole([RolesEnum::ProductionApprovalManager, RolesEnum::Advisor, RolesEnum::Admin])) {
            return $this->getUserRoles();
        }
        return self::with(array('usersroles' => function ($query) {
            $query->where('name', 'admin');
        }))->get();
    }

    public function hasMyLeadAccess()
    {
        return Auth::user()->hasAnyRole([
            RolesEnum::Admin, RolesEnum::CarAdvisor, RolesEnum::BusinessAdvisor, RolesEnum::HealthAdvisor, RolesEnum::HomeAdvisor,
            RolesEnum::LifeAdvisor, RolesEnum::TravelAdvisor, RolesEnum::GMAdvisor, RolesEnum::RMAdvisor, RolesEnum::CorpLineAdvisor,
            RolesEnum::EBPAdvisor, RolesEnum::HealthWCUAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor,
            RolesEnum::TravelAdvisor, RolesEnum::HealthWCUAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthNewBusinessAdvisor,
            RolesEnum::TravelRenewalAdvisor, RolesEnum::TravelNewBusinessAdvisor, RolesEnum::LifeRenewalAdvisor, RolesEnum::LifeNewBusinessAdvisor,
            RolesEnum::HomeRenewalAdvisor, RolesEnum::HomeNewBusinessAdvisor, RolesEnum::GMNewBusinessAdvisor, RolesEnum::GMRenewalAdvisor,
            RolesEnum::CorpLineRenewalAdvisor,
            RolesEnum::CorpLineNewBusinessAdvisor, RolesEnum::PetRenewalAdvisor, RolesEnum::PetNewBusinessAdvisor, RolesEnum::CarRenewalAdvisor,RolesEnum::PetAdvisor
        ]);
    }

    public function hasPolicyIssuanceAccess()
    {
        return Auth::user()->hasAnyRole([
            RolesEnum::Advisor, RolesEnum::PA, RolesEnum::Payment, RolesEnum::Invoicing,
            RolesEnum::ProductionApprovalManager
        ]);
    }

    public function getUserRoles()
    {
        return User::select(['id', 'name'])->whereHas(
            'roles',
            function ($q) {
                $q->where('name', 'pa');
            }
        )
            ->get();
    }

    public function isHealthWCUAdvisor()
    {
        $userRoles = Auth::user()->usersroles()->get();
        $isAdvisor = false;
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), 'wcu')) {
                $isAdvisor = true;
            }
        }
        return $isAdvisor;
    }
}
