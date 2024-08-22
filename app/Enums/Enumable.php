<?php

namespace App\Enums;

use Illuminate\Support\Str;

trait Enumable
{
    public function label()
    {
        return Str::title(Str::lower(str_replace('_', ' ', $this->name)));
    }

    public static function withLabels(): array
    {
        $values = [];

        foreach (self::cases() as $case) {
            $values[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $values;
}

    public static function withLabelsForHealth(): array
    {
        $values = [];
        $caseList = collect(self::cases())->whereNotIn('value',QuoteSegmentEnum::SIC_REVIVAL->value);
        foreach ($caseList as $case) {
            if($case->value != QuoteSegmentEnum::SIC_REVIVAL){
                $values[] = [
                    'value' => $case->value,
                    'label' => $case->label(),
                ];
            }
        }
        return $values;
    }

}
