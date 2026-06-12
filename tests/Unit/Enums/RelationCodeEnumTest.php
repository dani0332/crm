<?php

declare(strict_types=1);

use App\Enums\RelationCodeEnum;

describe('RelationCodeEnum cases', function () {
    test('has correct string values', function () {
        expect(RelationCodeEnum::SPOUSE->value)->toBe('relSpouse')
            ->and(RelationCodeEnum::CHILD->value)->toBe('relChild')
            ->and(RelationCodeEnum::PARENT->value)->toBe('relParent')
            ->and(RelationCodeEnum::SIBLING->value)->toBe('relSibling')
            ->and(RelationCodeEnum::RELATIVES->value)->toBe('relOtherRelatives')
            ->and(RelationCodeEnum::DOMESTIC_WORKER->value)->toBe('relDomesticWorker')
            ->and(RelationCodeEnum::SELF->value)->toBe('Self');
    });

    test('can be instantiated from string value', function () {
        expect(RelationCodeEnum::from('Self'))->toBe(RelationCodeEnum::SELF)
            ->and(RelationCodeEnum::from('relSpouse'))->toBe(RelationCodeEnum::SPOUSE)
            ->and(RelationCodeEnum::from('relChild'))->toBe(RelationCodeEnum::CHILD)
            ->and(RelationCodeEnum::from('relParent'))->toBe(RelationCodeEnum::PARENT)
            ->and(RelationCodeEnum::from('relSibling'))->toBe(RelationCodeEnum::SIBLING)
            ->and(RelationCodeEnum::from('relOtherRelatives'))->toBe(RelationCodeEnum::RELATIVES)
            ->and(RelationCodeEnum::from('relDomesticWorker'))->toBe(RelationCodeEnum::DOMESTIC_WORKER);
    });

    test('tryFrom returns null for unknown value', function () {
        expect(RelationCodeEnum::tryFrom('relSiblingOrRelatives'))->toBeNull()
            ->and(RelationCodeEnum::tryFrom('unknown'))->toBeNull();
    });
});

