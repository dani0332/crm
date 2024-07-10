<?php

namespace App\Console\Commands;

use App\Imports\PDMigrations\PersonalQuoteImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PersonalQuoteDataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PersonalQuoteDataMigration:cron';

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
            'personal.xlsx',
        ];

        foreach ($filePaths as $filePath) {
            if (!Storage::disk('pdmigrations')->exists($filePath)) {
                Log::error('File does not exist: ' . $filePath);

                continue;
            }

            $fullPath = Storage::disk('pdmigrations')->path($filePath);

            try {
                Log::info('Personal Quote data migrations started.');

                Excel::import(new PersonalQuoteImport, $fullPath);

                Log::info('Personal Quote data migrations succeeded.');
            } catch (\Exception $e) {
                Log::error('Error importing file: ' . $e->getMessage());
            }
        }
    }
}
