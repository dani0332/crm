<?php

namespace App\Http\Controllers;

use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RawQueryController extends Controller
{
    // Define constants for common fields
    private const COMMON_FIELDS = [
        'id', 'uuid', 'customer_id', 'quote_status_id', 'payment_status_id',
        'advisor_id', 'nationality_id',
    ];
    private const COMMON_TRACKING_FIELDS = [
        'source', 'lead_allocation_failed_at', 'created_at',
    ];

    // Add specific fields for each quote type
    private const SPECIFIC_FIELDS = [
        'health' => [
            'sic_flow_enabled',
            'sic_advisor_requested',
        ],
        'travel' => [
            'sic_flow_enabled',
            'sic_advisor_requested',
        ],
        'car' => [
            'is_renewal_tier_email_sent',
            'sic_flow_enabled',
            'sic_advisor_requested',
        ],
        'home' => [
            'pa_id',
        ],
        'personal' => [
            'plan_id',
        ],
        'life' => [
            'pa_id',
        ],
        'business' => [
            'pa_id',
        ],
    ];
    private const QUOTE_TYPES = [
        'health' => HealthQuote::class,
        'home' => HomeQuote::class,
        'travel' => TravelQuote::class,
        'personal' => PersonalQuote::class,
        'life' => LifeQuote::class,
        'car' => CarQuote::class,
        'business' => BusinessQuote::class,
    ];

    private array $fieldsMap;
    private array $relationMap;
    private array $relationFieldsMap;

    public function __construct()
    {
        $this->initializeMaps();
    }

    private function initializeMaps(): void
    {
        foreach (self::QUOTE_TYPES as $type => $class) {
            // Start with common fields
            $this->fieldsMap[$class] = self::COMMON_FIELDS;

            // Add plan_id or pa_id based on quote type
            if (in_array($type, ['health', 'travel', 'personal', 'car'])) {
                $this->fieldsMap[$class][] = 'plan_id';
            }
            if (in_array($type, ['home', 'life', 'business'])) {
                $this->fieldsMap[$class][] = 'pa_id';
            }

            // Add tracking fields
            if (in_array($type, ['health', 'travel', 'life', 'car'])) {
                $this->fieldsMap[$class] = array_merge(
                    $this->fieldsMap[$class],
                    self::COMMON_TRACKING_FIELDS
                );
            }

            // Add specific fields for certain quote types
            if (isset(self::SPECIFIC_FIELDS[$type])) {
                $this->fieldsMap[$class] = array_merge(
                    $this->fieldsMap[$class],
                    self::SPECIFIC_FIELDS[$type]
                );
            }

            // Initialize relation maps
            $relation = $type === 'personal' ? 'quoteDetail' : "{$type}QuoteRequestDetail";
            $this->relationMap[$class] = [$relation];
            $this->relationFieldsMap[$class] = [
                $relation => [
                    'id',
                    $type === 'personal' ? 'personal_quote_id' : "{$type}_quote_request_id",
                    'utm_source',
                    'utm_medium',
                    'utm_campaign',
                ],
            ];
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $modelType = $this->getModelType($request->modelType);

            if (! isset($this->fieldsMap[$modelType])) {
                return response()->json(['error' => 'Invalid model type'], 400);
            }

            if (! $request->has('uuid') || ! is_string($request->uuid) || trim($request->uuid) === '') {
                return response()->json(['error' => 'Valid UUID is required'], 400);
            }

            $entity = $this->fetchQuoteData($modelType, $request->uuid);

            return response()->json(['record' => $entity]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }

    private function getModelType(string $requestType): string
    {
        $nameSpace = 'App\\Models\\';

        return checkPersonalQuotes(ucwords($requestType))
            ? $nameSpace.'PersonalQuote'
            : $nameSpace.ucwords($requestType).'Quote';
    }

    private function fetchQuoteData(string $modelType, string $uuid)
    {
        $query = $modelType::select($this->fieldsMap[$modelType])
            ->where('uuid', $uuid);

        if (isset($this->relationMap[$modelType])) {
            foreach ($this->relationMap[$modelType] as $relation) {
                $query->with([
                    $relation => function ($query) use ($relation, $modelType) {
                        $query->select($this->relationFieldsMap[$modelType][$relation]);
                    },
                ]);
            }
        }

        return $query->first();
    }
}
