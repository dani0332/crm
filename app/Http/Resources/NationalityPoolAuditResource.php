<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NationalityPoolAuditResource extends JsonResource
{
    public function toArray($request)
    {
        $canonicalNationalities = app()->make(\App\Services\CanonicalNationalityService::class)
            ->getByCodes($this->canonical_nationality_codes);
        $visible = collect($canonicalNationalities)->take(8);
        $remaining = collect($canonicalNationalities)->count() - 8;

        return [
            'id' => $this->id,
            'user' => $this->user?->name,
            'nationalities' => $visible->implode(', ').
                ($remaining > 0 ? " +{$remaining} more" : ''),
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y'),
            'effective_from' => Carbon::parse($this->effective_from)->format('d-m-Y'),
            'effective_to' => Carbon::parse($this->effective_to)->format('d-m-Y'),
            'event' => $this->created_at != $this->updated_at ? 'Updated' : 'Created',
        ];
    }
}
