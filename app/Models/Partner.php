<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;


class Partner extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'partner';

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($partner) { // before delete() method call this
            $partner->rewards->each->delete();
             // do the rest of the cleanup...
        });
    }
}
