<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeResource extends JsonResource
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
            'id'                => $this->id,
            'code'              => $this->code,
            'text'              => $this->text,
            'quote_type_id'     => $this->quote_type_id,
            'accepted_files'    => $this->accepted_files,
            'max_files'         => $this->max_files,
            'max_size'          => $this->max_size,
            'is_required'       => $this->is_required,
            'sort_order'        => $this->sort_order
        ];
    }
}
