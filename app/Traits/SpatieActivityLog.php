<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Context;

trait SpatieActivityLog
{
    use LogsActivity;
    /**
     * Configure Spatie activity logging
     */
    public function getActivitylogOptions(): LogOptions
    {
        // Get log name key from model property or method, otherwise use model name
        $logName = $this->getActivityLogName();

        return LogOptions::defaults()
            ->logAll() // log all attributes
            ->logOnlyDirty() // only log changed attributes
            ->useLogName($logName)
            ->setDescriptionForEvent(function (string $eventName) {
                $authUser = Auth::check() ? Auth::user() : null;
                $user = $authUser?->name ?? 'System User';
                $modelName = class_basename($this);

                // Build description with changed keys
                $changedAttributes = $this->getChanges();
                $changedKeys = array_keys($changedAttributes);
                $description = "User '{$user}' performed the '{$eventName}' action on {$modelName}";

                if (!empty($changedKeys)) {
                    $keysList = implode(', ', $changedKeys);
                    $description .= " - Changed fields: {$keysList}";
                }

                return $description;
            });
    }

    /**
     * Customize the activity before it's saved
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        // Get feature and code from Context (set by LoggerService::startFeatureLogging)
        $feature = Context::get('feature');
        $code = Context::get('code');
        
        // Set extra column values
        // Check if request() is available (null in console commands and queued jobs)
        $request = request();
        $activity->url = $request ? $request->getRequestUri() : null;
        $activity->feature = $feature;
        $activity->ip_address = $request ? $request->ip() : null;
        $activity->user_agent = $request ? $request->userAgent() : null;
        $activity->code = $code;

        // For update events, ensure both old and new values are stored
        if ($eventName === 'updated') {
            // Get current properties (convert to array if needed)
            $properties = $activity->properties ?? [];
            if (is_object($properties) && method_exists($properties, 'toArray')) {
                $properties = $properties->toArray();
            }
            if (!is_array($properties)) {
                $properties = [];
            }
            
            // Get changed attributes (new values)
            $changedAttributes = $this->getChanges();
            
            // Only process if there are actual changes
            if (!empty($changedAttributes)) {
                // Build old values array from original attributes
                $oldValues = [];
                foreach (array_keys($changedAttributes) as $attribute) {
                    $oldValues[$attribute] = $this->getOriginal($attribute);
                }
                
                // Ensure properties structure includes both old and attributes
                // Preserve any existing properties but ensure old and attributes are set correctly
                $properties['old'] = $oldValues;
                $properties['attributes'] = $changedAttributes;
                
                // Update activity properties
                $activity->properties = $properties;
            }
        }
    }

    /**
     * Get activity log name for this model
     * 
     * Priority:
     * 1. Check for $activityLogName property
     * 2. Check for getModelActivityLogName() method in model class
     * 3. Convert model class name from camelCase to spaced words
     */
    protected function getActivityLogName(): string
    {
        // Check for $activityLogName property
        if (property_exists($this, 'activityLogName') && !empty($this->activityLogName)) {
            return $this->activityLogName;
        }

        // Check if model class has getModelActivityLogName() method (different name to avoid recursion)
        if (method_exists($this, 'getModelActivityLogName')) {
            $name = $this->getModelActivityLogName();
            if (!empty($name)) {
                return $name;
            }
        }

        // Fallback: Convert model class name from camelCase to spaced words
        $modelName = class_basename($this);
        return $this->convertCamelCaseToSpaced($modelName);
    }

    /**
     * Convert camelCase string to spaced words
     * Examples:
     * - PaymentSplits -> Payment Splits
     * - User -> User
     * - SimpleTestingModelUser -> Simple Testing Model User
     */
    protected function convertCamelCaseToSpaced(string $camelCase): string
    {
        // Insert space before uppercase letters (except the first one)
        $spaced = preg_replace('/(?<!^)([A-Z])/', ' $1', $camelCase);
        
        return trim($spaced);
    }
    
}