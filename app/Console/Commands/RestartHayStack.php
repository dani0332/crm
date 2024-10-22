<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class RestartHaystack extends Command
{
    protected $signature = 'haystack:restart';
    protected $description = 'Restart a stopped haystack process';

    public function handle()
    {
        $haystack = Haystack::whereNull('finished_at')
            ->whereNull('resume_at')
            ->where('started_at', '!=', null)
            ->first();

        if ($haystack) {
            if($haystack->bales()->count() > 0) {
                if(Carbon::now()->timezone(config('app.timezone'))->diffInMinutes($haystack->updated_at) <= 10) {
                    $haystack->restart();
                    $haystack->resume_at = Carbon::now();
                    $haystack->save();
                    $this->info('Haystack process restarted successfully.');
                    return;

                } 
            } else {
                $this->error('No bales found for the haystack process.');
            }
        } else {
            $this->error('Haystack process not found or already finished.');
        }
    }
}
