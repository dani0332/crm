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
            'corpline-1.xlsx',
            'corpline-2.xlsx',
            'corpline-3.xlsx',
            'corpline-4.xlsx',
            'corpline-5.xlsx',
            'corpline-6.xlsx',
            'corpline-7.xlsx',
            'corpline-8.xlsx',
            'corpline-9.xlsx',
        ];

        foreach ($filePaths as $filePath) {
            if (! Storage::disk('pdmigrations')->exists($filePath)) {
                Log::error('File does not exist: '.$filePath);

                continue;
            }

            $fullPath = Storage::disk('pdmigrations')->path($filePath);

            try {
                Log::info('Business Quote data migrations started.');

                Excel::import(new BusinessQuoteImport, $fullPath);

                Log::info('Business Quote data migrations succeeded.');
            } catch (\Exception $e) {
                Log::error('Error importing file: '.$e->getMessage());
            }
        }
    }
}
