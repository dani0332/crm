<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgeDiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'age_start' => $this->age_start,
            'age_end' => $this->age_end,
            'discount' => $this->discount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