describe('RelationCodeEnum::policyHolderRelationMap', function () {
    test('returns an array keyed by all non-self relation codes', function () {
        $map = RelationCodeEnum::policyHolderRelationMap();

        expect($map)->toBeArray()
            ->toHaveKey(RelationCodeEnum::SPOUSE->value)
            ->toHaveKey(RelationCodeEnum::CHILD->value)
            ->toHaveKey(RelationCodeEnum::PARENT->value)
            ->toHaveKey(RelationCodeEnum::SIBLING->value)
            ->toHaveKey(RelationCodeEnum::RELATIVES->value);
    });

    test('each inner map contains all expected relation code keys', function () {
        $map = RelationCodeEnum::policyHolderRelationMap();
        $expectedKeys = [
            RelationCodeEnum::SELF->value,
            RelationCodeEnum::SPOUSE->value,
            RelationCodeEnum::CHILD->value,
            RelationCodeEnum::PARENT->value,
            RelationCodeEnum::SIBLING->value,
            RelationCodeEnum::RELATIVES->value,
        ];

        foreach ($map as $innerMap) {
            foreach ($expectedKeys as $key) {
                expect($innerMap)->toHaveKey($key);
            }
        }
    });

    // Case 1: new policyholder was spouse
    describe('Case 1 – new policyholder was spouse', function () {
        test('old self becomes spouse', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SPOUSE->value][RelationCodeEnum::SELF->value])
                ->toBe(RelationCodeEnum::SPOUSE->value);
        });

        test('other spouses remain spouse', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SPOUSE->value][RelationCodeEnum::SPOUSE->value])
                ->toBe(RelationCodeEnum::SPOUSE->value);
        });

        test('child remains child', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SPOUSE->value][RelationCodeEnum::CHILD->value])
                ->toBe(RelationCodeEnum::CHILD->value);
        });

        test('parent and sibling become relatives', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SPOUSE->value][RelationCodeEnum::PARENT->value])
                ->toBe(RelationCodeEnum::RELATIVES->value)
                ->and($map[RelationCodeEnum::SPOUSE->value][RelationCodeEnum::SIBLING->value])
                ->toBe(RelationCodeEnum::RELATIVES->value);
        });
    });

    // Case 2: new policyholder was child
    describe('Case 2 – new policyholder was child', function () {
        test('old self and spouse become parent', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::CHILD->value][RelationCodeEnum::SELF->value])
                ->toBe(RelationCodeEnum::PARENT->value)
                ->and($map[RelationCodeEnum::CHILD->value][RelationCodeEnum::SPOUSE->value])
                ->toBe(RelationCodeEnum::PARENT->value);
        });

        test('other children become sibling', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::CHILD->value][RelationCodeEnum::CHILD->value])
                ->toBe(RelationCodeEnum::SIBLING->value);
        });

        test('parent and sibling become relatives', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::CHILD->value][RelationCodeEnum::PARENT->value])
                ->toBe(RelationCodeEnum::RELATIVES->value)
                ->and($map[RelationCodeEnum::CHILD->value][RelationCodeEnum::SIBLING->value])
                ->toBe(RelationCodeEnum::RELATIVES->value);
        });
    });

    // Case 3: new policyholder was parent
    describe('Case 3 – new policyholder was parent', function () {
        test('old self becomes child', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::PARENT->value][RelationCodeEnum::SELF->value])
                ->toBe(RelationCodeEnum::CHILD->value);
        });

        test('other parents become spouse', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::PARENT->value][RelationCodeEnum::PARENT->value])
                ->toBe(RelationCodeEnum::SPOUSE->value);
        });

        test('sibling becomes child', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::PARENT->value][RelationCodeEnum::SIBLING->value])
                ->toBe(RelationCodeEnum::CHILD->value);
        });

        test('spouse and child become relatives', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::PARENT->value][RelationCodeEnum::SPOUSE->value])
                ->toBe(RelationCodeEnum::RELATIVES->value)
                ->and($map[RelationCodeEnum::PARENT->value][RelationCodeEnum::CHILD->value])
                ->toBe(RelationCodeEnum::RELATIVES->value);
        });
    });

    // Case 4: new policyholder was sibling
    describe('Case 4 – new policyholder was sibling', function () {
        test('old self becomes sibling', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SIBLING->value][RelationCodeEnum::SELF->value])
                ->toBe(RelationCodeEnum::SIBLING->value);
        });

        test('parent remains parent', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SIBLING->value][RelationCodeEnum::PARENT->value])
                ->toBe(RelationCodeEnum::PARENT->value);
        });

        test('other siblings remain sibling', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SIBLING->value][RelationCodeEnum::SIBLING->value])
                ->toBe(RelationCodeEnum::SIBLING->value);
        });

        test('spouse and child become relatives', function () {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::SIBLING->value][RelationCodeEnum::SPOUSE->value])
                ->toBe(RelationCodeEnum::RELATIVES->value)
                ->and($map[RelationCodeEnum::SIBLING->value][RelationCodeEnum::CHILD->value])
                ->toBe(RelationCodeEnum::RELATIVES->value);
        });
    });

    // Case 5: new policyholder was other relatives
    describe('Case 5 – new policyholder was other relatives', function () {
        test('every relation becomes relatives', function (string $existing) {
            $map = RelationCodeEnum::policyHolderRelationMap();
            expect($map[RelationCodeEnum::RELATIVES->value][$existing])
                ->toBe(RelationCodeEnum::RELATIVES->value);
        })->with([
            'self' => [RelationCodeEnum::SELF->value],
            'spouse' => [RelationCodeEnum::SPOUSE->value],
            'child' => [RelationCodeEnum::CHILD->value],
            'parent' => [RelationCodeEnum::PARENT->value],
            'sibling' => [RelationCodeEnum::SIBLING->value],
            'relatives' => [RelationCodeEnum::RELATIVES->value],
        ]);
    });
});
