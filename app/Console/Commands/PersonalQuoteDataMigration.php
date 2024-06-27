<?php

namespace App\Console\Commands;

use App\Imports\PersonalQuoteImport;
use Illuminate\Console\Command;
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
        $filePath = 'personal.xlsx';

        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception('File does not exist: ' . $filePath);
        }

        $fullPath = Storage::disk('local')->path($filePath);

        try {
            Excel::import(new PersonalQuoteImport, $fullPath);
        } catch (\Exception $e) {
            \Log::error('Error importing file: ' . $e->getMessage());
        }
    }
}
