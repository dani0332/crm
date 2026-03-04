<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GenericDocumentResource extends JsonResource
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
            'id' => $this->id,
            'generic_document_type_id' => $this->generic_document_type_id,
            'uuid' => $this->uuid,
            'documentable_id' => $this->documentable_id,
            'documentable_type' => $this->documentable_type,
            'quote_type_id' => $this->quote_type_id,
            'name' => $this->name,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
