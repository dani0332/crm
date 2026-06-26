<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SendManagerDeactivationAttemptEmailJob
 *
 * Sends an email notification to IT support when a manager deactivation is attempted
 * while they still have subordinates.
 */
class SendManagerDeactivationAttemptEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    private int $deactivatingUserId;
    private int $attemptedByUserId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $deactivatingUserId, int $attemptedByUserId)
    {
        $this->deactivatingUserId = $deactivatingUserId;
        $this->attemptedByUserId = $attemptedByUserId;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info('SendManagerDeactivationAttemptEmailJob Started');
        try {
            $managerUser = User::query()
                ->with('managers:email')
                ->select(['id', 'name', 'email'])
                ->find($this->deactivatingUserId);

            $attemptedBy = User::query()
                ->select(['id', 'name', 'email'])
                ->find($this->attemptedByUserId);

            if (! $managerUser || ! $attemptedBy) {
                LoggerService::error('Manager deactivation attempt email skipped: user not found', [
                    'manager_id' => $this->deactivatingUserId,
                    'attempted_by' => $this->attemptedByUserId,
                ]);

                return;
            }

            $managerPayload = collect([$managerUser->only(['id', 'name', 'email', 'managers'])]);
            $attemptedByPayload = (object) $attemptedBy->only(['id', 'name', 'email']);

            LoggerService::info('Sending manager deactivation attempt email', [
                'manager_id' => $managerPayload,
                'attempted_by' => $attemptedByPayload->id,
            ]);

            app(SendEmailCustomerService::class)->sendManagerDeactivationAttemptEmail(
                $managerPayload,
                $attemptedByPayload
            );

            LoggerService::info(
                'Manager deactivation attempt email sent successfully',
                [],
                [
                    'manager_id' => $this->deactivatingUserId,
                    'attempted_by' => $this->attemptedByUserId,
                ]
            );

        } catch (Exception $e) {
            LoggerService::error('Error sending manager deactivation attempt email', [
                'manager_id' => $this->deactivatingUserId,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }
}
