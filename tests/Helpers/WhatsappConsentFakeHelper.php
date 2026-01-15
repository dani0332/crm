<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\QuoteAdditionalDetail;
use Mockery;
use Mockery\MockInterface;

final class WhatsappConsentFakeHelper
{
    /**
     * Avoid MongoDB access by making QuoteAdditionalDetail::first() return null.
     */
    public static function noConsent(): MockInterface
    {
        return self::fakeConsentDocument(null);
    }

    /**
     * Avoid MongoDB access by returning a fake document with flags['whatsapp_consent'].
     */
    public static function consent(bool $consent = true): MockInterface
    {
        return self::fakeConsentDocument((object) [
            'flags' => ['whatsapp_consent' => $consent],
        ]);
    }

    private static function fakeConsentDocument(?object $document): MockInterface
    {
        /** @var MockInterface $mock */
        $mock = Mockery::mock('alias:'.QuoteAdditionalDetail::class);

        // getWhatsappConsent() chains where(...)->where(...)->first()
        $mock->shouldReceive('where')
            ->withAnyArgs()
            ->andReturnSelf();

        $mock->shouldReceive('first')
            ->andReturn($document);

        return $mock;
    }
}

