<?php

namespace App\Http\Resources;

use App\Services\CanonicalNationalityService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NationalityPoolAuditResource extends JsonResource
{
    public function toArray($request)
    {
        $canonicalNationalities = ! empty($this->canonical_nationality_codes) ? app()->make(CanonicalNationalityService::class)
            ->getByCodes($this->canonical_nationality_codes) : [];

        return [
            'id' => $this->id,
            'user' => $this->user?->name,
            'nationalities' => $canonicalNationalities,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y'),
            'effective_from' => Carbon::parse($this->effective_from)->format('d-m-Y'),
            'effective_to' => Carbon::parse($this->effective_to)->format('d-m-Y'),
            'event' => $this->created_at != $this->updated_at ? 'Updated' : 'Created',
            'deleted_at' => $this->deleted_at,
        ];
    }
}
