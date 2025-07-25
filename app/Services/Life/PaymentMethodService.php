<?php

namespace App\Services\Life;

use App\Models\PaymentMethod;
use App\Services\BaseService;

class PaymentMethodService extends BaseService
{
    public function getAll()
    {
        return PaymentMethod::select('code', 'name', 'parent_code', 'tool_tip')->orderBy('name')->get();
    }
}
