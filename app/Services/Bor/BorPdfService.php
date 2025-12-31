<?php

namespace App\Services\Bor;

use App\Models\BorLog;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BorPdfService
{
    /**
     * Generate a BOR PDF document with embedded signature
     */
    public function generateTemporaryBorPdf(BorLog $borLog): ?string
    {
        try {
            // Get lead information
            $lead = $borLog->personalQuote;
            if (! $lead) {
                throw new \Exception('Lead information not found for BOR log ID: '.$borLog->id);
            }

            if ($borLog->date_uploaded && $borLog->date_uploaded != null) {
                $document = $borLog->document;

                return Storage::disk('azureIM')->temporaryUrl($document->doc_url, now()->addMinutes(2));
            }
            // Prepare data for PDF template
            $includeSignature = $borLog->date_signed ? true : false;
            $data = $this->preparePdfData($borLog, $lead, $includeSignature);

            // Generate PDF using blade template
            $pdf = Pdf::loadView('pdf.bor-document', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            // Generate filename
            $filename = $this->generatePdfFilename($borLog);

            // Save PDF to storage
            $pdfContent = $pdf->output();
            $pdfPath = 'bor-documents/'.$filename;
            Storage::disk('azureIM')->put($pdfPath, $pdfContent);

            Log::info('BOR PDF generated successfully', [
                'bor_log_id' => $borLog->id,
                'pdf_path' => $pdfPath,
                'filename' => $filename,
            ]);

            return Storage::disk('azureIM')->temporaryUrl($pdfPath, now()->addMinutes(2));

        } catch (\Exception $e) {
            Log::error('BOR PDF generation failed', [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate a BOR PDF document for preview (before signing)
     */
    public function generatePreviewBorPdf(BorLog $borLog): ?array
    {
        try {
            $lead = $borLog->personalQuote;
            if (! $lead) {
                throw new \Exception('Lead information not found for BOR log ID: '.$borLog->id);
            }

            // Prepare data for PDF template (without signature)
            $includeSignature = $borLog->date_signed ? true : false;
            $data = $this->preparePdfData($borLog, $lead, $includeSignature);
            // Generate PDF
            $pdf = Pdf::loadView('pdf.bor-document', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Generate filename for preview
            $filename = 'preview_'.$this->generatePdfFilename($borLog);

            return ['pdf' => $pdf, 'name' => $filename];
        } catch (\Exception $e) {
            LoggerService::error($e->getMessage(), [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare data for PDF template
     */
    public function preparePdfData(BorLog $borLog, PersonalQuote $lead, bool $includeSignature = true): array
    {
        $document = $borLog->signedDocument;
        $temporaryUrl = null;

        if ($includeSignature && $document) {
            try {
                $filename = urlencode($document->doc_url);
                $disk = Storage::disk('azureIM');
                if (method_exists($disk, 'temporaryUrl')) {
                    $temporaryUrl = $disk->temporaryUrl($filename, now()->addMinutes(2));

                    // For PDF generation, we need to convert the image to base64 data URI
                    // since DomPDF cannot access external URLs directly
                    if ($temporaryUrl) {
                        $signatureBase64 = $this->getSignatureImageFromUrl($temporaryUrl);
                        if ($signatureBase64) {
                            $temporaryUrl = $signatureBase64; // Replace URL with base64 data URI
                        } else {
                            $temporaryUrl = null;
                            $includeSignature = false;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to generate temporary URL for signature', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
                $temporaryUrl = null;
                $includeSignature = false;
            }
        }
        $data = [
            // BOR Information
            'bor_log' => $borLog,
            'bor_reference' => $borLog->bor_reference ?? Str::upper(Str::random(10)),
            'created_date' => $borLog->created_at,

            // Customer Information
            'customer_type' => $borLog->customer_type,
            'customer_name' => $borLog->insurer_name ?? ($lead->first_name.' '.$lead->last_name) ?? null,
            'company_name' => $borLog->company_name ?? $lead->company_name ?? null,
            'customer_email' => $lead->email,
            'customer_phone' => $lead->phone,

            // Lead Information
            'lead' => $lead,
            'lob' => strtoupper($lead->lob ?? 'GENERAL'),
            'quote_reference' => $lead->quote_reference ?? $lead->uuid,

            // Insurance Information
            'insurance_company' => $borLog->insuranceProvider->text ?? null,
            'policy_number' => $borLog->policy_number,
            'policy_expiry' => $borLog->policy_expiry ? $borLog->policy_expiry : null,
            'chassis_number' => $borLog->chasis_number,

            // Signature Information
            'include_signature' => $includeSignature && $temporaryUrl,
            'signature_path' => $includeSignature ? $temporaryUrl : null,
            'signature_name' => $borLog->customer_signature_name ?? $borLog->insurer_name ?? $borLog->company_name,
            'date_signed' => Carbon::parse($borLog->date_signed)->format('d/m/Y'), // Already formatted as string by model accessor
            'date_signed_time' => Carbon::parse($borLog->date_signed)->format('H:i:s'),
            'document_id' => $borLog->document_id,
            'user_agent' => $borLog->user_agent,

            // Additional Information
            'current_date' => now()->format('d F Y'),
            'current_time' => now()->format('h:i A'),
        ];

        return $data;
    }

    /**
     * Generate PDF filename
     */
    private function generatePdfFilename(BorLog $borLog): string
    {
        $lead = $borLog->personalQuote;
        $lob = $lead ? strtoupper($lead->lob) : 'GENERAL';
        $timestamp = now()->format('Ymd_His');

        return "BOR_{$lob}_{$borLog->id}_{$timestamp}.pdf";
    }

    /**
     * Get signature image data for embedding in PDF
     */
    private function getSignatureImageData(string $signaturePath): ?string
    {
        try {
            // Convert storage URL to local path
            $localPath = str_replace('/storage/', 'public/', $signaturePath);

            if (! Storage::disk('local')->exists($localPath)) {
                Log::warning('Signature file not found', ['path' => $localPath]);

                return null;
            }

            $imageData = Storage::disk('local')->get($localPath);

            return base64_encode($imageData);

        } catch (\Exception $e) {
            Log::error('Failed to get signature image data', [
                'signature_path' => $signaturePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download image from Azure URL and convert to base64 data URI for PDF embedding
     */
    private function getSignatureImageFromUrl(string $imageUrl): ?string
    {
        try {
            // Use cURL for better reliability with Azure URLs
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For dev environments
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $imageContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($imageContent === false || $httpCode !== 200) {
                Log::warning('Failed to download image from URL', [
                    'url' => $imageUrl,
                    'http_code' => $httpCode,
                    'curl_error' => $curlError,
                ]);

                return null;
            }

            // Detect image type from the content
            $imageInfo = getimagesizefromstring($imageContent);
            if (! $imageInfo) {
                Log::warning('Invalid image content downloaded', ['url' => $imageUrl]);

                return null;
            }

            $mimeType = $imageInfo['mime'];
            $base64Data = base64_encode($imageContent);

            // Create data URI
            $dataUri = "data:{$mimeType};base64,{$base64Data}";

            Log::info('Successfully converted signature image to base64', [
                'mime_type' => $mimeType,
                'image_size' => strlen($imageContent),
            ]);

            return $dataUri;

        } catch (\Exception $e) {
            Log::error('Failed to download and convert signature image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get LOB-specific data for PDF generation
     */
    private function getLobSpecificData(string $lob, PersonalQuote $lead): array
    {
        $lobData = [];

        switch (strtolower($lob)) {
            case 'car':
            case 'bike':
                $lobData = [
                    'vehicle_type' => $lob,
                    'requires_chassis' => true,
                    'requires_policy_details' => true,
                ];
                break;

            case 'travel':
                $lobData = [
                    'travel_destination' => 'Multiple Destinations',
                    'requires_policy_details' => false,
                ];
                break;

            case 'health':
                $lobData = [
                    'medical_coverage' => true,
                    'requires_policy_details' => false,
                ];
                break;

            case 'home':
                $lobData = [
                    'property_coverage' => true,
                    'requires_policy_details' => true,
                ];
                break;

            default:
                $lobData = [
                    'general_coverage' => true,
                    'requires_policy_details' => false,
                ];
        }

        return $lobData;
    }
}
