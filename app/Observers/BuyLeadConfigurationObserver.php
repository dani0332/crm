<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class BuyLeadConfigurationObserver
{
    public function creating($model)
    {
        $model->created_by = Auth::id();
    }

    public function updating($model)
    {
        $model->updated_by = Auth::id();
    }
}
