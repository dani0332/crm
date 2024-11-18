<?php

namespace App\Console\Commands;

use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Console\Command;

class InslyEmailFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'InslyEmailFix:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fix comma seperated email addresses because of insly bug';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \DB::transaction(function () {
            // Step 1: Chunk through leads with comma-separated emails in the `email` field
            CarQuote::where('email', 'LIKE', '%,%')
                ->chunkById(100, function ($carLeadsWithCommaEmails) {
                    foreach ($carLeadsWithCommaEmails as $lead) {
                        // Split the email addresses by comma and trim whitespace
                        $emails = array_map('trim', explode(',', $lead->email));
                        $primaryEmail = $emails[0];
                        $additionalEmails = array_slice($emails, 1);

                        // Check if the customer with the primary email already exists
                        $existingCustomer = Customer::where('email', $primaryEmail)->first();
                        if ($existingCustomer) {
                            $lead->customer_id = $existingCustomer->id;
                        } else {
                            // Create a new customer if the primary email does not exist
                            $newCustomer = new Customer;
                            $newCustomer->email = $primaryEmail;
                            $newCustomer->first_name = $lead->first_name;
                            $newCustomer->last_name = $lead->last_name;
                            $newCustomer->mobile_no = $lead->mobile_no;
                            $newCustomer->save();
                            $lead->customer_id = $newCustomer->id;
                            info("app:insly-email-fix:: Created new customer email {$primaryEmail} for lead ID {$lead->id}");
                        }

                        // Add additional emails to the CustomerAdditionalContact table
                        if ($additionalEmails) {
                            foreach ($additionalEmails as $email) {
                                CustomerAdditionalContact::updateOrCreate([
                                    'customer_id' => $lead->customer_id,
                                    'key' => 'email',
                                    'value' => $email,
                                ]);
                                info("app:insly-email-fix:: Added additional email {$email} for lead ID {$lead->id}");
                            }
                        }

                        // Update lead with the primary email and save
                        $lead->email = $primaryEmail;
                        $lead->save();
                        info("app:insly-email-fix:: Processed lead ID {$lead->id} with primary email {$primaryEmail}");
                    }
                });

            info('app:insly-email-fix:: All leads with comma-separated emails have been processed.');
        });
    }
}
