<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Support\Facades\DB;

class InslyEmailFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insly-email-fix';

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
            
            // Step 1: Find leads with comma-separated emails in the other_email_address field
            $carLeadsWithCommaEmails = CarQuote::where('email', 'LIKE', '%,%')->get();            
            if($carLeadsWithCommaEmails->count() > 0){
                foreach ($carLeadsWithCommaEmails as $lead) {
                    // Split the email addresses by comma and trim whitespace
                    $emails = array_map('trim', explode(',', $lead->email));
    
                    $primaryEmail = $emails[0];
                    $additionalEmails = array_slice($emails, 1);
                    
                    $existingCustomer = Customer::where('email', $primaryEmail)->first();
                    if ($existingCustomer) {
                        $lead->customer_id = $existingCustomer->id;
                    } else {
                        // Otherwise, add as an additional email for the main customer
                        // Assuming the Customer model has a method to add additional emails
                        $newCustomer = new Customer();
                        $newCustomer->email = $primaryEmail;
                        $newCustomer->first_name = $lead->first_name;
                        $newCustomer->last_name =$lead->last_name;
                        $newCustomer->mobile_no =$lead->mobile_no;
                        $newCustomer->save();
                        $lead->customer_id = $newCustomer->id;
                        info("app:insly-email-fix:: Created new customer email {$primaryEmail} for lead ID {$lead->id}");
                    }                
                    if ($additionalEmails) {
                        foreach ($additionalEmails as $email) {
                            CustomerAdditionalContact::updateOrCreate([
                                'customer_id' => $lead->customer_id,
                                'key' => 'email',
                                'value' => $email,
                            ]);
                            // Output status for each additional email
                            info("app:insly-email-fix:: Added additional email {$email} for lead ID {$lead->id}");
                        }
                    }
                    $lead->email = $primaryEmail;
                    $lead->save();
                    // Output status
                    info("app:insly-email-fix:: Processed lead ID {$lead->id} with primary email {$primaryEmail}");
                }            
                info('app:insly-email-fix:: All leads with comma-separated emails have been processed.');
            }
            
        });
    }   
}
