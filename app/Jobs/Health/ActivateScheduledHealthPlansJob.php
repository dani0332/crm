<?php

namespace App\Jobs\Health;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ActivateScheduledHealthPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;

    public function handle(): void
    {
        LoggerService::info(self::class.' - Started processing scheduled health plans');

        $today = Carbon::today();

        $this->archiveExpiredControls($today);
        $this->activateScheduledControls($today);

        LoggerService::info(self::class.' - Finished processing scheduled health plans');
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::warning(self::class.' - Job failed after '.$this->attempts().' attempt(s)', exception: $exception);
    }

    private function archiveExpiredControls(Carbon $today): void
    {
        $expiredControls = HealthRateControl::with('healthPlan')
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->whereDate('effective_to', '<', $today)
            ->get();

        if ($expiredControls->isEmpty()) {
            return;
        }

        $controlIds = $expiredControls->pluck('id');
        $planIds = $expiredControls->pluck('healthPlan')->pluck('id')->unique();

        DB::transaction(function () use ($controlIds, $planIds) {
            HealthRate::whereIn('health_rate_control_id', $controlIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            HealthRateControl::whereIn('id', $controlIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            HealthPlan::whereIn('id', $planIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);
        });

        LoggerService::info(self::class." - Archived expired rate control IDs: [{$controlIds->implode(', ')}], plan IDs: [{$planIds->implode(', ')}]");
    }

    private function activateScheduledControls(Carbon $today): void
    {
        $scheduledControls = HealthRateControl::with('healthPlan')
            ->where('status', HealthPlanRateSheetStatusEnum::SCHEDULED->value)
            ->whereDate('effective_from', $today)
            ->get();

        if ($scheduledControls->isEmpty()) {
            return;
        }

        $planCodes = $scheduledControls->pluck('healthPlan.code')->values();

        $activePlansByCode = HealthPlan::whereIn('code', $planCodes)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->with('activeRateControl')
            ->get()
            ->keyBy('code');

        foreach ($scheduledControls as $rateControl) {
            $plan = $rateControl->healthPlan;

            if (! $plan) {
                continue;
            }

            $activePlan = $activePlansByCode->get($plan->code);

            DB::transaction(function () use ($plan, $rateControl, $today, $activePlan) {
                $this->archiveCurrentlyActivePlan($plan, $today, $activePlan);

                $prevPlanVersion = $plan->version;
                $prevControlVersion = $rateControl->version;
                $newVersion = $this->deriveMajorVersion($rateControl->version);

                $rateControl->rates()->update([
                    'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
                    'version' => $newVersion,
                ]);

                $rateControl->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $rateControl->version = $newVersion;
                $rateControl->save();

                if ($plan->status === HealthPlanRateSheetStatusEnum::SCHEDULED->value) {
                    $plan->version = $newVersion;
                    $plan->parent_id = null;
                }

                $plan->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $plan->save();

                LoggerService::info(self::class." - Activated health plan ID: {$plan->id}, rate control ID: {$rateControl->id}", [
                    'plan_version' => ['previous' => $prevPlanVersion, 'new' => $plan->version],
                    'rate_control_version' => ['previous' => $prevControlVersion, 'new' => $newVersion],
                ]);
            });
        }
    }

    private function archiveCurrentlyActivePlan(
        HealthPlan $incomingPlan,
        Carbon $today,
        ?HealthPlan $activePlan,
    ): void {
        if (! $activePlan) {
            return;
        }

        $activeControl = $activePlan->activeRateControl;
        if ($activeControl) {
            $activeControl->rates()->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            $activeControl->effective_to = $today->copy()->subDay();
            $activeControl->status = HealthPlanRateSheetStatusEnum::ARCHIVED->value;
            $activeControl->save();
        }

        if ($activePlan->id !== $incomingPlan->id) {
            $activePlan->status = HealthPlanRateSheetStatusEnum::ARCHIVED->value;
            $activePlan->parent_id = $incomingPlan->id;
            $activePlan->save();

            LoggerService::info(self::class." - Archived previously active plan ID: {$activePlan->id}");
        }
    }

    private function deriveMajorVersion(float $version): float
    {
        return (float) (ceil($version).'.0');
    }
}
