<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\CQF\Contracts\CQFValidationInterface;
use Illuminate\Database\Eloquent\Model;

class CQFRenewalContext
{
    public function __construct(
        /** @var PersonalQuote|CarQuote Quote source (CarQuote only for Bike LOB when vehicle_type_id is Bike) */
        public Model $quote,
        public RenewalsUploadLeads $renewalsUploadLeads,
        public QuoteTypes $quoteType,
        public int $renewalDaysThreshold,
        /** @var array<int, string> */
        public array $epCodes = [],
        /** @var array<string, string> Validation errors when pipeline fails */
        public array $validationErrors = [],
        /** Created quote after successful storage */
        public ?Model $newQuote = null,
        /** LOB-specific validator (set by execution service) */
        public ?CQFValidationInterface $validator = null,
        /** LOB-specific mapper (set by execution service) */
        public ?CQFQuoteMappingInterface $mapper = null,
        /** LOB-specific storage (set by execution service) */
        public ?CQFQuoteStorageInterface $storage = null,
    ) {}

    public function fail(array $errors): self
    {
        $this->validationErrors = array_merge($this->validationErrors, $errors);

        return $this;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->validationErrors);
    }
}
