<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class tmLeadStatusCode extends Enum
{
    const PipelineNoInfo = 'PipelineNoInfo';
    const PipelineImmediate = 'PipelineImmediate';
    const PipelineFuture = 'PipelineFuture';
    const NoAnswer = 'NoAnswer';
    const SwitchedOff = 'SwitchedOff';
    const DealingWithAnAdvisor = 'DealingWithAnAdvisor';
    const NotContactablePE = 'NotContactablePE';
    const CarSold = 'CarSold';
    const NotEligible = 'NotEligible';
    const NotInterested = 'NotInterested';
    const PurchasedBeforeFirstCall = 'PurchasedBeforeFirstCall';
    const PurchasedFromCompetitor = 'PurchasedFromCompetitorr';
    const RevivedByNewBusiness = 'RevivedByNewBusiness';
    const RevivedByRenewals = 'RevivedByRenewals';
    const WrongNumber = 'WrongNumber';
    const DONOTCALL = 'DONOTCALL';
    const Duplicate = 'Duplicate';
    const Revived = 'Revived';
    const Recycled = 'Recycled';
    const NewLead = 'NewLead';
}
