<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class SendUpdateSeederForCyber extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sendUpdateCodes = [
            [
                'code' => SendUpdateLogStatusEnum::CAAFE,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::MPC,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::ATIB,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::ATCRNB,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::ACB,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::ATCRNB_RBB,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::ATCRN_CRNRBB,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::CAA,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::UWOS,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::DWI,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::CIID,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::CII,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::CIC,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
            [
                'code' => SendUpdateLogStatusEnum::CIED_EOP,
                'quote_type_id' => QuoteTypeId::Cyber,
            ],
        ];

        foreach ($sendUpdateCodes as $sendUpdateCode) {
            $existingSendUpdate = Lookup::where('code', $sendUpdateCode['code'])->where('quote_type_id', $sendUpdateCode['quote_type_id'])->first();
            if (! $existingSendUpdate) {
                $parent = Lookup::where('code', $sendUpdateCode['code'])->first();
                Lookup::create([
                    'code' => $sendUpdateCode['code'],
                    'quote_type_id' => $sendUpdateCode['quote_type_id'],
                    'parent_id' => $parent->parent_id ?? null,
                    'key' => $parent->key ?? null,
                    'text' => $parent->text ?? null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
