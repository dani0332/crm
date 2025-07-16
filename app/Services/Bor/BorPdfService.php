<?php

namespace App\Services\Bor;

use App\Models\BorLog;
use App\Models\PersonalQuote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BorPdfService
{
    /**
     * Generate a BOR PDF document with embedded signature
     */
    public function generateSignedBorPdf(BorLog $borLog): ?string
    {
        try {
            // Get lead information
            $lead = $borLog->personalQuote;
            if (!$lead) {
                throw new \Exception('Lead information not found for BOR log ID: ' . $borLog->id);
            }

            // Prepare data for PDF template
            $data = $this->preparePdfData($borLog, $lead);

            // Generate PDF using blade template
            $pdf = Pdf::loadView('pdf.bor-document', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Generate filename
            $filename = $this->generatePdfFilename($borLog);
            
            // Save PDF to storage
            $pdfContent = $pdf->output();
            $pdfPath = 'bor-documents/' . $filename;
            Storage::disk('public')->put($pdfPath, $pdfContent);

            // Update BOR log with PDF path
            $borLog->update([
                'signed_pdf_path' => Storage::url($pdfPath),
            ]);

            Log::info('BOR PDF generated successfully', [
                'bor_log_id' => $borLog->id,
                'pdf_path' => $pdfPath,
                'filename' => $filename,
            ]);

            return Storage::url($pdfPath);

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
    public function generatePreviewBorPdf(BorLog $borLog): array|null
    {
        try {
            $lead = $borLog->personalQuote;
            if (!$lead) {
                throw new \Exception('Lead information not found for BOR log ID: ' . $borLog->id);
            }

            // Prepare data for PDF template (without signature)
            $data = $this->preparePdfData($borLog, $lead, false);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.bor-document', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Generate filename for preview
            $filename = 'preview_' . $this->generatePdfFilename($borLog);
            
            return ['pdf' => $pdf, 'name' => $filename];
        } catch (\Exception $e) {
            Log::error('BOR preview PDF generation failed', [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Prepare data for PDF template
     */
    private function preparePdfData(BorLog $borLog, PersonalQuote $lead, bool $includeSignature = true): array
    {
        $data = [
            // BOR Information
            'bor_log' => $borLog,
            'bor_reference' => $borLog->bor_reference ?? Str::upper(Str::random(10)),
            'created_date' => $borLog->created_at,
            
            // Customer Information
            'customer_type' => $borLog->customer_type,
            'customer_name' => $borLog->customer_type === 'Individual' 
                ? ($lead->first_name . ' ' . $lead->last_name)
                : $borLog->insurer_name,
            'company_name' => $borLog->customer_type === 'Entity' ? $borLog->insurer_name : null,
            'customer_email' => $lead->email,
            'customer_phone' => $lead->phone,
            
            // Lead Information
            'lead' => $lead,
            'lob' => strtoupper($lead->lob ?? 'GENERAL'),
            'quote_reference' => $lead->quote_reference ?? $lead->uuid,
            
            // Insurance Information
            'insurance_company' => $borLog->insuranceProvider->text ?? null,
            'policy_number' => $borLog->policy_number,
            'policy_expiry' => $borLog->policy_expiry ? $borLog->policy_expiry->format('Y-m-d') : null,
            'chassis_number' => $borLog->chasis_number,
            
            // Signature Information
            'include_signature' => $includeSignature && $borLog->signature_path,
            'signature_path' => $includeSignature ? $borLog->signature_path : null,
            'signature_name' => $borLog->customer_signature_name,
            'date_signed' => $borLog->date_signed ? $borLog->date_signed->format('Y-m-d H:i:s') : null,
            
            // Additional Information
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
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
            
            if (!Storage::disk('local')->exists($localPath)) {
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
     * Generate BOR document for specific LOB
     */
    public function generateLobSpecificBorPdf(BorLog $borLog, string $lob): ?string
    {
        try {
            $lead = $borLog->personalQuote;
            if (!$lead) {
                throw new \Exception('Lead information not found');
            }

            // Prepare LOB-specific data
            $data = $this->preparePdfData($borLog, $lead);
            $data['lob_specific'] = $this->getLobSpecificData($lob, $lead);

            // Use LOB-specific template if available, otherwise use default
            $templateName = $this->getLobSpecificTemplate($lob);
            
            $pdf = Pdf::loadView($templateName, $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Generate filename with LOB prefix
            $filename = strtoupper($lob) . '_' . $this->generatePdfFilename($borLog);
            
            // Save PDF
            $pdfContent = $pdf->output();
            $pdfPath = 'bor-documents/' . strtolower($lob) . '/' . $filename;
            Storage::disk('public')->put($pdfPath, $pdfContent);

            return Storage::url($pdfPath);

        } catch (\Exception $e) {
            Log::error('LOB-specific BOR PDF generation failed', [
                'bor_log_id' => $borLog->id,
                'lob' => $lob,
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

    /**
     * Get LOB-specific template name
     */
    private function getLobSpecificTemplate(string $lob): string
    {
        $templateMap = [
            'car' => 'pdf.bor-car',
            'bike' => 'pdf.bor-bike',
            'travel' => 'pdf.bor-travel',
            'health' => 'pdf.bor-health',
            'home' => 'pdf.bor-home',
        ];

        // Use LOB-specific template if it exists, otherwise use default
        $templateName = $templateMap[strtolower($lob)] ?? 'pdf.bor-document';
        
        // Check if view exists, fallback to default if not
        if (!view()->exists($templateName)) {
            return 'pdf.bor-document';
        }

        return $templateName;
    }
} 