<?php

namespace App\Console\Commands;

use App\Imports\PDMigrations\BusinessQuoteImport;
use Illuminate\Console\Command;
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
            'Corpline_Part1.xlsx',
            'Corpline_Part2.xlsx',
            'Corpline_Part3.xlsx',
            'Corpline_Part4.xlsx',
        ];

        foreach ($filePaths as $filePath) {
            $fullPath = storage_path('app/PDMigrations/'.$filePath);

            if (! Storage::disk('pdmigrations')->exists($filePath)) {
                \Log::error('File does not exist: '.$filePath);

                continue;
            }

            $fullPath = Storage::disk('pdmigrations')->path($filePath);

            try {
                \Log::info('Business Quote data migrations started.');

                Excel::import(new BusinessQuoteImport, $fullPath);

                \Log::info('Business Quote data migrations succeeded.');
            } catch (\Exception $e) {
                \Log::error('Error importing file: '.$e->getMessage());
            }
        }
    }
}
