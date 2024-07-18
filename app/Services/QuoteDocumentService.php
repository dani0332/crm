<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use FilippoToso\PdfWatermarker\Support\Position;
use FilippoToso\PdfWatermarker\Facades\ImageWatermarker;

class QuoteDocumentService extends BaseService
{
    use GenericQueriesAllLobs;

    /**
     * get list of active document types can be presented to customer to upload documents.
     *
     * @return mixed
     */
    public function getQuoteDocumentsToReceive($quoteTypeId)
    {
        return DocumentType::where([
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => $quoteTypeId,
        ])
            ->orderBy('sort_order')
            ->get();
    }

    public function isEnabled($quoteModelType)
    {
        $enabledLOBs = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Home];
        if (in_array($quoteModelType, $enabledLOBs)) {
            return true;
        }

        return false;
    }

    public function getQuoteDocumentsForUpload($quoteTypeId)
    {
        return DocumentType::where(['quote_type_id' => $quoteTypeId, 'is_active' => true])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * @param  $data  doc_name, doc_uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteQuoteDocument($quoteType, $data)
    {
        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

        //load quote document with provided detail
        $quote->load(['documents' => function ($q) use ($data) {
            $q->where([
                'doc_name' => $data['doc_name'],
                'doc_uuid' => $data['doc_uuid'],
            ]);
        }]);

        //check for document and delete if found
        if (($document = $quote->documents->first())) {
            $document->delete();
            //Log::info('CL: '.get_class().' FN: deleteQuoteDocument  UUID: '.$data['quote_uuid'].' Message: document ('.$data['doc_name'].') deleted');

            return response()->json(['message' => 'document deleted successfully']);
        }

        vAbort('Invalid document detail provided.');
    }

    /**
     * upload quote document and store document record in db.
     *
     * @param  $documentTypeCode
     * @param  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadQuoteDocument($fileOrBase64, $data, $quote, $isKyc = false, $isPaymentReceipt = false)
    {
        if (! ($documentType = DocumentType::where('code', $data['document_type_code'])->first())) {
            return response()->json(['error' => 'Invalid document type code provided'], 500);
        }
        try {

            if (data_get($data, 'is_base_64', 0) == 1) {
                $originalName = 'Base 64 file';
                @[$extension, $fileMimeType, $file_data] = getBase64FileInfo($fileOrBase64);

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$data['document_type_code'].'.'.$extension);
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;

                // Set the filename for Azure storage
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                Storage::disk('azureIM')->put($filePathAzure, base64_decode($file_data));
            } elseif ($isPaymentReceipt) {
                $originalName = 'Receipt-'.$data['pdf_filename'].'.pdf';

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = 'application/pdf';

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                $uploaded = Storage::disk('azureIM')->put($filePathAzure, $fileOrBase64);
                if (! $uploaded) {
                    return false;
                }
            } elseif ($isKyc) {
                $originalName = 'SystemGeneratedKycDocument.pdf';

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = $documentType->accepted_files;

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                $uploaded = Storage::disk('azureIM')->put($filePathAzure, $fileOrBase64);
                if (! $uploaded) {
                    return false;
                }
            } else {
                $originalName = sanitizeFileName($fileOrBase64->getClientOriginalName());

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = $fileOrBase64->getClientMimeType();

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = $fileOrBase64->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

                // watermark only for pdf files
                if ($fileMimeType == 'application/pdf') {
                    $this->watermarkPdf($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType);
                } elseif ($fileMimeType == 'image/jpeg' || $fileMimeType == 'image/png' || $fileMimeType == 'image/jpg') {
                    $this->watermarkImage($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType);
                } else if ($fileMimeType == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $fileMimeType == 'application/msword') {
                    $this->watermarkWordDocs($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType);
                }
            }

            // Generate a unique UUID
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            return $quote->documents()->create([
                'doc_name' => $docName,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'member_detail_id' => $data['member_detail_id'] ?? null,
                'payment_split_type' => $data['split_payment_doc_type'] ?? null,
                'payment_split_id' => $data['payment_split_id'] ?? null,
                'created_by_id' => auth()->id(),
            ]);
        } catch (\Exception $exception) {
            Log::info('CL: '.get_class().' FN: uploadQuoteDocument  UUID: '.$data['quote_uuid'].' Error Code/Message: '.$exception->getCode().'/'.$exception->getMessage());

            return response()->json(['error' => 'Document upload failed, please try again'], 500);
        }
    }

    public function getQuoteDocumentUrl($id)
    {
        $quoteDocument = QuoteDocument::where('doc_uuid', $id)->first();

        if (! $quoteDocument) {
            abort(404);
        }

        return (object) ['doc_url' => $quoteDocument->doc_url, 'doc_mime_type' => $quoteDocument->doc_mime_type];
    }

    public function showSendPolicyButton($record, $quoteDocuments, $quoteTypeId)
    {
        if (! $record) {
            return 0;
        }

        if (! auth()->user()->hasRole(RolesEnum::BetaUser)) {
            return false;
        }

        if (! isset($record->policy_number) || ! isset($record->policy_issuance_date) || ! isset($record->policy_start_date) ||
            ! isset($record->premium) || ! isset($record->renewal_expiry_date) || ! isset($record->plan_id) ||
            $record->advisor_id != auth()->user()->id) {
            return 0;
        }

        $documentUploadTypes = $this->getQuoteDocumentsForUpload($quoteTypeId);
        if (! $documentUploadTypes) {
            return 0;
        }
        $displaySendPolicyButton = 1;
        foreach ($documentUploadTypes->where('is_required', 1) as $documentUploadType) {
            if ($quoteDocuments->where('document_type_code', $documentUploadType->code)->count() == 0) {
                $displaySendPolicyButton = 0;
                break;
            }
        }

        return $displaySendPolicyButton;
    }

    public function getQuoteDocuments($quoteType, $recordId)
    {
        $quote = $this->getQuoteObject($quoteType, $recordId);

        return $quote ? $quote->documents()->with('createdBy:id,name,email')->latest()->get() : [];
    }

    /**
     * create pdf watermark function
     *
     * @param [type] $file
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function watermarkPdf($file, $docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        if (!file_exists(storage_path('/app/temp'))) {
            mkdir(storage_path('/app/temp'), 0777, true);
        }

        ImageWatermarker::input($file)
            ->watermark(public_path('images/watermark1.png'))
            ->output(storage_path('app/temp/' . $docName))
            ->position(Position::MIDDLE_CENTER, 0, 0)
            ->asBackground()
            ->resolution(96)
            ->save();

        $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);

    }

    /**
     * create image watermark function
     *
     * @param [type] $file
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function watermarkImage($file, $docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        if (!file_exists(storage_path('/app/temp'))) {
            mkdir(storage_path('/app/temp'), 0777, true);
        }

        $manager = new ImageManager(new Driver());

        $image = $manager->read($file);

        $image->place(
            public_path('images/watermark2.png'),
            'center',
            10,
            10,
            15
        );

        $image->save(storage_path('app/temp/'.$docName));

        $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);
    }

    /**
     * store watermarked media
     *
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        $watermarkedFile = new \Illuminate\Http\File(storage_path('app/temp/' . $docName));

        // Set the filename for Azure storage
        $watermarkedFileNameAzure = uniqid() . '_' . $data['quote_uuid'] . '_watermarked_' . $docName;
        // upload file to azure
        $filePathAzure = Storage::disk('azureIM')->putFileAs('documents/' . $documentType->folder_path, $watermarkedFile, $watermarkedFileNameAzure);

        // Generate a unique UUID
        $docUuid = uniqid();
        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid() . rand(1, 100);
        }

        // stored watermarked file
        $quote->documents()->create([
            'doc_name' => 'watermarked_' . $docName,
            'original_name' => 'watermarked_' . $originalName,
            'doc_url' => $filePathAzure,
            'is_watermarked' => true,
            'doc_mime_type' => $fileMimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => $docUuid,
            'member_detail_id' => $data['member_detail_id'] ?? null,
            'payment_split_type' => $data['split_payment_doc_type'] ?? null,
            'payment_split_id' => $data['payment_split_id'] ?? null,
            'created_by_id' => auth()->id(),
        ]);

        // delete temp file
        unlink(storage_path('temp/' . $docName));
    }

    public function watermarkWordDocs($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        if (!file_exists(storage_path('/app/temp'))) {
            mkdir(storage_path('/app/temp'), 0777, true);
        }

        $tempFile = $fileOrBase64->move(storage_path('/app/temp'), $docName)->getRealPath();

        $phpWord = IOFactory::load($tempFile);
        $section = $phpWord->getSection(0);
        // Define the watermark style
        $header = $section->addHeader();
        $header->addWatermark(public_path('images/watermark1.png'));

        // Save the modified document
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);
    }
}
