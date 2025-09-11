<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Instant Writer AI Facade
 *
 * Provides static access to InstantWriterAIAPIService methods
 *
 * @method static object request(string $path, string $method = 'post', array $data = [], bool $isContentGeneration = false)
 * @method static object generateContent(string $prompt, array $options = [])
 * @method static object analyzeContent(string $content, array $analysisType = ['sentiment', 'readability'])
 * @method static object improveContent(string $content, array $improvementOptions = [])
 * @method static object generateVariations(string $originalContent, int $variationCount = 3, array $variationOptions = [])
 * @method static object summarizeContent(string $content, array $summaryOptions = [])
 * @method static object checkGrammar(string $content, array $checkOptions = [])
 * @method static object getModelInfo()
 * @method static bool ping()
 * @method static array getHealthStatus()
 */
class InstantWriterAIFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'InstantWriterAIAPIService';
    }
}
