<?php

namespace App\Services;

use App\Console\Commands\Common\Batchable;

class AddBatchForNonMotors extends BaseService
{
    use Batchable;

    public function handle()
    {
        $this->startProcessing('2024-07-29');
    }
}
