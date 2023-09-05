<?php

namespace Database\Seeders;

use App\Enums\CommercialKeywordEnum;
use App\Models\CommercialKeyword;
use Illuminate\Database\Seeder;

class CommercialKeywordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keywordsList = CommercialKeywordEnum::KEYWORDS_LIST;

        foreach ($keywordsList as $id => $key) {
            CommercialKeyword::updateOrCreate(
                ['id' => $id, 'key' => $key],
                ['id' => $id, 'key' => $key, 'name' => strtolower(str_replace('_', ' ', $key))],
            );
        }
    }
}
