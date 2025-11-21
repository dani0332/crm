<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyLeadConfigurationNationality extends Model
{
    protected $table = 'buy_lead_configuration_nationalities';

    protected $fillable = [
        'quote_type',
        'nationality_id',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function scopeForQuoteType($query, string $quoteType)
    {
        $query->where('quote_type', $quoteType);
    }

    public static function getEnabledNationalityIds(string $quoteType): array
    {
        return self::forQuoteType($quoteType)->pluck('nationality_id')->toArray();
    }

    public static function syncNationalities(string $quoteType, array $nationalityIds): void
    {
        self::forQuoteType($quoteType)
            ->whereNotIn('nationality_id', $nationalityIds)
            ->delete();

        foreach ($nationalityIds as $nationalityId) {
            self::firstOrCreate([
                'quote_type' => $quoteType,
                'nationality_id' => $nationalityId,
            ]);
        }
    }
}
