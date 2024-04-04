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
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_ADDITIONAL_EMAIL_ATTACHEMENTS,
        ]), [
            'text' => 'Additional Email Attachments',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 20,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_GARAGE_LIST,
        ]), [
            'text' => 'Garage List',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_GARAGE_LIST,
        ]), [
            'text' => 'Garage List',
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'SEND_UPDATE',
        ]);

        DocumentType::updateOrCreate(([
            'code' => DocumentTypeCode::SEND_UPDATE_POLICY_HANDBOOK,
        ]), [
            'text' => 'Policy Handbook',
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
