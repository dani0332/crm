<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class RestartHaystack extends Command
{
    protected $signature = 'haystack:restart';
    protected $description = 'Restart a stopped haystack process';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info('Haystack process is about to be restarted');

        $haystack = Haystack::whereNull('finished_at')
            ->whereNull('resume_at')
            ->where('started_at', '!=', null)
            ->where('created_at', '>=', Carbon::parse('20-oct-2024'))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($haystack) {
            if ($haystack->bales()->count() > 0) {
                $haystack->restart();
                $haystack->resume_at = Carbon::now();
                $haystack->save();
                $this->info('Haystack process restarted successfully.');

                return;
            } else {
                info('HayStack:' . ' ---  No bales found for the haystack process.');
            }
        } else {
            info('HayStack:' . ' ---  process not found or already finished.');
        }
    }
}
