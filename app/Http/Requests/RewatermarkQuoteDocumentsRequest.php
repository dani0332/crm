<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\EmbeddedTransaction;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use Illuminate\Foundation\Http\FormRequest;

class RewatermarkQuoteDocumentsRequest extends FormRequest
{
    /**
     * Restrict to known models that own QuoteDocument via morphMany('quote_documentable').
     * This prevents dispatching against arbitrary model class names.
     *
     * @var array<int, string>
     */
    private const ALLOWED_QUOTE_DOCUMENTABLE_TYPES = [
        TravelQuote::class,
        PersonalQuote::class,
        LifeQuote::class,
        HomeQuote::class,
        HealthQuote::class,
        CarQuote::class,
        BusinessQuote::class,
        CycleQuote::class,
        BikeQuote::class,
        PetQuote::class,
        YachtQuote::class,

        SendUpdateLog::class,
        EmbeddedTransaction::class,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quote_type_id' => ['required_without:quote_documentable_type', 'integer'],
            'quote_documentable_type' => ['required_without:quote_type_id', 'string'],
            'doc_ids' => ['required', 'array', 'min:1'],
            'doc_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('quote_type_id')) {
                $quoteTypeId = $this->integer('quote_type_id');
                if (! QuoteTypes::getName($quoteTypeId)) {
                    $validator->errors()->add('quote_type_id', 'Invalid quote_type_id');
                }
            }

            if ($this->filled('quote_documentable_type')) {
                $type = (string) $this->input('quote_documentable_type');
                if (! in_array($type, self::ALLOWED_QUOTE_DOCUMENTABLE_TYPES, true)) {
                    $validator->errors()->add('quote_documentable_type', 'Invalid quote_documentable_type');
                }
            }
        });
    }
}
