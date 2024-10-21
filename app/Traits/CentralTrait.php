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
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                // if plan is not provided, then send ecom quote link
                $link = $afiaWebDomain.'/bike-insurance/quote/'.$uuid;
                if ($plan) {
                    // if plan is provided, then send buy now link
                    $link = $link.'/payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;
                }

                return $link;
        }
    }

    /**
     * Create a temporary PDF file for watermarking
     *
     * @param string $pdfFile
     * @return File
     */
    public function createTempPdfFileForWatermark($pdfFile): File
    {
        if (! file_exists(storage_path('/app/temp'))) {
            mkdir(storage_path('/app/temp'), 0775, true);
        }
        // Create a temporary file and write the PDF content to it
        $tempDir = storage_path('app/temp');

        // Generate a unique filename for the temp PDF file
        $tempFileName = 'pdf_'.uniqid().'.pdf';
        $tempFilePath = $tempDir.'/'.$tempFileName;

        // Create the temporary file and write the PDF content to it
        file_put_contents($tempFilePath, $pdfFile);

        // Return a new File instance pointing to the temporary file
        return new File($tempFilePath);
    }
}
