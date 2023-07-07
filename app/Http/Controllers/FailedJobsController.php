<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Artisan;
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
            info('failed job : '.json_encode($failedJob));
        }

        return DataTables::of($failedJobs)->make(true);
    }

    public function clearCache()
    {
        if (auth()->user()->hasRole(RolesEnum::Engineering)) {
            Artisan::call('cache:clear');
            Artisan::call('view:cache');
            Artisan::call('config:cache');

            return '<h1>All cache cleared and optimized</h1>';
        } else {
            throw new AuthorizationException('Access Denied');
        }
    }
}
