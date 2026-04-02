<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\MyAlfredWelcomeEmailInboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMyAlfredWelcomeEmailSqsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $email,
        public string $code,
        public ?string $source = null,
        public ?string $tag = null,
    ) {
        $this->onConnection('sqs');
    }

    public function handle(MyAlfredWelcomeEmailInboundService $welcomeEmailInboundService): void
    {
        $welcomeEmailInboundService->process(
            $this->email,
            $this->code,
            $this->source,
            $this->tag,
        );
    }
}
