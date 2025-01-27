<?php

namespace App\Console\Commands;

use App\Imports\PDMigrations\BusinessQuoteImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CorplineDataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CorplineDataMigration:cron';

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
            'instant_alfred_car.csv',
            // 'instant_alfred_health.csv',
        ];

        foreach ($filePaths as $filePath) {
            if (! Storage::disk('instantchat')->exists($filePath)) {
                Log::error('File does not exist: '.$filePath);

                continue;
            }

            $fullPath = Storage::disk('instantchat')->path($filePath);

            try {
                Log::info('InstantChatMigrationCSV - data migrations started.');

                Excel::import(new BusinessQuoteImport, $fullPath);

                Log::info('InstantChatMigrationCSV - data migrations succeeded.');
            } catch (\Exception $e) {
                Log::error('Error importing file: '.$e->getMessage());
            }
        }
    }
}
