<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UAEAMLListUpload extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('uae_aml_list_uploads')->insert([
            'file_name' => '10-11-2021_UAESanctionList.xls',
            'is_updated' => true,
        ]);
    }
}
