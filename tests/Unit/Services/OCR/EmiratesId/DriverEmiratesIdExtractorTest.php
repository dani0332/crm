<?php

declare(strict_types=1);

use App\Services\OCR\EmiratesId\DriverEmiratesIdExtractor;

describe('DriverEmiratesIdExtractor', function () {
    test('extracts all required fields from complete OCR data', function () {
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'John Doe Smith',
            'dateOfBirth' => '1985-05-15',
            'nationality' => 'United Arab Emirates',
            'sex' => 'M',
            'metadata' => (object) [
                'model' => 'gpt-4-vision',
                'provider' => 'openai',
            ],
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result)->toBeArray()
            ->and($result['eid_number'])->toBe('784-1985-1234567-1')
            ->and($result['driver_name'])->toBe('John Doe Smith')
            ->and($result['date_of_birth'])->toBe('1985-05-15')
            ->and($result['nationality'])->toBe('United Arab Emirates')
            ->and($result['sex'])->toBe('male')
            ->and($result['ocr_model'])->toBe('gpt-4-vision')
            ->and($result['ocr_provider'])->toBe('openai')
            ->and($result['ocr_processed_at'])->not->toBeNull();
    });

    test('handles missing optional fields gracefully', function () {
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'John Doe',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result)->toBeArray()
            ->and($result['eid_number'])->toBe('784-1985-1234567-1')
            ->and($result['driver_name'])->toBe('John Doe')
            ->and(array_key_exists('date_of_birth', $result))->toBeTrue()
            ->and(array_key_exists('nationality', $result))->toBeTrue()
            ->and(array_key_exists('sex', $result))->toBeTrue();
    });

    test('handles null values in OCR data', function () {
        $ocrData = (object) [
            'idNumber' => null,
            'name' => null,
            'dateOfBirth' => null,
            'nationality' => null,
            'sex' => null,
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result)->toBeArray()
            ->and($result['ocr_processed_at'])->not->toBeNull();
    });

    test('formats gender correctly for male variations', function () {
        $testCases = [
            'M' => 'male',
            'MALE' => 'male',
            'male' => 'male',
        ];

        foreach ($testCases as $input => $expected) {
            $ocrData = (object) [
                'sex' => $input,
            ];

            $extractor = new DriverEmiratesIdExtractor($ocrData);
            $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

            expect($result['sex'])->toBe($expected, "Failed to format gender: {$input}");
        }
    });

    test('formats gender correctly for female variations', function () {
        $testCases = [
            'F' => 'female',
            'FEMALE' => 'female',
            'female' => 'female',
        ];

        foreach ($testCases as $input => $expected) {
            $ocrData = (object) [
                'sex' => $input,
            ];

            $extractor = new DriverEmiratesIdExtractor($ocrData);
            $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

            expect($result['sex'])->toBe($expected, "Failed to format gender: {$input}");
        }
    });

    test('formats date correctly', function () {
        $testCases = [
            '1985-05-15' => '1985-05-15',
            '2000-12-31' => '2000-12-31',
            '1990/08/20' => '1990-08-20',
        ];

        foreach ($testCases as $input => $expected) {
            $ocrData = (object) [
                'dateOfBirth' => $input,
            ];

            $extractor = new DriverEmiratesIdExtractor($ocrData);
            $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

            expect($result['date_of_birth'])->toBe($expected, "Failed to format date: {$input}");
        }
    });

    test('handles malformed date gracefully', function () {
        $ocrData = (object) [
            'dateOfBirth' => 'invalid-date',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        // formatDate should return null for invalid dates
        expect(array_key_exists('date_of_birth', $result))->toBeTrue();
    });

    test('handles empty strings as null', function () {
        $ocrData = (object) [
            'idNumber' => '',
            'name' => '',
            'dateOfBirth' => '',
            'nationality' => '',
            'sex' => '',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        // getCleanData filters out empty strings
        expect($result)->toBeArray()
            ->and(isset($result['eid_number']))->toBeFalse()
            ->and(isset($result['driver_name']))->toBeFalse()
            ->and(isset($result['nationality']))->toBeFalse()
            ->and(isset($result['sex']))->toBeFalse();
    });

    test('handles array input instead of object', function () {
        $ocrData = [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'Jane Smith',
            'sex' => 'F',
        ];

        $extractor = new DriverEmiratesIdExtractor((object) $ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result)->toBeArray()
            ->and($result['eid_number'])->toBe('784-1985-1234567-1')
            ->and($result['driver_name'])->toBe('Jane Smith')
            ->and($result['sex'])->toBe('female');
    });

    test('preserves unrecognized gender values', function () {
        $ocrData = (object) [
            'sex' => 'Other',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result['sex'])->toBe('Other');
    });

    test('extracts metadata when present', function () {
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'metadata' => (object) [
                'model' => 'claude-3',
                'provider' => 'anthropic',
            ],
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect($result['ocr_model'])->toBe('claude-3')
            ->and($result['ocr_provider'])->toBe('anthropic');
    });

    test('handles missing metadata gracefully', function () {
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        expect(array_key_exists('ocr_model', $result))->toBeTrue()
            ->and(array_key_exists('ocr_provider', $result))->toBeTrue();
    });

    test('getExtractedData returns proper array structure', function () {
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
        ];

        $extractor = new DriverEmiratesIdExtractor($ocrData);
        $result = $extractor->extractDriverEmiratesIdData()->getExtractedData();

        $expectedKeys = [
            'eid_number',
            'driver_name',
            'date_of_birth',
            'nationality',
            'sex',
            'ocr_processed_at',
            'ocr_model',
            'ocr_provider',
        ];

        foreach ($expectedKeys as $key) {
            expect(array_key_exists($key, $result))->toBeTrue("Missing key: {$key}");
        }
    });
});
