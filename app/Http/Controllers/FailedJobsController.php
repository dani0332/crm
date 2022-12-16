<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Yajra\DataTables\Facades\DataTables;

class FailedJobsController extends Controller
{
    public function index()
    {
            $failedJobs = [];

            $redis = Redis::connection();
            $failedJobIds = $redis->lrange('queues:failed', 0, -1);

            foreach ($failedJobIds as $failedJobId) {
                $failedJob = json_decode($redis->get("queues:failed:{$failedJobId}"), true);
                $failedJobs[] = $failedJob;
                info('failed job : ' . json_encode($failedJob));
            }
        return DataTables::of($failedJobs)->make(true);
    }
}
