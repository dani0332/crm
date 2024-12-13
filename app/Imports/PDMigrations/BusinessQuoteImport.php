<?php

namespace App\Imports\PDMigrations;

use App\Models\CarQuote;
use App\Models\HealthQuote;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BusinessQuoteImport implements ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        if ((isset($row['quote_id']) && isset($row['created_at']))) {
            $carbonDate = Carbon::parse($row['created_at']);

            $lead = CarQuote::with('carQuoteRequestDetail')->where('uuid', $row['quote_id'])->first();
            // $lead = HealthQuote::with('healthQuoteRequestDetail')->where('uuid', $row['quote_id'])->first();

            if ($lead) {
                $quoteRequestDetail = $lead->carQuoteRequestDetail;
                // $quoteRequestDetail = $lead->healthQuoteRequestDetail;

                $existingDate = $quoteRequestDetail->chat_initiated_at;

                if (is_null($existingDate) || $carbonDate->lessThan(Carbon::parse($existingDate))) {
                    $quoteRequestDetail->update(['chat_initiated_at' => $carbonDate->format('Y-m-d H:i:s')]);
                    info('InstantChatMigrationCSV - '.$row['quote_type'].' - Quote found: '.$lead->uuid.' - Quote updated');
                } else {
                    info('InstantChatMigrationCSV - '.$row['quote_type'].' - Chat_initiated_at is already set: '.$row['quote_id']);
                }
            } else {
                info('InstantChatMigrationCSV - '.$row['quote_type'].' - Quote not found: '.$row['quote_id']);
            }

        }
    }

    public function chunkSize(): int
    {
        return 1500;
    }
}
