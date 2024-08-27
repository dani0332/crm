<?php

namespace App\Enums;

use Illuminate\Support\Str;

trait Enumable
{
    public function label()
    {
        return Str::title(Str::lower(str_replace('_', ' ', $this->name)));
    }

    public static function withLabels($quoteTypeId = null): array
    {
        $values = [];
        $caseList = collect(self::cases());
        if($quoteTypeId == QuoteTypeId::Health)  {
            $caseList = collect($caseList)->whereNotIn('value', QuoteSegmentEnum::SIC_REVIVAL->value);
        }
        foreach ($caseList  as $case) {
            $values[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $values;
    }

}
