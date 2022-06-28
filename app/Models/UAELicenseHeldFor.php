<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class UAELicenseHeldFor extends BaseModel
{
    use HasFactory;
    protected $table = 'uae_license_held_for';
    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, 'uae_license_held_for', ['code', 'id', 'text', 'text_ar']);
    }
}
