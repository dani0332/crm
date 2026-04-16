<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedProductRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEpDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('modelType') && is_string($this->input('modelType'))) {
            $this->merge([
                'modelType' => strtolower($this->input('modelType')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'epId' => ['required', 'exists:embedded_products,id'],
            'modelType' => ['required', 'string', Rule::in(self::allowedModelTypesForEpDocumentOverride())],
            'quoteId' => ['required', 'integer'],
            'documentId' => [
                'required',
                'integer',
                Rule::exists('quote_documents', 'id')
                    ->withoutTrashed()
                    ->where('quote_documentable_type', EmbeddedTransaction::class)
                    ->where(function ($query): void {
                        $ids = $this->embeddedTransactionIdsForManualOverride();

                        if ($ids === []) {
                            $query->whereRaw('1 = 0');

                            return;
                        }

                        $query->whereIn('quote_documentable_id', $ids);
                    }),
            ],
            'documentNumber' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * IDs of embedded transactions for this EP, LOB, and quote (same scope as {@see EmbeddedProductRepository::fetchTransaction} with `$selected = false`).
     *
     * @return list<int>
     */
    private function embeddedTransactionIdsForManualOverride(): array
    {
        $epId = filter_var($this->input('epId'), FILTER_VALIDATE_INT);
        $quoteId = filter_var($this->input('quoteId'), FILTER_VALIDATE_INT);
        $modelType = $this->input('modelType');

        $quoteTypeId = false;
        $optionsIds = [];

        if (
            $epId !== false && $epId >= 1
            && $quoteId !== false && $quoteId >= 1
            && is_string($modelType) && $modelType !== ''
        ) {
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
            if ($quoteTypeId !== false) {
                $ep = EmbeddedProduct::query()->with('prices')->find($epId);
                if ($ep !== null) {
                    $optionsIds = $ep->prices !== null ? $ep->prices->pluck('id')->all() : [];
                }
            }
        }

        if ($quoteTypeId === false || $optionsIds === []) {
            return [];
        }

        /** @var list<int> */
        return EmbeddedTransaction::query()
            ->where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteId)
            ->whereIn('product_id', $optionsIds)
            ->pluck('id')
            ->all();
    }

    /**
     * Lowercase modelType strings for embedded-product document flows (aligned with {@see EmbeddedProductRepository::ALLOWED_LOBS}).
     *
     * @return list<string>
     */
    private static function allowedModelTypesForEpDocumentOverride(): array
    {
        $options = QuoteTypeId::getOptions();
        $types = [];

        foreach (EmbeddedProductRepository::ALLOWED_LOBS as $quoteTypeId) {
            $label = $options[$quoteTypeId] ?? null;
            if (is_string($label) && $label !== '') {
                $types[] = strtolower($label);
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'epId.required' => 'EP ID is required.',
            'epId.exists' => 'The selected EP does not exist.',
            'modelType.required' => 'Model type is required.',
            'modelType.in' => 'The selected model type is invalid.',
            'quoteId.required' => 'Quote ID is required.',
            'documentId.required' => 'Document ID is required.',
            'documentId.exists' => 'The selected document does not exist.',
            'documentNumber.required' => 'Document number is required.',
            'file.required' => 'A PDF file is required.',
            'file.mimes' => 'The file must be a PDF.',
            'remarks.required' => 'Remarks are required.',
        ];
    }
}
