<?php

namespace Database\Seeders;

use App\Enums\ClaimsEnum;
use App\Enums\QuoteTypeId;
use App\Models\Lookup;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClaimStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $claimAccessTypeSystem = Lookup::whereCode(ClaimsEnum::CLAIM_REQUEST_ACCESS_TYPE_SYSTEM_GENERATED_CODE->value)->first(); // 68540
        $claimAccessTypeManual = Lookup::whereCode(ClaimsEnum::CLAIM_REQUEST_ACCESS_TYPE_MANUAL_CODE->value)->first(); // 68541
        $claimReqTypeReimbursement = Lookup::whereCode(ClaimsEnum::CLAIM_REQUEST_TYPE_REIMBURSEMENT_CODE->value)->first(); // 68534
        $claimReqTypePendingApproval = Lookup::whereCode(ClaimsEnum::CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE->value)->first(); // 68535
        $claimReqTypeAskQuestion = Lookup::whereCode(ClaimsEnum::CLAIM_REQUEST_TYPE_ASK_A_QUESTION_CODE->value)->first(); // 68536

        // General Claim Statuses
        $generalStatuses = [
            ['text' => 'Open', 'description' => null, 'is_active' => 1, 'sort_order' => 1, 'claim_request_type_id' => null, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Close', 'description' => null, 'is_active' => 1, 'sort_order' => 2, 'claim_request_type_id' => null, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Motor (Car) Claim Statuses - quote_type_id = 1
        $carStatuses = [
            ['text' => 'New claim', 'description' => 'Your claim has been received and is being reviewed by your claims manager.', 'is_active' => 1, 'sort_order' => 1, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim initiated', 'description' => 'Your claim is initiated and awaiting claim registration, which usually takes 24-48 working hours. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 2, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim registered and awaiting inspection', 'description' => 'Your claim has been registered and is awaiting damage inspection.', 'is_active' => 1, 'sort_order' => 3, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Estimate under review', 'description' => 'Your repair estimate is received and awaiting approval.', 'is_active' => 1, 'sort_order' => 4, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Repair approved & work in progress', 'description' => 'The workshop has been granted approval to start the repairs.', 'is_active' => 1, 'sort_order' => 5, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Parts ordered', 'description' => 'Parts needed to repair your vehicle are ordered and currently awaited.', 'is_active' => 1, 'sort_order' => 6, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Parts on backorder', 'description' => 'Some parts needed to repair your vehicle are currently unavailable and will be ordered as soon as they are back in stock.', 'is_active' => 1, 'sort_order' => 7, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Parts delayed', 'description' => 'Some parts needed to repair your vehicle are still awaited.', 'is_active' => 1, 'sort_order' => 8, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Parts arrived & work in progress', 'description' => 'Parts needed to repair your vehicle have arrived and repair is in progress.', 'is_active' => 1, 'sort_order' => 9, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Hire car requested', 'description' => 'Your request for a hire/rental car is under review.', 'is_active' => 1, 'sort_order' => 10, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Hire car approved', 'description' => 'Your request for a hire car has been approved.', 'is_active' => 1, 'sort_order' => 11, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Hire car refund in progress', 'description' => 'Your hire cost refund is in progress.', 'is_active' => 1, 'sort_order' => 12, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Car ready for collection', 'description' => 'The workshop has completed the approved repairs, and your car is ready for collection.', 'is_active' => 1, 'sort_order' => 13, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Repair completed and claim settled', 'description' => 'Your claim is now settled.', 'is_active' => 1, 'sort_order' => 14, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Total loss approved', 'description' => 'Major damage was found, and a total loss settlement has been approved.', 'is_active' => 1, 'sort_order' => 15, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Total Loss Offer Letter shared', 'description' => 'An offer for your vehicle\'s total loss settlement has been shared.', 'is_active' => 1, 'sort_order' => 16, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Total loss payment in progress', 'description' => 'Payment towards your vehicle\'s total loss settlement is in progress, this usually takes 10-15 working days. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 17, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Total loss paid and claim settled', 'description' => 'Your total loss amount has been paid, and your claim is now settled.', 'is_active' => 1, 'sort_order' => 18, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Cash loss approved', 'description' => 'A cash settlement has been approved.', 'is_active' => 1, 'sort_order' => 19, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Cash loss payment inprogress', 'description' => 'Payment towards your vehicle\'s cash loss settlement is in progress, this usually takes 10-15 working days. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 20, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Cash loss paid and claim settled', 'description' => 'Your cash settlement amount has been paid, and your claim is now settled.', 'is_active' => 1, 'sort_order' => 21, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim withdrawn', 'description' => 'We understand you\'ve decided not to proceed with the claim. If you have any questions or change your mind, please contact us.', 'is_active' => 1, 'sort_order' => 22, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim denied', 'description' => 'We regret to inform you that your claim has been denied. If you need further clarification, please contact us.', 'is_active' => 1, 'sort_order' => 23, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Additional documents awaited', 'description' => 'We\'ve requested additional information or documents to proceed with your claim. Kindly provide these at your earliest convenience.', 'is_active' => 1, 'sort_order' => 24, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Documents uploaded', 'description' => 'The documents you uploaded are received and under review.', 'is_active' => 1, 'sort_order' => 25, 'claim_request_type_id' => null, 'quote_type_id' => QuoteTypeId::Car, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Non-Motor Claim Statuses Template
        $nonMotorStatuses = [
            ['text' => 'New claim', 'description' => 'Your claim has been received and is being reviewed by your claims manager.', 'sort_order' => 1, 'access_type_id' => $claimAccessTypeSystem?->id],
            ['text' => 'Claim initiated', 'description' => 'Your claim is initiated and awaiting claim registration, which usually takes 24-48 working hours. We\'re working to speed up the process.', 'sort_order' => 2, 'access_type_id' => $claimAccessTypeSystem?->id],
            ['text' => 'Claim registered', 'description' => 'Your claim has been registered and is awaiting survey.', 'sort_order' => 3, 'access_type_id' => $claimAccessTypeSystem?->id],
            ['text' => 'Survey in progress', 'description' => 'Your claim is under review by a surveyor.', 'sort_order' => 4, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Claim under review', 'description' => 'Your claims assessment report is awaiting approval from the insurer.', 'sort_order' => 5, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Claim approved', 'description' => 'Your claim has been approved and settlement is in progress.', 'sort_order' => 6, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Claim denied', 'description' => 'We regret to inform you that your claim has been denied. If you need further clarification, please contact us.', 'sort_order' => 7, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Settlement in progress', 'description' => 'Kindly note that your claim settlement is in progress.', 'sort_order' => 8, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Claim paid', 'description' => 'Your claim has been paid and is now settled.', 'sort_order' => 9, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Claim withdrawn', 'description' => 'We acknowledge your decision not to proceed with the claim. If you have any questions or change your mind, please feel free to contact us for assistance.', 'sort_order' => 10, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Additional documents awaited', 'description' => 'We\'ve requested additional information or documents to proceed with your claim. Kindly provide these at your earliest convenience.', 'sort_order' => 11, 'access_type_id' => $claimAccessTypeManual?->id],
            ['text' => 'Documents uploaded', 'description' => 'The documents you uploaded are received and under review.', 'sort_order' => 12, 'access_type_id' => $claimAccessTypeSystem?->id],
        ];

        // Quote Type IDs for Non-Motor
        $nonMotorQuoteTypes = [
            QuoteTypeId::Life, QuoteTypeId::Home, QuoteTypeId::Business, QuoteTypeId::Bike, QuoteTypeId::Yacht, QuoteTypeId::Travel, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski, QuoteTypeId::TradeCredit,
            QuoteTypeId::GroupMedical, QuoteTypeId::Corpline, QuoteTypeId::CompanyCar, QuoteTypeId::JobLoss, QuoteTypeId::JBLS, QuoteTypeId::Savings,
        ];

        // Health Claim Statuses
        $healthReimbursementStatuses = [
            ['text' => 'New claim', 'description' => 'Your claim has been received and is being reviewed by your claims manager.', 'is_active' => 1, 'sort_order' => 1, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim initiated', 'description' => 'Your claim is initiated and awaiting claim registration, which usually takes 24-48 working hours. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 2, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Additional documents awaited', 'description' => 'We\'ve requested additional information or documents to proceed with your claim. Kindly provide these at your earliest convenience.', 'is_active' => 1, 'sort_order' => 3, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claims Registered', 'description' => 'Your claim has been successfully registered and is currently being processed. The expected turnaround time is 15–21 working days. We\'ll keep you informed with timely updates throughout the process.', 'is_active' => 1, 'sort_order' => 4, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Under Review', 'description' => 'Your claims assessment report is awaiting approval from the insurer.', 'is_active' => 1, 'sort_order' => 5, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Approved', 'description' => 'Your claim has been approved and settlement is in progress.', 'is_active' => 1, 'sort_order' => 6, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Partially Approved', 'description' => 'Your claim has been partially approved.', 'is_active' => 1, 'sort_order' => 7, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Pending for Additional Information', 'description' => 'Your claim has been reviewed and there is a requirement of an additional information. Kindly provide these at your earliest convenience.', 'is_active' => 1, 'sort_order' => 8, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Reprocessing', 'description' => 'Your claim is under re-evaluation. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 9, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Denied', 'description' => 'We regret to inform you that your claim has been denied. If you need further clarification, please contact us.', 'is_active' => 1, 'sort_order' => 10, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Settlement in progress', 'description' => 'Kindly note that your claim settlement is in progress.', 'is_active' => 1, 'sort_order' => 11, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Paid', 'description' => 'Your claim amount has been paid and claim is now settled.', 'is_active' => 1, 'sort_order' => 12, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Closed', 'description' => 'Your claim has been closed due to absence of a response from your end. Should you need any further assistance, please contact us.', 'is_active' => 1, 'sort_order' => 13, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Claim Withdrawn', 'description' => 'We acknowledge your decision not to proceed with the claim. If you have any questions or change your mind, please feel free to contact us for assistance.', 'is_active' => 1, 'sort_order' => 14, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Documents uploaded', 'description' => 'The documents you uploaded are received and under review.', 'is_active' => 1, 'sort_order' => 15, 'claim_request_type_id' => $claimReqTypeReimbursement?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        $healthPendingApprovalsStatuses = [
            ['text' => 'New request', 'description' => 'Your request has been received and is being reviewed by your claims manager.', 'is_active' => 1, 'sort_order' => 1, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Under Evaluation', 'description' => 'Your request is under evaluation. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 2, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Additional documents awaited', 'description' => 'We\'re awaiting required documents from the medical facility to proceed with the request. Please follow up with the facility to provide these to the TPA as soon as possible', 'is_active' => 1, 'sort_order' => 3, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Under Re-evaluation', 'description' => 'Your request is under re-evaluation. We\'re working to speed up the process.', 'is_active' => 1, 'sort_order' => 4, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Partially Approved', 'description' => 'Your request is partially approved. (Reason)', 'is_active' => 1, 'sort_order' => 5, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Request Denied', 'description' => 'We regret to inform you that your request has been denied. (Reasons)', 'is_active' => 1, 'sort_order' => 6, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Request Approved', 'description' => 'Your request is approved.', 'is_active' => 1, 'sort_order' => 7, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Documents uploaded', 'description' => 'The documents you uploaded are received and under review.', 'is_active' => 1, 'sort_order' => 8, 'claim_request_type_id' => $claimReqTypePendingApproval?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        $healthAskQuestionStatuses = [
            ['text' => 'New request', 'description' => 'Your query has been received and is being reviewed by your claims manager.', 'is_active' => 1, 'sort_order' => 1, 'claim_request_type_id' => $claimReqTypeAskQuestion?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeSystem?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Under Review', 'description' => 'Thank you for your patience. We\'re reviewing your query and will update you shortly.', 'is_active' => 1, 'sort_order' => 2, 'claim_request_type_id' => $claimReqTypeAskQuestion?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Answered & Closed', 'description' => 'Your query has been answered and closed.', 'is_active' => 1, 'sort_order' => 3, 'claim_request_type_id' => $claimReqTypeAskQuestion?->id, 'quote_type_id' => QuoteTypeId::Health, 'access_type_id' => $claimAccessTypeManual?->id, 'parent' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Insert data in chunks to avoid memory issues
        $this->insertInChunks('claim_statuses', $generalStatuses);
        $this->insertInChunks('claim_statuses', $carStatuses);

        // Insert Non-Motor statuses for each quote type
        foreach ($nonMotorQuoteTypes as $quoteTypeId) {
            $statusesToInsert = [];
            foreach ($nonMotorStatuses as $status) {
                $statusesToInsert[] = [
                    'text' => $status['text'],
                    'description' => $status['description'],
                    'is_active' => 1,
                    'sort_order' => $status['sort_order'],
                    'claim_request_type_id' => null,
                    'quote_type_id' => $quoteTypeId,
                    'access_type_id' => $status['access_type_id'],
                    'parent' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->insertInChunks('claim_statuses', $statusesToInsert);
        }

        // Insert Health statuses
        $this->insertInChunks('claim_statuses', $healthReimbursementStatuses);
        $this->insertInChunks('claim_statuses', $healthPendingApprovalsStatuses);
        $this->insertInChunks('claim_statuses', $healthAskQuestionStatuses);

        $this->command->info('Claim statuses seeded successfully!');
    }

    /**
     * Insert data in chunks to avoid memory issues
     */
    private function insertInChunks(string $table, array $data, int $chunkSize = 100): void
    {
        $chunks = array_chunk($data, $chunkSize);
        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
