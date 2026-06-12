<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeResource extends JsonResource
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
            'code' => $this->code,
            'text' => $this->text,
            'description' => $this->description,
            'quote_type_id' => $this->quote_type_id,
            'accepted_files' => $this->accepted_files,
            'max_files' => $this->max_files,
            'max_size' => $this->max_size,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'send_to_customer' => $this->send_to_customer,
            'category' => $this->category,
            'tool_tip' => $this->tool_tip,
            'business_type_of_insurance_id' => $this->business_type_of_insurance_id,
            'is_claim_form' => $this->when(isset($this->is_claim_form), fn () => $this->is_claim_form),
        ];
    }
}
