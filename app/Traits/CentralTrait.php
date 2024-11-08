<?php

namespace App\Traits;

use App\Enums\QuoteTypes;
use Illuminate\Http\File;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function getEcomQuoteLink(QuoteTypes $quoteType, string $uuid, ?object $plan = null): string
    {
        if (! $quoteType || ! $uuid) {
            info('Failed to generate Buy Now/Ecom Quote link for quote.', [
                'quoteType' => $quoteType,
                'uuid' => $uuid,
            ]);

            return '';
        }
        $afiaWebDomain = config('constants.AFIA_WEBSITE_DOMAIN');
        $basePath = '';
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                $basePath = '/bike-insurance/quote/';
                break;
            case QuoteTypes::HOME->id():
                $basePath = '/home-insurance/quote/';
                break;
            default:
                return '';
        }
        // Base link with trailing slash
        $link = rtrim($afiaWebDomain, '/').$basePath.$uuid.'/';

        // Append plan parameters if the plan is provided
        if ($plan) {
            $link .= 'payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;
        }

        return $link;
    }

    /**
     * Create a temporary PDF file for watermarking
     *
     * @param  string  $pdfFile
     */
    public function createTempPdfFileForWatermark($pdfFile): File
    {
        if (! file_exists(storage_path('temp'))) {
            mkdir(storage_path('temp'), 0775, true);
        }
        // Create a temporary file and write the PDF content to it
        $tempDir = storage_path('temp');

        // Generate a unique filename for the temp PDF file
        $tempFileName = 'pdf_'.uniqid().'.pdf';
        $tempFilePath = $tempDir.'/'.$tempFileName;

        // Create the temporary file and write the PDF content to it
        file_put_contents($tempFilePath, $pdfFile);

        // Return a new File instance pointing to the temporary file
        return new File($tempFilePath);
    }
}
