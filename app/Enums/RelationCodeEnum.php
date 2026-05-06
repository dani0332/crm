<?php

namespace App\Enums;

enum RelationCodeEnum: string
{
    case SPOUSE = 'relSpouse';
    case CHILD = 'relChild';
    case PARENT = 'relParent';
    case SIBLING = 'relSibling';
    case RELATIVES = 'relOtherRelatives';
    case DOMESTIC_WORKER = 'relDomesticWorker';
    case SELF = 'Self';

    /**
     * When a new policyholder is designated, every other insured member's relation_code
     * must be recalculated relative to the new policyholder.
     *
     * The outer key is the NEW policyholder's current relation_code.
     * The inner key is an existing member's current relation_code.
     * The value is what that member's relation_code should become.
     *
     * Case 1 – new PH was spouse
     * Case 2 – new PH was child
     * Case 3 – new PH was parent
     * Case 4 – new PH was sibling
     * Case 5 – new PH was other relatives
     *
     * The new policyholder itself always becomes SELF (handled separately in code).
     *
     * @return array<string, array<string, string>>
     */
    public static function policyHolderRelationMap(): array
    {
        $self = self::SELF->value;
        $spouse = self::SPOUSE->value;
        $child = self::CHILD->value;
        $parent = self::PARENT->value;
        $sibling = self::SIBLING->value;
        $relatives = self::RELATIVES->value;

        return [
            // Case 1: new policyholder was spouse
            // Other spouses remain spouse (multiple spouses allowed)
            $spouse => [
                $self => $spouse,
                $spouse => $spouse,
                $child => $child,
                $parent => $relatives,
                $sibling => $relatives,
                $relatives => $relatives,
            ],
            // Case 2: new policyholder was child
            // Other children become siblings of the new policyholder
            $child => [
                $self => $parent,
                $spouse => $parent,
                $child => $sibling,
                $parent => $relatives,
                $sibling => $relatives,
                $relatives => $relatives,
            ],
            // Case 3: new policyholder was parent
            // Other parents become spouses of the new policyholder
            $parent => [
                $self => $child,
                $spouse => $relatives,
                $child => $relatives,
                $parent => $spouse,
                $sibling => $child,
                $relatives => $relatives,
            ],
            // Case 4: new policyholder was sibling
            // Other siblings remain siblings of the new policyholder
            $sibling => [
                $self => $sibling,
                $spouse => $relatives,
                $child => $relatives,
                $parent => $parent,
                $sibling => $sibling,
                $relatives => $relatives,
            ],
            // Case 5: new policyholder was other relatives
            $relatives => [
                $self => $relatives,
                $spouse => $relatives,
                $child => $relatives,
                $parent => $relatives,
                $sibling => $relatives,
                $relatives => $relatives,
            ],
        ];
    }
}
