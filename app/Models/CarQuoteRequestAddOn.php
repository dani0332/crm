<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarQuoteRequestAddOn extends Model
{
    use HasFactory;

    protected $table = 'car_quote_request_addon';
    protected $guarded = [];
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
        ],
        'list' => [
            'pa' => ['id', 'quote_request_id', 'addon_option_id', 'price'],
            'advisor' => ['id', 'quote_request_id', 'addon_option_id', 'price'],
            'oe' => ['id', 'quote_request_id', 'addon_option_id', 'price'],
            'admin' => ['id', 'quote_request_id', 'addon_option_id', 'price'],
            'invoicing' => ['id', 'quote_request_id', 'addon_option_id', 'price'],
        ],
    ];

    public function addon_option_id()
    {
        return $this->hasOne(CarAddOnOption::class, 'id', 'addon_option_id')->select(['id', 'value', 'addon_id', 'price']);
    }

    public function relations()
    {
        return ['addon_option_id'];
    }
}
