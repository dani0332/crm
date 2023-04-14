<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'doc_name' => $this->doc_name,
            'doc_uuid' => $this->doc_uuid,
            'original_name' => $this->original_name,
            'document_type_code' => $this->document_type_code,
            'document_type_text' => $this->document_type_text,
            'member_detail_id' => $this->member_detail_id,
        ];
    }
}
