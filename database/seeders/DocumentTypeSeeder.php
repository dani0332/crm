<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $documentTypesCount = DocumentType::all()->count();
        if ($documentTypesCount == 0) {
            DB::table('document_types')->insert([[
                'code' => 'CPC',
                'text' => 'Policy Certificate',
                'max_files' => 1,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 1,
            ], [
                'code' => 'CTI',
                'text' => 'Tax Invoice',
                'max_files' => 1,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 1,
            ], [
                'code' => 'CTIRBB',
                'text' => 'Tax Invoice Raise by Buyer',
                'max_files' => 2,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 3,
                'is_required' => 1,
            ], [
                'code' => 'CEID',
                'text' => 'Emirates ID',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 4,
                'is_required' => 1,
            ], [
                'code' => 'TR',
                'text' => 'Receipt',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 5,
                'is_required' => 1,
            ], [
                'code' => 'TAD',
                'text' => 'Additional Documents',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 6,
                'is_required' => 0,
            ], [
                'code' => 'TAEA',
                'text' => 'Additional Email Attachments',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 7,
                'is_required' => 0,
            ]]);
        }
    }
}
