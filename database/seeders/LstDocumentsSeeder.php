<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class LstDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DocumentType::updateOrCreate([
            'code' => 'CPC',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CPC',
            'text' => 'Policy Certificate',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => 12,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'CTI',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CTI',
            'text' => 'Tax Invoice',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 13,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'CTIRBB',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CTIRBB',
            'text' => 'Tax Invoice Raised By Buyer',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 14,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'EID_CAR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'EID_CAR',
            'text' => 'Emirates ID (both sides)',
            'description' => 'Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 3,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'TR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'TR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 14,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'TAD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'TAD',
            'text' => 'Passing certificate (less than 30 days old)',
            'description' => "You may be required to submit additional documents to support the issuance of your policy. Any questions or concerns? Don't hesitate to contact your personal shopper who'll be happy to help.",

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 15,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'TAEA',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'TAEA',
            'text' => 'Additional Email Attachments',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 16,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'PP_CAR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'PP_CAR',
            'text' => 'Passport',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        DocumentType::updateOrCreate([
            'code' => 'VISA_CAR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'VISA_CAR',
            'text' => 'Visa',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 2,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);
        // CMUL	Vehicle license (both sides) or Dealer invoice copy or VCC		"ee"	1	car	.xlsx,.pdf,.jpeg,.jpg,.docx,.png	20	25	0	0	0	4	0	ISSUING_DOCUMENTS
        DocumentType::updateOrCreate([
            'code' => 'CMUL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMUL',
            'text' => 'Vehicle license (both sides) or Dealer invoice copy or VCC',
            'description' => 'Please share a copy of the current vehicle registration document for the vehicle you are insuring with us.
            If you are purchasing a vehicle please provide a copy of any of the following documents:
            Purchase Invoice
            Possession Certificate
            Hazaya / Vehicle Clearance Certificate (VCC)

            Any questions? Please contact your personal shopper for advice and assistance.',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.docx,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);
        // CTL	Trade License			1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	6	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CTL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CTL',
            'text' => 'Trade License',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.docx,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 6,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CCL	Company Letter			1	car	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	20	25	1	0	0	10	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CCL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CCL',
            'text' => 'Company Letter',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 10,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CAF	Application Form			1	car	.xlsx,.pdf,.jpeg,.jpg,.png	20	25	0	0	0	7	0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAF',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAF',
            'text' => 'Application Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 7,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // CTC	Final Terms & Conditions			1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	0	0	1	10	0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CTC',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CTC',
            'text' => 'Final Terms & Conditions',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 10,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // CCL	Census List			3	health	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	20	25	1	0	0	9	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CCL',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'CCL',
            'text' => 'Census List',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 9,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CPS	Policy Schedule			1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	5	25	1	1	1	11	0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CPS',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CPS',
            'text' => 'Policy Schedule',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => 11,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // DL_CAR	Driver’s license (both sides)		Please share a copy of your valid driving license with us. Don't have a valid one? Please contact your personal shopper for advice on how to proceed.
        // 1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0	4	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'DL_CAR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'DL_CAR',
            'text' => 'Driver’s license (both sides)',
            'description' => "Please share a copy of your valid driving license with us. Don't have a valid one? Please contact your personal shopper for advice on how to proceed.",

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 4,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // BAL	Broker Appointment letter		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	11	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BAL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'BAL',
            'text' => 'Broker Appointment letter',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 11,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // PHB	Policy Handbook		1	car	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	25	1	1	1		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'PHB',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'PHB',
            'text' => 'Policy Handbook',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // SOLDUNCON	Internal Proof		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	0	0	0		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'SOLDUNCON',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'SOLDUNCON',
            'text' => 'Internal Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // PS	Passport		4	life	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'PS',
            'quote_type_id' => QuoteTypes::LIFE->id(),
        ], [
            'code' => 'PS',
            'text' => 'Passport',
            'description' => '',

            'quote_type_id' => QuoteTypes::LIFE->id(),
            'folder_path' => 'life',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // MEPP	Member's Passport Copy	"eee"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	50	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEPP',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEPP',
            'text' => "Member's Passport Copy",
            'description' => 'Please upload the passport copy of the member to be insured.',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 50,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        //         MEV	Member's Visa Copy	"Please upload the visa copy of the member to be insured.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	4	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEV',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEV',
            'text' => "Member's Visa Copy",
            'description' => 'Please upload the visa copy of the member to be insured.',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 4,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        //         MEEID	Member's Emirates ID Copy	"Please upload the Emirates ID (front & back side) or Emirates ID Application Form copy of the member to be insured.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	40	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEEID',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEEID',
            'text' => "Member's Emirates ID Copy",
            'description' => 'Please upload the Emirates ID (front & back side) or Emirates ID Application Form copy of the member to be insured.',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 40,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        //         MEBC	UAE Birth Certificate (for Newborn)	"Please upload the UAE birth certificate for the newborn member up to 2 months old.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEBC',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEBC',
            'text' => 'UAE Birth Certificate (for Newborn)',
            'description' => 'Please upload the UAE birth certificate for the newborn member up to 2 months old.',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        //         MEDS	Discharge Summary (for Newborn)	"Please upload the discharge summary for members up to 6 months old.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEDS',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEDS',
            'text' => 'Discharge Summary (for Newborn)',
            'description' => 'Please upload the discharge summary for members up to 6 months old.',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);
        //         SDI	Sponsor's Documents - Individual	"Please upload the Sponsor's copy of Passport, Visa,    Emirates ID (front & back), Proof of Insurance

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'SDI',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'SDI',
            'text' => "Sponsor's Documents - Individual",
            'description' => "Please upload the Sponsor's copy of Passport, Visa, Emirates ID (front & back), Proof of Insurance",

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        //         SDC	Sponsor's Documents - Company	"Please upload a copy of the Trade License, VAT certificate & Establishment Card

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'SDC',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'SDC',
            'text' => "Sponsor's Documents - Company",
            'description' => 'Please upload a copy of the Trade License, VAT certificate & Establishment Card',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        //         OAPPD	Other Application Documents	"Please note that insurers may request further documentation as part of the underwriting process and we will let you know if this applies to your application.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	1	0	0		1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'OAPPD',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'OAPPD',
            'text' => 'Other Application Documents',
            'description' => 'Please note that insurers may request further documentation as part of the underwriting process and we will let you know if this applies to your application',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        //         CD	Confirmation Documents	"Please upload your signed benefit table, proof of payment, signed policy wordings here.

        // These documents will be provided once application documents have been validated and final premium has been released.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CD',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'CD',
            'text' => 'Confirmation Documents',
            'description' => 'Please upload your signed benefit table, proof of payment, signed policy wordings here.
            These documents will be provided once application documents have been validated and final premium has been released',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        //         MEMR	Medical Report (if applicable)	"Please upload the medical report/s of the member to be insured.

        // *Max size 25mb per file"	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	50	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'MEMR',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'MEMR',
            'text' => 'Medical Report (if applicable)',
            'description' => 'Please upload the medical report/s of the member to be insured',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 50,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // ECARD	E-card		3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	20	25	1	1	1		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'ECARD',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'ECARD',
            'text' => 'E-card',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // POLC	Policy Certificate		3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	15	25	1	1	1		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'POLC',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'POLC',
            'text' => 'Policy Certificate',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 15,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // POLW	Policy Wordings/Bond		3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	1		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'POLW',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'POLW',
            'text' => 'Policy Wordings/Bond',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // RV	Receipt		3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	1		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'RV',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'RV',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // TI	Tax Invoice	Tax Invoice or Debit Note (without the commission)	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	1		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TI',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'TI',
            'text' => 'Tax Invoice',
            'description' => 'Tax Invoice or Debit Note (without the commission)',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // TIRBB	TIRBB/Credit Note	Tax Invoice Raised by Buyer or Credit Note (document where commission is stated)	3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TIRBB',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'TIRBB',
            'text' => 'TIRBB/Credit Note',
            'description' => 'Tax Invoice Raised by Buyer or Credit Note (document where commission is stated)',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // TRAEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	8	travel	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TRAEID',
            'quote_type_id' => QuoteTypes::TRAVEL->id(),
        ], [
            'code' => 'TRAEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::TRAVEL->id(),
            'folder_path' => 'travel',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // BUSEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	5	business	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BUSEID',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'BUSEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // HOMEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	2	home	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HOMEID',
            'quote_type_id' => QuoteTypes::HOME->id(),
        ], [
            'code' => 'HOMEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::HOME->id(),
            'folder_path' => 'home',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // HEAEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	3	health	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HEAEID',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'HEAEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'home',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // LIFEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	4	life	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'LIFEID',
            'quote_type_id' => QuoteTypes::LIFE->id(),
        ], [
            'code' => 'LIFEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::LIFE->id(),
            'folder_path' => 'life',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // PETEID	Emirates ID (Front side & Back side)	Please share a copy of your Emirates ID application form to enable us to proceed.	9	pet	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	5	20	1	1	0	1	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'PETEID',
            'quote_type_id' => QuoteTypes::PET->id(),
        ], [
            'code' => 'PETEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your Emirates ID application form to enable us to proceed',

            'quote_type_id' => QuoteTypes::PET->id(),
            'folder_path' => 'pet',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 20,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CTI	Tax Invoice		6	bike	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	1	25	1	1	0	13	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CTI',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'CTI',
            'text' => 'Tax Invoice',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 1,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 13,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CTIRBB	Tax Invoice Raise by Buyer		6	bike	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	2	25	1	1	0	14	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CTIRBB',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'CTIRBB',
            'text' => 'Tax Invoice Raise by Buyer',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 2,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 14,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CEID	Emirates ID (Front side & Back side)	Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.	6	bike	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	20	25	1	1	0	3	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CEID',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'CEID',
            'text' => 'Emirates ID (Front side & Back side)',
            'description' => 'Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 3,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // TR	Receipt		6	bike	.xlsx,.pdf,.jpeg,.jpg,.png	20	25	1	1	0	14	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TR',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'TR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 14,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CCL	Company Letter		6	bike	.xlsx,.pdf,.jpeg,.jpg,.png	20	25	1	0	0	6	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CCL',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'CCL',
            'text' => 'Company Letter',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 6,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);
        // CAF	Application Form		6	bike	.xlsx,.pdf,.jpeg,.jpg,.png	20	25	1	0	0	7	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CAF',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'CAF',
            'text' => 'Application Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.xlsx,.pdf,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 7,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // VCP	Vehicle photos (as per format shared by the advisor)		1	car	.jpeg,.jpg,.png	20	25	0	0	0	62	1	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'VCP',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'VCP',
            'text' => 'Vehicle photos (as per format shared by the advisor)',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 62,

            'receive_from_customer' => 1,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // OCD	Other documents		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	13	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'OCD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'OCD',
            'text' => 'Other documents',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 13,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CCL	Census List		5	business	.xlsm,.xlsx	5	25	1	0	0	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CCL',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'CCL',
            'text' => 'Census List',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.xlsm,.xlsx',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CPD	Payment Proof		1	car	.png,.pdf,.jpeg,.jpg	15	30	0	0	0	4	0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CPD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        //  HPD	Payment Proof		3	health	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	4	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HPD',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'HPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // FTC_CAR	Final terms and conditions	sample description	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	9	25	0	0	0		0	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'FTC_CAR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'FTC_CAR',
            'text' => 'Final terms and conditions',
            'description' => 'sample description',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 9,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'MEMBER',
        ]);

        // TPD	Payment Proof		8	travel	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	4	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TPD',
            'quote_type_id' => QuoteTypes::TRAVEL->id(),
        ], [
            'code' => 'TPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::TRAVEL->id(),
            'folder_path' => 'car',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CPDR	Receipt		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	5	25	1	0	1	4	0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CPDR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // HPDR	Receipt		3	health	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	4	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HPDR',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'HPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // TPDR	Receipt		8	travel	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	4	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TPDR',
            'quote_type_id' => QuoteTypes::TRAVEL->id(),
        ], [
            'code' => 'TPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::TRAVEL->id(),
            'folder_path' => 'travel',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 4,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CYCEDD	Enhanced Due Diligence Form	For compliance use only.	10	cycle	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CYCEDD',
            'quote_type_id' => QuoteTypes::CYCLE->id(),
        ], [
            'code' => 'CYCEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => 'For compliance use only',

            'quote_type_id' => QuoteTypes::CYCLE->id(),
            'folder_path' => 'cycle',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // YACEDD	Enhanced Due Diligence Form	For compliance use only.	7	yacht	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'YACEDD',
            'quote_type_id' => QuoteTypes::YACHT->id(),
        ], [
            'code' => 'YACEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => 'For compliance use only',

            'quote_type_id' => QuoteTypes::YACHT->id(),
            'folder_path' => 'yacht',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // LPD	Payment Proof		4	life	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'LPD',
            'quote_type_id' => QuoteTypes::LIFE->id(),
        ], [
            'code' => 'LPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::LIFE->id(),
            'folder_path' => 'life',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // KYCDOC	KYC Document		3	kyc	.pdf	1	5	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'KYCDOC',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'KYCDOC',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'kyc',
            'accepted_files' => '.pdf',

            'max_files' => 1,
            'max_size' => 5,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // PPD	Payment Proof		9	pet	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'PPD',
            'quote_type_id' => QuoteTypes::PET->id(),
        ], [
            'code' => 'PPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::PET->id(),
            'folder_path' => 'pet',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);
        // PPDR	Receipt		9	pet	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'PPDR',
            'quote_type_id' => QuoteTypes::PET->id(),
        ], [
            'code' => 'PPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::PET->id(),
            'folder_path' => 'pet',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // BPD	Payment Proof		6	bike	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BPD',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'BPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // BPDR	Receipt		6	bike	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BPDR',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'BPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);
        // CYCPD	Payment Proof		10	cycle	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CYCPD',
            'quote_type_id' => QuoteTypes::CYCLE->id(),
        ], [
            'code' => 'CYCPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::CYCLE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CYCPDR	Receipt		10	cycle	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CYCPDR',
            'quote_type_id' => QuoteTypes::CYCLE->id(),
        ], [
            'code' => 'CYCPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::CYCLE->id(),
            'folder_path' => 'cycle',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // YPD	Payment Proof		7	yacht	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'YPD',
            'quote_type_id' => QuoteTypes::YACHT->id(),
        ], [
            'code' => 'YPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::YACHT->id(),
            'folder_path' => 'yacht',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // YPDR	Receipt		7	yacht	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'YPDR',
            'quote_type_id' => QuoteTypes::YACHT->id(),
        ], [
            'code' => 'YPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::YACHT->id(),
            'folder_path' => 'yacht',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CLPD	Payment Proof		5	business	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CLPD',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'CLPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CLPDR	Receipt		5	business	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CLPDR',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'CLPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // TRAEDD	Enhanced Due Diligence Form		8	travel	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'TRAEDD',
            'quote_type_id' => QuoteTypes::TRAVEL->id(),
        ], [
            'code' => 'TRAEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::TRAVEL->id(),
            'folder_path' => 'travel',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CAREDD	Enhanced Due Diligence Form		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	0	0	0		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAREDD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAREDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 0,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // HEAEDD	Enhanced Due Diligence Form		3	health	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HEAEDD',
            'quote_type_id' => QuoteTypes::HEALTH->id(),
        ], [
            'code' => 'HEAEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::HEALTH->id(),
            'folder_path' => 'health',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // LIFEDD	Enhanced Due Diligence Form		4	travel	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'LIFEDD',
            'quote_type_id' => QuoteTypes::TRAVEL->id(),
        ], [
            'code' => 'LIFEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::TRAVEL->id(),
            'folder_path' => 'travel',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // HOMEDD	Enhanced Due Diligence Form		2	home	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HOMEDD',
            'quote_type_id' => QuoteTypes::HOME->id(),
        ], [
            'code' => 'HOMEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::HOME->id(),
            'folder_path' => 'home',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // PETEDD	Enhanced Due Diligence Form		9	pet	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'PETEDD',
            'quote_type_id' => QuoteTypes::PET->id(),
        ], [
            'code' => 'PETEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::PET->id(),
            'folder_path' => 'pet',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // BIKEDD	Enhanced Due Diligence Form		6	bike	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BIKEDD',
            'quote_type_id' => QuoteTypes::BIKE->id(),
        ], [
            'code' => 'BIKEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::BIKE->id(),
            'folder_path' => 'bike',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // BUSEDD	Enhanced Due Diligence Form		5	business	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'BUSEDD',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'BUSEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // JETEDD	Enhanced Due Diligence Form		11	jetski	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'JETEDD',
            'quote_type_id' => QuoteTypes::JETSKI->id(),
        ], [
            'code' => 'JETEDD',
            'text' => 'Enhanced Due Diligence Form',
            'description' => '',

            'quote_type_id' => QuoteTypes::JETSKI->id(),
            'folder_path' => 'jetski',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // CAR_MULKIY	Registration card (Mulkiya)		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0	5	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CAR_MULKIY',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_MULKIY',
            'text' => 'Registration card (Mulkiya)',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => 5,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CEC	Establishment Card		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	7	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CEC',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CEC',
            'text' => 'Establishment Card',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 7,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CVAT	VAT Certificate or Undertaking letter for non-VAT		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	9	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CVAT',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CVAT',
            'text' => 'VAT Certificate or Undertaking letter for non-VAT',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 9,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // CKYCD	KYC (Know-Your-Customer) Documents		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0	12	1	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'CKYCD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CKYCD',
            'text' => 'KYC (Know-Your-Customer) Documents',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => 12,

            'receive_from_customer' => 1,
            'category' => 'QUOTE',
        ]);

        // HOMPD	Payment Proof		2	home	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HOMPD',
            'quote_type_id' => QuoteTypes::HOME->id(),
        ], [
            'code' => 'HOMPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::HOME->id(),
            'folder_path' => 'home',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // HOMPDR	Receipt		2	home	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'HOMPDR',
            'quote_type_id' => QuoteTypes::HOME->id(),
        ], [
            'code' => 'HOMPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::HOME->id(),
            'folder_path' => 'home',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // GMQPD	Payment Proof		5	business	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	1	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'GMQPD',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'GMQPD',
            'text' => 'Payment Proof',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 1,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // GMQPDR	Receipt		5	business	.png,.pdf,.jpeg,.jpg	15	30	1	0	1	2	0	QUOTE

        DocumentType::updateOrCreate([
            'code' => 'GMQPDR',
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
        ], [
            'code' => 'GMQPDR',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'folder_path' => 'business',
            'accepted_files' => '.png,.pdf,.jpeg,.jpg',

            'max_files' => 15,
            'max_size' => 30,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => 2,

            'receive_from_customer' => 0,
            'category' => 'QUOTE',
        ]);

        // HTC	Home test code	Home test code	6	home	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	25	25	1	0	0		0	QUOTE

        // DocumentType::updateOrCreate([
        //     'code' => 'HTC',
        //     'quote_type_id' => QuoteTypes::HOME->id()
        // ], [
        //     'code' => 'HTC',
        //     'text' => "Home test code",
        //     'description' => "Home test code",

        //     'quote_type_id' => QuoteTypes::HOME->id(),
        //     'folder_path' => 'home',
        //     'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

        //     'max_files' => 25,
        //     'max_size' => 25,
        //     'is_active' => 1,

        //     'is_required' => 0,
        //     'send_to_customer' => 0,
        //     'sort_order' => "",

        //     'receive_from_customer' => 0,
        //     'category' => 'QUOTE',
        // ]);

        // CMEM_EID	Emirates ID (both sides)	Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_EID',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_EID',
            'text' => 'Emirates ID (both sides)',
            'description' => 'Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_PP	Passport		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_PP',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_PP',
            'text' => 'Passport',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_VISA	Visa		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_VISA',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_VISA',
            'text' => 'Visa',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_CTL	Trade License		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_CTL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_CTL',
            'text' => 'Trade License',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_CCL	Company Letter		1	car	.pdf,.xlsx,.docx,.jpeg,.jpg,.png	20	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_CCL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_CCL',
            'text' => 'Company Letter',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 20,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_DL	Driver’s license (both sides)	Please share a copy of your valid driving license with us. Don't have a valid one? Please contact your personal shopper for advice on how to proceed.	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_DL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_DL',
            'text' => "Please share a copy of your valid driving license with us. Don't have a valid one? Please contact your personal shopper for advice on how to proceed.",
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_BAL	Broker Appointment letter		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_BAL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_BAL',
            'text' => 'Broker Appointment letter',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_OCD	Other documents		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_OCD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_OCD',
            'text' => 'Other documents',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_MULK	Registration card (Mulkiya)		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_MULK',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_MULK',
            'text' => 'Registration card (Mulkiya)	',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_CEC	Establishment Card		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_CEC',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_CEC',
            'text' => 'Establishment Card',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_CVAT	VAT Certificate or Undertaking letter for non-VAT		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_CVAT',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_CVAT',
            'text' => 'VAT Certificate or Undertaking letter for non-VAT	',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CMEM_CKYCD	KYC (Know-Your-Customer) Documents		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		1	MEMBER

        DocumentType::updateOrCreate([
            'code' => 'CMEM_CKYCD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CMEM_CKYCD',
            'text' => 'KYC (Know-Your-Customer) Documents',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 1,
            'category' => 'MEMBER',
        ]);

        // CAR_ECARD	E-card	E-card	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	5	25	1	0	1		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_ECARD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_ECARD',
            'text' => 'E-card',
            'description' => 'E-card',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // CAR_GL	Garage List	Garage List	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	5	25	1	0	1		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_GL',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_GL',
            'text' => 'Garage List',
            'description' => 'Garage List',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // MY_AF	myAlfred	myAlfred	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	5	25	1	1	1		0	ISSUING_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'MY_AF',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'MY_AF',
            'text' => 'myAlfred',
            'description' => 'myAlfred',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 5,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ISSUING_DOCUMENTS',
        ]);

        // CAR_CDOCS	Customer Documents (Endorsement)	Customer Documents (Endorsement)	1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_CDOCS',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_CDOCS',
            'text' => 'Customer Documents (Endorsement)',
            'description' => 'Customer Documents (Endorsement)',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // CUW_ECOR	Underwriter Email Correspondence		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CUW_ECOR',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CUW_ECOR',
            'text' => 'Underwriter Email Correspondence',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // CAR_PS_NCC	Payment Slip (Non CC)		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_PS_NCC',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_PS_NCC',
            'text' => 'Payment Slip (Non CC)',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // CAR_IRCPT	Receipt (Insurer)		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_IRCPT',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_IRCPT',
            'text' => 'Receipt (Insurer)',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // CAR_PP	Payment Proof (Insurer Collects)		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_PP',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_PP',
            'text' => 'Payment Proof (Insurer Collects)',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // CAR_PA	Payment Approval		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	0		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'CAR_PA',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'CAR_PA',
            'text' => 'Payment Approval',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 0,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // END_SCHED	Endorsed Schedule		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	1	1		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'END_SCHED',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'END_SCHED',
            'text' => 'Endorsed Schedule',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 1,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // END_CERT	Endorsed Certificate		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	1		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'END_CERT',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'END_CERT',
            'text' => 'Endorsed Certificate',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // END_ECARD	E-Card		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	1		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'END_ECARD',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'END_ECARD',
            'text' => 'E-Card',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // END_TI	Tax Invoice		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	1		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'END_TI',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'END_TI',
            'text' => 'Tax Invoice',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);

        // END_RCPT	Receipt		1	car	.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png	10	25	1	0	1		0	ENDORSEMENT_DOCUMENTS

        DocumentType::updateOrCreate([
            'code' => 'END_RCPT',
            'quote_type_id' => QuoteTypes::CAR->id(),
        ], [
            'code' => 'END_RCPT',
            'text' => 'Receipt',
            'description' => '',

            'quote_type_id' => QuoteTypes::CAR->id(),
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',

            'max_files' => 10,
            'max_size' => 25,
            'is_active' => 1,

            'is_required' => 0,
            'send_to_customer' => 1,
            'sort_order' => '',

            'receive_from_customer' => 0,
            'category' => 'ENDORSEMENT_DOCUMENTS',
        ]);
    }
}
