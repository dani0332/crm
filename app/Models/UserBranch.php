<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserBranch extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'user_branches';
    protected $fillable = ['user_id', 'branch_id', 'is_primary', 'effective_from', 'effective_to', 'status'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => User::class,
            'show_auditables' => false,
            'relations' => [
                [
                    'auditable_type' => UserBranch::class,
                    'key' => 'user_id',
                    'relation' => 'many',
                ],
            ],
        ];
    }

    public function transformAuditables($data): array
    {
        $audit = &$data['audit'];
        $transformedOld = &$data['transformedOld'];
        $transformedNew = &$data['transformedNew'];

        $auditableId = $audit->auditable_id;
        if (! empty($auditableId)) {
            $transformedNew['id'] = $auditableId;
            if ($audit->event == 'updated') {
                $transformedOld['id'] = $auditableId;
            }
        }

        $userbranch = $this->where('id', $auditableId)->with('branch')->first();
        if ($audit->event == 'created') {
            $audit->event = 'Added Branch';
            $transformedNew['notes'] = 'New branch added '.$userbranch?->branch?->name;
        } else {
            if (isset($transformedNew['is_primary']) && $transformedNew['is_primary'] == 1) {
                $audit->event = 'Made Primary Branch';
                $transformedNew['notes'] = $userbranch?->branch?->name.' was made primary branch';
            } elseif (isset($transformedNew['is_primary']) && $transformedNew['is_primary'] == 0) {
                $audit->event = 'Removed Primary Branch';
                $transformedNew['notes'] = $userbranch?->branch?->name.' was removed as primary branch';
            } elseif (isset($transformedNew['status']) && $transformedNew['status'] == 0) {
                $audit->event = 'Removed Branch';
                $transformedNew['notes'] = $userbranch?->branch?->name.' branch was removed';
            }
        }

        return [
            'audit' => $audit,
            'transformedOld' => $transformedOld,
            'transformedNew' => $transformedNew,
        ];
    }
}
