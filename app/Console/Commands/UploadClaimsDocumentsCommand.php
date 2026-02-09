<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ClaimsDocumentUploadUtility;
use Illuminate\Console\Command;

/**
 * One-time command to upload documents from claims folder to Azure Storage
 * and insert records into generic_documents table.
 */
class UploadClaimsDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'claims:upload-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload documents from claims folder to Azure Storage and insert into database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting claims document upload process...');
        $this->newLine();

        $utility = new ClaimsDocumentUploadUtility();
        $stats = $utility->processAllDocuments();

        $this->info('Upload process completed!');
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $stats['processed']],
                ['Successfully Uploaded', $stats['uploaded']],
                ['Failed', $stats['failed']],
            ]
        );

        if (! empty($stats['errors'])) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($stats['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        return $stats['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

