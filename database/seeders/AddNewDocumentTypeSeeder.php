<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class AddNewDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypesCount = DocumentType::get()->where('code', 'CPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CPD',
                'text' => 'Payment Document',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'car',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
       $documentTypesCount = DocumentType::get()->where('code', 'CPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'car',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'HPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'health',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 3,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'HPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'health',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 3,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        

        $documentTypesCount = DocumentType::get()->where('code', 'TPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'TPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'travel',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 8,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'TPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'TPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'travel',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 8,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        
        $documentTypesCount = DocumentType::get()->where('code', 'LPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'LPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'life',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 4,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'LPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'LPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'life',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 4,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'PPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'PPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'pet',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 9,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'PPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'PPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'pet',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 9,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }

    }
}
