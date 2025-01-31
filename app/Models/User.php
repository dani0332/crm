<?php

namespace App\Models;

use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Lab404\Impersonate\Models\Impersonate;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuditableContract
{
    use Auditable;
    use HasFactory;
    use HasRoles;
    use Impersonate;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
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

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
    public function getPermissionAttribute()
    {
        return $this->getAllPermissions();
    }

    public function usersroles()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function subTeam()
    {
        return $this->belongsTo(Team::class, 'sub_team_id', 'id');
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = env('DATETIME_FORMAT');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = env('DATETIME_FORMAT');

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
            $userIds = self::where('manager_id', $this->id)->get()->pluck('id');

            return $userIds->implode(',');
        } else {
            return 0;
        }
    }

    public function hasTeam(...$teams)
    {
        return $this->teams->whereIn('name', $teams)->isNotEmpty();
    }

    public function isLeadPool()
    {
        return Auth::user()->hasRole(RolesEnum::LeadPool);
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
        return Auth::user()->hasAnyRole([RolesEnum::CarRenewalAdvisor, RolesEnum::HealthRenewalAdvisor, RolesEnum::HomeRenewalAdvisor, RolesEnum::LifeRenewalAdvisor, RolesEnum::GMRenewalAdvisor, RolesEnum::CorpLineRenewalAdvisor, RolesEnum::PetRenewalAdvisor]);
    }

    public function isRenewalManager()
    {
        return Auth::user()->hasAnyRole([RolesEnum::CarRenewalManager, RolesEnum::HealthRenewalManager, RolesEnum::HomeRenewalManager, RolesEnum::LifeRenewalManager, RolesEnum::GMRenewalManager, RolesEnum::CorpLineRenewalManager, RolesEnum::PetRenewalManager]);
    }

    public function isNewBusinessManager()
    {
        return Auth::user()->hasAnyRole([RolesEnum::PetNewBusinessManager]);
    }

    public function isNewBusinessAdvisor()
    {
        return Auth::user()->hasAnyRole([RolesEnum::CarNewBusinessAdvisor]);
    }

    public function isHealthManager()
    {
        return Auth::user()->hasRole(RolesEnum::HealthManager);
    }

    public function isCarManager()
    {
        return Auth::user()->hasRole(RolesEnum::CarManager);
    }

    public function isCarAdvisor()
    {
        return Auth::user()->hasRole(RolesEnum::CarAdvisor);
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
        return Auth::user()->hasRole(RolesEnum::Admin);
    }

    public function isEngineer()
    {
        return Auth::user()->hasRole(RolesEnum::Engineering);
    }

    public function isSeniorManagement()
    {
        return Auth::user()->hasRole(RolesEnum::SeniorManagement);
    }

    public function isDepartmentManager()
    {
        return Auth::user()->can(PermissionsEnum::DEPARTMENT_MANAGER);
    }

    public function getUserTeams($userId)
    {
        $userTeamIds = UserTeams::where('user_id', $userId)->get()->pluck('team_id');

        return Team::whereIn('id', $userTeamIds)->get()->pluck('name');
    }

    public function hasMyLeadAccess()
    {
        return Auth::user()->hasAnyRole([
            RolesEnum::Admin,
            RolesEnum::BusinessAdvisor,
            RolesEnum::HealthAdvisor,
            RolesEnum::HomeAdvisor,
            RolesEnum::LifeAdvisor,
            RolesEnum::TravelAdvisor,
            RolesEnum::GMAdvisor,
            RolesEnum::RMAdvisor,
            RolesEnum::CorpLineAdvisor,
            RolesEnum::EBPAdvisor,
            RolesEnum::HealthRenewalAdvisor,
            RolesEnum::TravelAdvisor,
            RolesEnum::HealthRenewalAdvisor,
            RolesEnum::LifeRenewalAdvisor,
            RolesEnum::HomeRenewalAdvisor,
            RolesEnum::GMRenewalAdvisor,
            RolesEnum::CorpLineRenewalAdvisor,
            RolesEnum::PetRenewalAdvisor,
            RolesEnum::CarRenewalAdvisor,
            RolesEnum::PetAdvisor,
        ]);
    }

    public function hasPolicyIssuanceAccess()
    {
        return Auth::user()->hasAnyRole([
            RolesEnum::Advisor,
            RolesEnum::PA,
            RolesEnum::Payment,
            RolesEnum::Invoicing,
            RolesEnum::ProductionApprovalManager,
        ]);
    }

    public function getUserRoles()
    {
        return self::select(['id', 'name'])->whereHas(
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

    public function getUserEmailOrDefault()
    {
        if (auth()->user() && auth()->user()->email) {
            return auth()->user()->email;
        } else {
            return User::where('name', 'System User')->first()->email;
        }
    }

    /**
     * get renewal batches segment wise for a particularadvisors function
     */
    public function renewalBatch(): BelongsToMany
    {
        return $this->belongsToMany(
            RenewalBatch::class,
            'renewal_batch_segment_user',
            'advisor_id',
            'renewal_batch_id',
            'id',
            'id',
            'renewalBatch'
        )->withTimestamps()->withPivot('segment_type');
    }

    /**
     * get user all teams function
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'user_team',
            'user_id',
            'team_id',
            'id',
            'id',
            'teams'
        )->withTimestamps()->withPivot('manager_id');
    }

    /**
     * @return mixed
     */
    public function scopeActiveUser($query)
    {
        return $query->where('users.is_active', 1);
    }

    /**
     * get user all teams id function
     *
     * @param  int  $userId
     */
    public function getUserTeamsIds($userId): Collection
    {
        $userTeamIds = UserTeams::where('user_id', $userId)->get()->pluck('team_id');

        return Team::whereIn('id', $userTeamIds)->get()->pluck('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_manager', 'user_id', 'manager_id')->select(['user_id', 'name', 'email']);
    }

    public function sessions()
    {
        return $this->hasMany(Sessions::class);
    }

    public function products()
    {
        return $this->hasMany(UserProducts::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->select('id', 'name');
    }

    public function advisors()
    {
        return $this->hasMany(InslyAdvisor::class);
    }

    public function businessTypes()
    {
        return $this->belongsToMany(BusinessTypeOfInsurance::class, 'business_type_of_insurance_user', 'user_id', 'business_type_of_insurance_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'user_departments', 'user_id', 'department_id');
    }

    public function isValueUser(QuoteTypes $quoteType): bool
    {
        if ($quoteType === QuoteTypes::HEALTH) {
            return $this->hasTeam(TeamNameEnum::RM_SPEED);
        }

        return strtolower($this->subTeam?->name) === strtolower(TeamNameEnum::VALUE);
    }

    public function isVolumeUser(QuoteTypes $quoteType): bool
    {
        if ($quoteType === QuoteTypes::HEALTH) {
            return $this->hasTeam(TeamNameEnum::EBP);
        }

        return strtolower($this->subTeam?->name) === strtolower(TeamNameEnum::VOLUME);
    }

    public function scopeIsValueUser($q, QuoteTypes $quoteType)
    {
        if ($quoteType === QuoteTypes::HEALTH) {
            $q->whereHas('teams', function ($q) {
                $q->where('name', 'like', TeamNameEnum::RM_SPEED);
            });
        } else {
            $q->whereHas('subTeam', function ($q) {
                $q->where('name', 'like', TeamNameEnum::VALUE);
            });
        }
    }

    public function scopeIsVolumeUser($q, QuoteTypes $quoteType)
    {
        if ($quoteType === QuoteTypes::HEALTH) {
            $q->whereHas('teams', function ($q) {
                $q->where('name', 'like', TeamNameEnum::EBP);
            });
        } else {
            $q->whereHas('subTeam', function ($q) {
                $q->where('name', 'like', TeamNameEnum::VOLUME);
            });
        }
    }

    public function scopeChs($query)
    {
        return $query->where('email', PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL);
    }
}
