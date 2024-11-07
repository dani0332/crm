<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RateCoverageUpload extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = ['file_name', 'file_path', 'total_records', 'cannot_upload', 'good', 'status', 'type'];
}
