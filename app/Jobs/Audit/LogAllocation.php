<?php

namespace App\Jobs\Audit;

use App\Enums\QuoteTypes;
use App\Models\Audit\AllocationAudit;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Facades\Auth;

class LogAllocation implements ShouldQueue
{
    use Queueable;

    public ?User $user = null;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations] protected Model $model,
        protected ?QuoteTypes $quoteType = null
    ) {
        if (Auth::check()) {
            $this->user = Auth::user();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AllocationAudit::log(
            $this->model,
            $this->quoteType,
            $this->user
        );
    }
}
