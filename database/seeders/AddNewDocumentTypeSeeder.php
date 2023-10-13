<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class AddNewDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypesCount = DocumentType::get()->where('code','CPD')->count();
        if(!$documentTypesCount){
            \DB::table('document_types')->insert([
                'code' => 'CPD',
                'text' => 'Payment Document',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 4,
                'is_required' => 0,
            ]);            
        }     
    }
}
