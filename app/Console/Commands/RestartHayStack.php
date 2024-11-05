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

        $haystacks = Haystack::whereNotNull('started_at')
            ->where('created_at', '>=', Carbon::parse('5-nov-2024'))
            ->whereHas('bales')
            ->whereHas('data', function ($query) {
                $query->where('key', 'count');
            })
            ->get();

        foreach ($haystacks as $haystack) {
            $countExist = optional($haystack->data->where('key', 'count')->first())->updated_at;

            if ($haystack->bales()->exists() && $countExist && $countExist->diffInMinutes(Carbon::now()) > 20) {
                $haystack->restart();
                $haystack->resume_at = Carbon::now();
                $haystack->save();

                info('HayStack: --- process restarted successfully. Process ID:' . $haystack->id);
                $this->info('Haystack process restarted successfully. Process ID: ' . $haystack->id);
                return;
            } else {
                info('HayStack: --- No bales found or currently haystack is been running. Process ID:' . $haystack->id);
            }
        }
        info('HayStack:' . ' ---  No Haystacks found to process.');
    }
}
