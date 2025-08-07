<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\Nationality;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class LifeAllocation extends BaseAllocation
{
    private const CAT_A = 'categoryA';
    private const CAT_B = 'categoryB';

    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::LifeAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    protected function getAdvisorEmails($storageKey = null)
    {
        $category = $this->evaluateCategory();
        $amount = $this->lead?->lifeQuote?->currency?->convertToAED((float) $this->lead?->lifeQuote?->sum_insured_value ?? 0);

        if (empty($amount)) {
            LoggerService::info('LifeAllocation: No amount found');

            return [];
        }

        if ($this->lead->isFIC(QuoteTypes::LIFE)) {
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule users');
            $users = $this->getFicRulesUsers();
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule  ', ['users_ids' => $users->pluck('id')->toArray()]);

            return $users->pluck('email')->toArray();
        }
        $santosh = 'santhosh.ganesan@insurancemarket.ae';
        $karuna = 'karuna.ramesh@insurancemarket.ae';
        $christy = 'christy.thomas@insurancemarket.ae';
        $katrina = 'katrina.guerrero@insurancemarket.ae';
        $larry = 'larry.bascon@insurancemarket.ae';

        $gaurav = 'gaurav.sharma@insurancemarket.ae';
        $vivian = 'vivian.sandel@insurancemarket.ae';
        $sourabh = 'sourabh.yadav@insurancemarket.ae';

        $emails = [];

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $emails;
        }

        if ($amount < 1000000 && in_array($category, [self::CAT_A])) {
            $emails = [$gaurav, $vivian];
        } elseif ($amount >= 1000000 && $amount <= 2000000 && in_array($category, [self::CAT_A])) {
            $emails = [$vivian];
        } elseif ($amount <= 2000000 && in_array($category, [self::CAT_B])) {
            $emails = [$gaurav, $sourabh];
        } elseif ($amount > 2000000 && in_array($category, [self::CAT_A, self::CAT_B])) {
            $emails = [$santosh, $karuna, $christy, $katrina, $larry];
        }

        return $emails;
    }

    private function getCountriesMapping()
    {
        $catACountryMapping = [
            'American',
            'Australian',
            'South African',
            'United Kingdom',
            'Lebanese',
            'Filipino',
            'New Zealander',
            'Canadian',
            'Russian',
            'Ukrainian',
            'French',
            'Spanish',
            'Swedish',
            'German',
            'Finnish',
            'Norwegian',
            'Polish',
            'Italian',
            'Romanian',
            'Belarusian',
            'Kazakhstani',
            'Greek',
            'Bulgarian',
            'Icelander',
            'Hungarian',
            'Portuguese',
            'Austrian',
            'Czech',
            'Serbian',
            'Irish',
            'Lithuanian',
            'Latvian',
            'Norwegian',
            'Croatian',
            'Herzegovinian',
            'Slovakian',
            'Estonian',
            'Danish',
            'Dutch',
            'Swiss',
            'Moldovan',
            'Belgian',
            'Albanian',
            'Macedonian',
            'Turkish',
            'Slovenian',
            'Montenegrin',
            'Kosovar',
            'Azerbaijani',
            'Georgian',
            'Luxembourger',
            'Faroese',
            'Andorran',
            'Maltese',
            'Liechtensteiner',
            'Sammarinese',
            'Gibraltar',
            'Monacan',
            'Vatican City',
            'Armenian',
            'Cypriot',
            'Greenlandic',
        ];

        $catBCountryMapping = cache()->remember('countries_category_mapping', now()->addHours(24), function () use ($catACountryMapping) {
            return Nationality::whereNotIn('code', [...$catACountryMapping])->pluck('code')->toArray();
        });

        return [
            self::CAT_A => $catACountryMapping,
            self::CAT_B => $catBCountryMapping,
        ];
    }

    private function evaluateCategory()
    {
        $countriesMapping = $this->getCountriesMapping();

        return in_array($this->lead->nationality?->code, $countriesMapping[self::CAT_A])
            ? self::CAT_A
            : self::CAT_B;
    }

    private function getFicRulesUsers()
    {
        $usersIds = app(RuleService::class)->getFicRulesUsers();

        return User::select('id', 'email')->whereIn('id', $usersIds)->get();
    }
}
