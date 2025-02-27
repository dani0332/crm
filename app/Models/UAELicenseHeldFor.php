<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UAELicenseHeldFor extends BaseModel
{
    use HasFactory;

    protected $table = 'uae_license_held_for';

    /**
     * scope to get active records
     *
     * @return mixed
     */
    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeIsBackHomeActive($query)
    {
        $query->where('is_back_home_license_active', 1);
    }
}
