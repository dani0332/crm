<?php

namespace App\Services\Life;

use App\Services\BaseService;
use App\Models\PaymentMethod;

class PaymentMethodService extends BaseService
{
    public function getAll()
    {
        return PaymentMethod::select('code', 'name', 'parent_code', 'tool_tip')->orderBy('name')->get();
    }
}
