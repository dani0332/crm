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
            'text' => 'Send Update Policy Schedule',
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
            'text' => 'Send Update Policy Certificate',
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
            'text' => 'Send Update E-Card',
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
            'text' => 'Send Update Tax Invoice',
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
            'text' => 'Send Update Tax Invoice Raised Buyer',
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
            'text' => 'Send Update Receipt',
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
            'text' => 'Send Update Additional Email Attachments',
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
            'text' => 'Send Update Garage List',
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
            'text' => 'Send Update Garage List',
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
            'text' => 'Send Update Policy Handbook',
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
