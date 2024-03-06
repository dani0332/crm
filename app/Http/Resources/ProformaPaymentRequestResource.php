<?php

namespace App\Http\Resources;

use App\Enums\DocumentTypeEnum;
use App\Models\QuoteDocument;
use Illuminate\Http\Resources\Json\JsonResource;

class ProformaPaymentRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'original_name' => $this->original_name,
            'doc_url' => $this->doc_url,
        ];
    }
}
