<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LostReasonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('lost_reasons')->insert([
            [
                'text' => 'Unresponsive via email, call and Whatsapp (more than 3 attempts)',
                'text_ar' => 'Unresponsive via email, call and Whatsapp (more than 3 attempts)',
            ],
            [
                'text' => 'Lost to competing insurance provider',
                'text_ar' => 'Lost to competing insurance provider',
            ],
            [
                'text' => 'Not interested in plans offered',
                'text_ar' => 'Not interested in plans offered',
            ],
            [
                'text' => 'Lost to competing broker',
                'text_ar' => 'Lost to competing broker',
            ],
            [
                'text' => 'Already purchased insurance elsewhere',
                'text_ar' => 'Already purchased insurance elsewhere',
            ],
            [
                'text' => 'Non-renewable',
                'text_ar' => 'Non-renewable',
            ],
            [
                'text' => 'No longer requires insurance',
                'text_ar' => 'No longer requires insurance',
            ],
            [
                'text' => 'Unhappy with our service',
                'text_ar' => 'Unhappy with our service',
            ],
            [
                'text' => 'Delays with advisor',
                'text_ar' => 'Delays with advisor',
            ],
            [
                'text' => 'Risk not covered',
                'text_ar' => 'Risk not covered',
            ],
            [
                'text' => 'Endorsed to colleague',
                'text_ar' => 'Endorsed to colleague',
            ],
            [
                'text' => 'Incorrect quotes provided',
                'text_ar' => 'Incorrect quotes provided',
            ],
            [
                'text' => 'Undecided customer',
                'text_ar' => 'Undecided customer',
            ],
            [
                'text' => 'No more risk to insure',
                'text_ar' => 'No more risk to insure',
            ],
            [
                'text' => 'Delays with insurer',
                'text_ar' => 'Delays with insurer',
            ],
            [
                'text' => 'Not looking for insurance',
                'text_ar' => 'Not looking for insurance',
            ],
            [
                'text' => 'No insurance plans applicable',
                'text_ar' => 'No insurance plans applicable',
            ],
            [
                'text' => 'Fake inquiry',
                'text_ar' => 'Fake inquiry',
            ],
            [
                'text' => 'Outside of budget',
                'text_ar' => 'Outside of budget',
            ],
            [
                'text' => 'Shopping around',
                'text_ar' => 'Shopping around',
            ],
            [
                'text' => 'Abu Dhabi visa holder',
                'text_ar' => 'Abu Dhabi visa holder',
            ],
            [
                'text' => 'Insured through a group',
                'text_ar' => 'Insured through a group',
            ],
            [
                'text' => 'Insurer not on our panel',
                'text_ar' => 'Insurer not on our panel',
            ],
            [
                'text' => 'Unhappy with insurers service',
                'text_ar' => 'Unhappy with insurers service',
            ],
            [
                'text' => 'No valid UAE visa',
                'text_ar' => 'No valid UAE visa',
            ],
            [
                'text' => 'Duplicate inquiry',
                'text_ar' => 'Duplicate inquiry',
            ],
            [
                'text' => 'Invalid contact information',
                'text_ar' => 'Invalid contact information',
            ],
            [
                'text' => 'Mortgage enquiry - Banks dont allow prospects to purchase outside of the bank',
                'text_ar' => 'Mortgage enquiry - Banks dont allow prospects to purchase outside of the bank',
            ],
            [
                'text' => 'Uninsurable due to medical reasons',
                'text_ar' => 'Uninsurable due to medical reasons',
            ],
            [
                'text' => 'Uninsurable due to age',
                'text_ar' => 'Uninsurable due to age',
            ],
        ]);
    }
}
