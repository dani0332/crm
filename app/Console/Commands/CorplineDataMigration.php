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
        // Define an array with the file paths
        $filePaths = [
            'Corpline_Part1.xlsx',
            'Corpline_Part2.xlsx',
            'Corpline_Part3.xlsx',
            'Corpline_Part4.xlsx',
        ];

        foreach ($filePaths as $filePath) {
            if (!Storage::disk('local')->exists($filePath)) {
                \Log::error('File does not exist: ' . $filePath);

                continue;
            }

            $fullPath = Storage::disk('local')->path($filePath);

            try {
                Excel::import(new BusinessQuoteImport, $fullPath);
            } catch (\Exception $e) {
                \Log::error('Error importing file: ' . $e->getMessage());
            }
        }
    }
}
