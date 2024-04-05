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
                'text' => 'Payment Proof',
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

        $documentTypesCount = DocumentType::get()->where('code', 'CDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'car',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 3,
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
        $documentTypesCount = DocumentType::get()->where('code', 'HDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'health',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 3,
                'send_to_customer' => true,
                'sort_order' => 3,
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
        $documentTypesCount = DocumentType::get()->where('code', 'TDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'TDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'travel',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 8,
                'send_to_customer' => true,
                'sort_order' => 3,
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
        $documentTypesCount = DocumentType::get()->where('code', 'LDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'LDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'life',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 4,
                'send_to_customer' => true,
                'sort_order' => 3,
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
        $documentTypesCount = DocumentType::get()->where('code', 'PDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'PDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'pet',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 9,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'BPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'BPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'bike',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 6,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'BPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'BPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'bike',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 6,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'BDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'BDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'bike',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 6,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'CYCPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CYCPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'cycle',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 10,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'CYCPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CYCPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'cycle',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 10,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'CYCDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CYCDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'cycle',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 10,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'YPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'YPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'yacht',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 7,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'YPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'YPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'yacht',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 7,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'YDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'YDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'yacht',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 7,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'CLPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CLPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'CLPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CLPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'CLDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'CLDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'HOMPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HOMPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'home',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 2,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'HOMPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HOMPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'home',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 2,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'HOMDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'HOMDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'home',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 2,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

        $documentTypesCount = DocumentType::get()->where('code', 'GMQPD')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'GMQPD',
                'text' => 'Payment Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 1,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'GMQPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'GMQPDR',
                'text' => 'Receipt',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 2,
                'is_required' => 0,
            ]);
        }
        $documentTypesCount = DocumentType::get()->where('code', 'GMQDPDR')->count();
        if (! $documentTypesCount) {
            \DB::table('document_types')->insert([
                'code' => 'GMQDPDR',
                'text' => 'Discount Proof',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'business',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'quote_type_id' => 5,
                'send_to_customer' => true,
                'sort_order' => 3,
                'is_required' => 0,
            ]);
        }

    }
}
