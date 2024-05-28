<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class SendUpdateDocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::updateOrCreate([
            'code' => DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
        ], [
            'text' => 'Endorsed Schedule',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 1,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE,
        ]), [
            'text' => 'Endorsed Certificate',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 2,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_ECARD,
        ]), [
            'text' => 'E-Card',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 3,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_TAX_INVOICE,
        ]), [
            'text' => 'Tax Invoice',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 4,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER,
        ]), [
            'text' => 'Tax Invoice Raised Buyer',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 5,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_RECEIPT,
        ]), [
            'text' => 'Receipt',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
            'sort_order' => 6,
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_PAYMENT_PROOF,
        ]), [
            'text' => 'Payment Proof',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_CUSTOMER_DOCUMENTS,
        ]), [
            'text' => 'Customer documents',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_UW_EMAIL_CORRESPONDENCE,
        ]), [
            'text' => 'UW email Correspondence',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);
        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::PPR,
            'category' => 'SEND_UPDATE',
        ]), [
            'text' => 'Payment Proforma Request',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);
    }
}
