<?php

namespace App\Console\Commands;

use App\Imports\PDMigrations\HealthQuoteImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class HealthDataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'HealthDataMigration:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePaths = [
            // 'health-part1.xlsx',
            // 'health-part2.xlsx',
            // 'health-part3.xlsx',
            // 'health-part4.xlsx',
            // 'health-part5.xlsx',
            // 'health-part6.xlsx',
            // 'health-part7.xlsx',
            'health-new.xlsx',
        ];

        foreach ($filePaths as $filePath) {
            if (!Storage::disk('pdmigrations')->exists($filePath)) {
                Log::error('File does not exist: ' . $filePath);

                continue;
            }

            $fullPath = Storage::disk('pdmigrations')->path($filePath);

            try {
                Log::info('Health Quote data migrations started.');

                Excel::import(new HealthQuoteImport, $fullPath);

                Log::info('Health Quote data migrations succeeded');
            } catch (\Exception $e) {
                Log::error('Error importing file: ' . $e->getMessage());
            }
        }
    }
}
