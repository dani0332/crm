<?php

namespace Database\Seeders;

use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CAR - START

        // New Lead
        $carNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 8])->first();
        if (! $carNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Price too high
        $carPriceTooHigh = DB::table('quote_status')->where('code', 'PriceTooHigh')->first();
        if (! $carPriceTooHigh) {
            $carPriceTooHighId = DB::table('quote_status')->insertGetId([
                'id' => 40,
                'code' => 'PriceTooHigh',
                'text' => 'Price too high',
                'text_ar' => 'Price too high',
                'is_active' => 1,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carPriceTooHighMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carPriceTooHighId])->first();
            if (! $carPriceTooHighMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carPriceTooHighId,
                    'sort_order' => 2,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Policy purchased before first call
        $carPolicyPurchasedBeforeFirstCall = DB::table('quote_status')->where('code', 'PolicyPurchasedBeforeFirstCall')->first();
        if (! $carPolicyPurchasedBeforeFirstCall) {
            $carPolicyPurchasedBeforeFirstCallId = DB::table('quote_status')->insertGetId([
                'id' => 41,
                'code' => 'PolicyPurchasedBeforeFirstCall',
                'text' => 'Policy purchased before first call',
                'text_ar' => 'Policy purchased before first call',
                'is_active' => 1,
                'sort_order' => 61,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carPolicyPurchasedBeforeFirstCallMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carPolicyPurchasedBeforeFirstCallId])->first();
            if (! $carPolicyPurchasedBeforeFirstCallMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carPolicyPurchasedBeforeFirstCallId,
                    'sort_order' => 3,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Not contactable-P&E
        $carNotContactablePe = DB::table('quote_status')->where('code', 'NotContactablePe')->first();
        if (! $carNotContactablePe) {
            $carNotContactablePeId = DB::table('quote_status')->insertGetId([
                'id' => 42,
                'code' => 'NotContactablePe',
                'text' => 'Not contactable-P&E',
                'text_ar' => 'Not contactable-P&E',
                'is_active' => 1,
                'sort_order' => 62,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNotContactablePeMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNotContactablePeId])->first();
            if (! $carNotContactablePeMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNotContactablePeId,
                    'sort_order' => 4,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Follow-up Call
        $carFollowupCall = DB::table('quote_status')->where('code', 'FollowupCall')->first();
        if (! $carFollowupCall) {
            $carFollowupCallId = DB::table('quote_status')->insertGetId([
                'id' => 43,
                'code' => 'FollowupCall',
                'text' => 'Follow-up Call',
                'text_ar' => 'Follow-up Call',
                'is_active' => 1,
                'sort_order' => 63,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carFollowupCallMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carFollowupCallId])->first();
            if (! $carFollowupCallMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carFollowupCallId,
                    'sort_order' => 5,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Interested
        $carInterested = DB::table('quote_status')->where('code', 'Interested')->first();
        if (! $carInterested) {
            $carInterestedId = DB::table('quote_status')->insertGetId([
                'id' => 44,
                'code' => 'Interested',
                'text' => 'Interested',
                'text_ar' => 'Interested',
                'is_active' => 1,
                'sort_order' => 64,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carInterestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carInterestedId])->first();
            if (! $carInterestedMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carInterestedId,
                    'sort_order' => 6,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // No Answer
        $carNoAnswer = DB::table('quote_status')->where('code', 'NoAnswer')->first();
        if (! $carNoAnswer) {
            $carNoAnswerId = DB::table('quote_status')->insertGetId([
                'id' => 45,
                'code' => 'NoAnswer',
                'text' => 'No Answer',
                'text_ar' => 'No Answer',
                'is_active' => 1,
                'sort_order' => 65,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNoAnswerMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNoAnswerId])->first();
            if (! $carNoAnswerMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNoAnswerId,
                    'sort_order' => 7,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Not Interested
        $carNotInterested = DB::table('quote_status')->where('code', 'NotInterested')->first();
        if (! $carNotInterested) {
            $carNotInterestedId = DB::table('quote_status')->insertGetId([
                'id' => 46,
                'code' => 'NotInterested',
                'text' => 'Not Interested',
                'text_ar' => 'Not Interested',
                'is_active' => 1,
                'sort_order' => 66,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNotInterestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNotInterestedId])->first();
            if (! $carNotInterestedMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNotInterestedId,
                    'sort_order' => 8,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Not Eligible for Insurance
        $carNotEligibleForInsurance = DB::table('quote_status')->where('code', 'NotEligibleForInsurance')->first();
        if (! $carNotEligibleForInsurance) {
            $carNotEligibleForInsuranceId = DB::table('quote_status')->insertGetId([
                'id' => 47,
                'code' => 'NotEligibleForInsurance',
                'text' => 'Not Eligible for Insurance',
                'text_ar' => 'Not Eligible for Insurance',
                'is_active' => 1,
                'sort_order' => 67,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNotEligibleForInsuranceMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNotEligibleForInsuranceId])->first();
            if (! $carNotEligibleForInsuranceMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNotEligibleForInsuranceId,
                    'sort_order' => 9,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // AFIA Renewal
        $carAfiaRenewal = DB::table('quote_status')->where('code', 'AfiaRenewal')->first();
        if (! $carAfiaRenewal) {
            $carAfiaRenewalId = DB::table('quote_status')->insertGetId([
                'id' => 48,
                'code' => 'AfiaRenewal',
                'text' => 'AFIA Renewal',
                'text_ar' => 'AFIA Renewal',
                'is_active' => 1,
                'sort_order' => 68,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carAfiaRenewalMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carAfiaRenewalId])->first();
            if (! $carAfiaRenewalMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carAfiaRenewalId,
                    'sort_order' => 10,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Not looking for motor insurance
        $carNotLookingForMotorInsurance = DB::table('quote_status')->where('code', 'NotLookingForMotorInsurance')->first();
        if (! $carNotLookingForMotorInsurance) {
            $carNotLookingForMotorInsuranceId = DB::table('quote_status')->insertGetId([
                'id' => 49,
                'code' => 'NotLookingForMotorInsurance',
                'text' => 'Not looking for motor insurance',
                'text_ar' => 'Not looking for motor insurance',
                'is_active' => 1,
                'sort_order' => 69,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNotLookingForMotorInsuranceMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNotLookingForMotorInsuranceId])->first();
            if (! $carNotLookingForMotorInsuranceMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNotLookingForMotorInsuranceId,
                    'sort_order' => 11,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Non-GCC Spec
        $carNonGccSpec = DB::table('quote_status')->where('code', 'NonGccSpec')->first();
        if (! $carNonGccSpec) {
            $carNonGccSpecId = DB::table('quote_status')->insertGetId([
                'id' => 50,
                'code' => 'NonGccSpec',
                'text' => 'Non-GCC Spec',
                'text_ar' => 'Non-GCC Spec',
                'is_active' => 1,
                'sort_order' => 70,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
            ]);

            $carNonGccSpecMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => $carNonGccSpecId])->first();
            if (! $carNonGccSpecMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => $carNonGccSpecId,
                    'sort_order' => 12,
                    'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Quoted
        $carQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 2])->first();
        if (! $carQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 2,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $carLostMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 17])->first();
        if (! $carLostMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 17,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $carDuplicateMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 35])->first();
        if (! $carDuplicateMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 35,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $carFakeMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 9])->first();
        if (! $carFakeMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 9,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // CAR - END

        // HEALTH - START

        // New Lead
        $healthNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 8])->first();
        if (! $healthNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $healthQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 30])->first();
        if (! $healthQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $healthQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 31])->first();
        if (! $healthQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $healthQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 2])->first();
        if (! $healthQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $healthFollowedUp = QuoteStatus::find(24);
        if (! $healthFollowedUp) {
            DB::insert("INSERT INTO `quote_status` (`id`, `code`, `text`, `text_ar`, `is_active`, `sort_order`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`, `uuid`, `created_by`, `updated_by`)
            VALUES(24, 'Followed Up', 'Followed Up', NULL, 1, 5, 0, '2021-02-01 08:47:52', '2021-02-01 08:47:52', NULL, '4cf834e6-79e2-11ec-954e-f23017e1271d', '', '');");
        }
        $healthFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 24])->first();
        if (! $healthFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // AML Screening Cleared
        $amlScreeningCleared = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 6])->first();
        if (! $amlScreeningCleared) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 6,
                'sort_order' => 11,
                'created_by' => 'daniyal.shahid@insurancemarket.ae',
                'updated_by' => 'daniyal.shahid@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // AML Screening Failed
        $amlScreeningFailed = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 7])->first();
        if (! $amlScreeningFailed) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 7,
                'sort_order' => 12,
                'created_by' => 'daniyal.shahid@insurancemarket.ae',
                'updated_by' => 'daniyal.shahid@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $healthInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 25])->first();
        if (! $healthInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $healthApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 26])->first();
        if (! $healthApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $healthMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 14])->first();
        if (! $healthMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $healthPendingWithUwMap = QuoteStatusMap::where(['quote_type_id' => 3, 'quote_status_id' => 27])->first();
        if ($healthPendingWithUwMap) {
            $healthPendingWithUwMap->delete();
        }

        // Application Submitted
        $healthApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 36])->first();
        if (! $healthApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $healthFtcPendingMap = QuoteStatusMap::where(['quote_type_id' => 3, 'quote_status_id' => 22])->first();
        if ($healthFtcPendingMap) {
            $healthFtcPendingMap->delete();
        }

        // FTC Sent
        $healthFtcSentMap = QuoteStatusMap::where(['quote_type_id' => 3, 'quote_status_id' => 10])->first();
        if ($healthFtcSentMap) {
            $healthFtcSentMap->delete();
        }

        // FTC Accepted
        $healthFtcAcceptedMap = QuoteStatusMap::where(['quote_type_id' => 3, 'quote_status_id' => 11])->first();
        if ($healthFtcAcceptedMap) {
            $healthFtcAcceptedMap->delete();
        }

        // FTC Resubmitted
        $healthFtcResubmittedMap = QuoteStatusMap::where(['quote_type_id' => 3, 'quote_status_id' => 12])->first();
        if ($healthFtcResubmittedMap) {
            $healthFtcResubmittedMap->delete();
        }

        // KYC Cleared
        $healthKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 19])->first();
        if (! $healthKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $healthPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 28])->first();
        if (! $healthPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $healthTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 15])->first();
        if (! $healthTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $healthPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 29])->first();
        if (! $healthPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $healthPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 33])->first();
        if (! $healthPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $healthLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 17])->first();
        if (! $healthLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $healthFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 9])->first();
        if (! $healthFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $healthDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 3, 'quote_status_id' => 35])->first();
        if (! $healthDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 3,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // HEALTH - END

        // TRAVEL - START

        // New Lead
        $travelNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 8])->first();
        if (! $travelNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $travelQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 30])->first();
        if (! $travelQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $travelQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 31])->first();
        if (! $travelQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $travelQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 2])->first();
        if (! $travelQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $travelFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 24])->first();
        if (! $travelFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $travelInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 25])->first();
        if (! $travelInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $travelApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 26])->first();
        if (! $travelApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $travelMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 14])->first();
        if (! $travelMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $travelPendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 27])->first();
        if (! $travelPendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $travelApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 36])->first();
        if (! $travelApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $travelFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 22])->first();
        if (! $travelFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $travelFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 10])->first();
        if (! $travelFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $travelFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 11])->first();
        if (! $travelFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $travelFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 12])->first();
        if (! $travelFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $travelKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 19])->first();
        if (! $travelKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $travelPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 28])->first();
        if (! $travelPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $travelTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 15])->first();
        if (! $travelTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $travelPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 29])->first();
        if (! $travelPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $travelPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 33])->first();
        if (! $travelPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $travelLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 17])->first();
        if (! $travelLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $travelFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 9])->first();
        if (! $travelFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $travelDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 8, 'quote_status_id' => 35])->first();
        if (! $travelDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 8,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // TRAVEL - END

        // HOME - START

        // New Lead
        $homeNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 8])->first();
        if (! $homeNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $homeQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 30])->first();
        if (! $homeQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $homeQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 31])->first();
        if (! $homeQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $homeQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 2])->first();
        if (! $homeQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $homeFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 24])->first();
        if (! $homeFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $homeInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 25])->first();
        if (! $homeInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $homeApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 26])->first();
        if (! $homeApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $homeMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 14])->first();
        if (! $homeMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $homePendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 27])->first();
        if (! $homePendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $homeApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 36])->first();
        if (! $homeApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $homeFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 22])->first();
        if (! $homeFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $homeFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 10])->first();
        if (! $homeFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $homeFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 11])->first();
        if (! $homeFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $homeFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 12])->first();
        if (! $homeFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $homeKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 19])->first();
        if (! $homeKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $homePaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 28])->first();
        if (! $homePaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $homeTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 15])->first();
        if (! $homeTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $homePolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 29])->first();
        if (! $homePolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $homePolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 33])->first();
        if (! $homePolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $homeLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 17])->first();
        if (! $homeLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $homeFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 9])->first();
        if (! $homeFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $homeDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 2, 'quote_status_id' => 35])->first();
        if (! $homeDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 2,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // HOME - END

        // LIFE - START

        // New Lead
        $lifeNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 8])->first();
        if (! $lifeNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $lifeQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 30])->first();
        if (! $lifeQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $lifeQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 31])->first();
        if (! $lifeQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $lifeQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 2])->first();
        if (! $lifeQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $lifeFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 24])->first();
        if (! $lifeFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $lifeInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 25])->first();
        if (! $lifeInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $lifeApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 26])->first();
        if (! $lifeApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $lifeMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 14])->first();
        if (! $lifeMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $lifePendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 27])->first();
        if (! $lifePendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $lifeApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 36])->first();
        if (! $lifeApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $lifeFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 22])->first();
        if (! $lifeFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $lifeFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 10])->first();
        if (! $lifeFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $lifeFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 11])->first();
        if (! $lifeFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $lifeFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 12])->first();
        if (! $lifeFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $lifeKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 19])->first();
        if (! $lifeKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $lifePaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 28])->first();
        if (! $lifePaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $lifeTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 15])->first();
        if (! $lifeTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $lifePolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 29])->first();
        if (! $lifePolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $lifePolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 33])->first();
        if (! $lifePolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $lifeLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 17])->first();
        if (! $lifeLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $lifeFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 9])->first();
        if (! $lifeFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $lifeDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 4, 'quote_status_id' => 35])->first();
        if (! $lifeDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 4,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // LIFE - END

        // BUSINESS - START

        // New Lead
        $businessNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 8])->first();
        if (! $businessNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $businessQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 30])->first();
        if (! $businessQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $businessQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 31])->first();
        if (! $businessQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $businessQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 2])->first();
        if (! $businessQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $businessFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 24])->first();
        if (! $businessFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $businessInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 25])->first();
        if (! $businessInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $businessApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 26])->first();
        if (! $businessApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $businessMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 14])->first();
        if (! $businessMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $businessPendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 27])->first();
        if (! $businessPendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $businessApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 36])->first();
        if (! $businessApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $businessFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 22])->first();
        if (! $businessFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $businessFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 10])->first();
        if (! $businessFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $businessFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 11])->first();
        if (! $businessFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $businessFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 12])->first();
        if (! $businessFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $businessKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 19])->first();
        if (! $businessKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $businessPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 28])->first();
        if (! $businessPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $businessTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 15])->first();
        if (! $businessTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $businessPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 29])->first();
        if (! $businessPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $businessPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 33])->first();
        if (! $businessPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $businessLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 17])->first();
        if (! $businessLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $businessFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 9])->first();
        if (! $businessFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $businessDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 5, 'quote_status_id' => 35])->first();
        if (! $businessDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 5,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // BUSINESS - END

        // BIKE - START

        // New Lead
        $bikeNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 8])->first();
        if (! $bikeNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $bikeQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 30])->first();
        if (! $bikeQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $bikeQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 31])->first();
        if (! $bikeQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $bikeQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 2])->first();
        if (! $bikeQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $bikeFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 24])->first();
        if (! $bikeFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $bikeInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 25])->first();
        if (! $bikeInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $bikeApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 26])->first();
        if (! $bikeApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $bikeMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 14])->first();
        if (! $bikeMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $bikePendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 27])->first();
        if (! $bikePendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $bikeApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 36])->first();
        if (! $bikeApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $bikeFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 22])->first();
        if (! $bikeFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $bikeFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 10])->first();
        if (! $bikeFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $bikeFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 11])->first();
        if (! $bikeFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $bikeFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 12])->first();
        if (! $bikeFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $bikeKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 19])->first();
        if (! $bikeKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $bikePaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 28])->first();
        if (! $bikePaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $bikeTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 15])->first();
        if (! $bikeTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $bikePolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 29])->first();
        if (! $bikePolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $bikePolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 33])->first();
        if (! $bikePolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $bikeLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 17])->first();
        if (! $bikeLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $bikeFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 9])->first();
        if (! $bikeFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $bikeDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 6, 'quote_status_id' => 35])->first();
        if (! $bikeDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 6,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // BIKE - END

        // YACHT - START

        // New Lead
        $yachtNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 8])->first();
        if (! $yachtNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $yachtQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 30])->first();
        if (! $yachtQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $yachtQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 31])->first();
        if (! $yachtQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $yachtQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 2])->first();
        if (! $yachtQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $yachtFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 24])->first();
        if (! $yachtFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $yachtInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 25])->first();
        if (! $yachtInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $yachtApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 26])->first();
        if (! $yachtApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $yachtMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 14])->first();
        if (! $yachtMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $yachtPendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 27])->first();
        if (! $yachtPendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $yachtApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 36])->first();
        if (! $yachtApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $yachtFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 22])->first();
        if (! $yachtFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $yachtFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 10])->first();
        if (! $yachtFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $yachtFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 11])->first();
        if (! $yachtFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $yachtFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 12])->first();
        if (! $yachtFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $yachtKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 19])->first();
        if (! $yachtKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $yachtPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 28])->first();
        if (! $yachtPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $yachtTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 15])->first();
        if (! $yachtTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $yachtPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 29])->first();
        if (! $yachtPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $yachtPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 33])->first();
        if (! $yachtPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $yachtLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 17])->first();
        if (! $yachtLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $yachtFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 9])->first();
        if (! $yachtFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $yachtDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 7, 'quote_status_id' => 35])->first();
        if (! $yachtDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 7,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // YACHT - END

        // PET - START

        // New Lead
        $petNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 8])->first();
        if (! $petNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $petQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 30])->first();
        if (! $petQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $petQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 31])->first();
        if (! $petQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $petQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 2])->first();
        if (! $petQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $petFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 24])->first();
        if (! $petFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $petInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 25])->first();
        if (! $petInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $petApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 26])->first();
        if (! $petApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $petMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 14])->first();
        if (! $petMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $petPendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 27])->first();
        if (! $petPendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $petApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 36])->first();
        if (! $petApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $petFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 22])->first();
        if (! $petFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $petFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 10])->first();
        if (! $petFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $petFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 11])->first();
        if (! $petFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $petFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 12])->first();
        if (! $petFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $petKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 19])->first();
        if (! $petKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $petPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 28])->first();
        if (! $petPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $petTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 15])->first();
        if (! $petTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $petPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 29])->first();
        if (! $petPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $petPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 33])->first();
        if (! $petPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $petLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 17])->first();
        if (! $petLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $petFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 9])->first();
        if (! $petFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $petDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 35])->first();
        if (! $petDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 9,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // PET - END

        // CYCLE - START
        // New Lead
        $cycleNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 8])->first();
        if (! $cycleNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $cycleQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 30])->first();
        if (! $cycleQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $cycleQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 31])->first();
        if (! $cycleQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $cycleQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 2])->first();
        if (! $cycleQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $cycleFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 24])->first();
        if (! $cycleFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $cycleInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 25])->first();
        if (! $cycleInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $cycleApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 26])->first();
        if (! $cycleApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $cycleMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 14])->first();
        if (! $cycleMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $cyclePendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 27])->first();
        if (! $cyclePendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $cycleApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 36])->first();
        if (! $cycleApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $cycleFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 22])->first();
        if (! $cycleFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $cycleFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 10])->first();
        if (! $cycleFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $cycleFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 11])->first();
        if (! $cycleFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $cycleFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 12])->first();
        if (! $cycleFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $cycleKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 19])->first();
        if (! $cycleKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $cyclePaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 28])->first();
        if (! $cyclePaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $cycleTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 15])->first();
        if (! $cycleTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $cyclePolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 29])->first();
        if (! $cyclePolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $cyclePolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 33])->first();
        if (! $cyclePolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $cycleLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 17])->first();
        if (! $cycleLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $cycleFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 9])->first();
        if (! $cycleFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $cycleDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 10, 'quote_status_id' => 35])->first();
        if (! $cycleDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 10,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // CYCLE - END

        // JETSKI - START
        // New Lead
        $jetskiNewLeadMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 8])->first();
        if (! $jetskiNewLeadMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 8,
                'sort_order' => 1,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualification Pending
        $jetskiQualificationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 30])->first();
        if (! $jetskiQualificationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 30,
                'sort_order' => 2,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qualified
        $jetskiQualifiedgMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 31])->first();
        if (! $jetskiQualifiedgMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 31,
                'sort_order' => 3,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Quoted
        $jetskiQuotedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 2])->first();
        if (! $jetskiQuotedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 2,
                'sort_order' => 4,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Followed Up
        $jetskiFollowedUpMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 24])->first();
        if (! $jetskiFollowedUpMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 24,
                'sort_order' => 5,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // In Negotiation
        $jetskiInNegotiationMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 25])->first();
        if (! $jetskiInNegotiationMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 25,
                'sort_order' => 6,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Pending
        $jetskiApplicationPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 26])->first();
        if (! $jetskiApplicationPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 26,
                'sort_order' => 7,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Missing Documents Requested
        $jetskiMissingDocumentsRequestedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 14])->first();
        if (! $jetskiMissingDocumentsRequestedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 14,
                'sort_order' => 8,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pending with UW
        $jetskiPendingWithUwMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 27])->first();
        if (! $jetskiPendingWithUwMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 27,
                'sort_order' => 9,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Application Submitted
        $jetskiApplicationSubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 36])->first();
        if (! $jetskiApplicationSubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 36,
                'sort_order' => 10,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Pending
        $jetskiFtcPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 22])->first();
        if (! $jetskiFtcPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 22,
                'sort_order' => 11,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Sent
        $jetskiFtcSentMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 10])->first();
        if (! $jetskiFtcSentMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 10,
                'sort_order' => 12,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Accepted
        $jetskiFtcAcceptedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 11])->first();
        if (! $jetskiFtcAcceptedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 11,
                'sort_order' => 13,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FTC Resubmitted
        $jetskiFtcResubmittedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 12])->first();
        if (! $jetskiFtcResubmittedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 12,
                'sort_order' => 14,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // KYC Cleared
        $jetskiKycClearedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 19])->first();
        if (! $jetskiKycClearedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 19,
                'sort_order' => 15,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Payment Pending
        $jetskiPaymentPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 28])->first();
        if (! $jetskiPaymentPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 28,
                'sort_order' => 16,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transaction Approved
        $jetskiTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 15])->first();
        if (! $jetskiTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Documents Pending
        $jetskiPolicyDocumentsPendingMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 29])->first();
        if (! $jetskiPolicyDocumentsPendingMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 29,
                'sort_order' => 18,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Policy Issued
        $jetskiPolicyIssuedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 33])->first();
        if (! $jetskiPolicyIssuedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 33,
                'sort_order' => 19,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Lost
        $jetskiLostdMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 17])->first();
        if (! $jetskiLostdMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 17,
                'sort_order' => 20,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fake
        $jetskiFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 9])->first();
        if (! $jetskiFakedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 9,
                'sort_order' => 21,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Duplicate
        $jetskiDuplicatedMap = DB::table('quote_status_map')->where(['quote_type_id' => 11, 'quote_status_id' => 35])->first();
        if (! $jetskiDuplicatedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 11,
                'quote_status_id' => 35,
                'sort_order' => 22,
                'created_by' => 'bilalsaeed@insurancemarket.ae',
                'updated_by' => 'bilalsaeed@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // JETSKI - END
    }
}
