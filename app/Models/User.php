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
        $isManager = $this->hasRole('MANAGER');
        if ($isManager) {
            $team_members = UserTeams::where('manager_id', $this->id)->get();
            $user_ids = $team_members->pluck('user_id');
            $users = User::whereIn('id', $user_ids)->get();
            $userIds = $users->pluck('id');
            return $userIds->implode(',');
        } else {
            return 0;
        }
    }

    public function getUserTeams($userId)
    {
        $userTeams = UserTeams::where('user_id', $userId)->get();
        $teamIds = $userTeams->pluck('team_id');
        $teams = Team::whereIn('id', $teamIds)->get();
        return $teams->pluck('name');
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

        if(Auth::user()->hasRole('advisor')) {

            $users =  User::select(['id', 'name'])->whereHas(
                'roles', function($q) use($filters) {
                    foreach($filters as $key => $value) {
                        $q->where($key,$value);
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
