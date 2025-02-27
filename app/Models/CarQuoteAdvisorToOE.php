<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteAdvisorToOE extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_assign_oe_to_advisor';
    public $access = [

        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => ['oe_id', 'advisor_id'],
            'invoicing' => [],
            'payment' => [],

        ],
        'list' => [
            'pa' => ['id', 'oe_id', 'advisor_id'],
            'advisor' => ['id', 'oe_id', 'advisor_id'],
            'oe' => ['id', 'oe_id', 'advisor_id'],
            'admin' => ['id', 'oe_id', 'advisor_id'],
            'invoicing' => ['id', 'oe_id', 'advisor_id'],
            'payment' => ['id', 'oe_id', 'advisor_id'],
        ],
    ];

    public function advisor_id()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name']);
    }

    public function oe_id()
    {
        return $this->hasOne(User::class, 'id', 'oe_id')->select(['id', 'email', 'name']);
    }

    public function relations()
    {
        return ['advisor_id', 'oe_id'];
    }

    public function saveForm($request, $update = false)
    {
        try {
            if (self::where($request->all())->exists()) {
                return $this->APIController->respondData(['message' => 'This relation already exists.'], 500);
            }

            CarQuote::where('advisor_id', $request->input('advisor_id'))->update(['oe_id' => $request->input('oe_id')]);

            return parent::saveForm($request, $update);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
