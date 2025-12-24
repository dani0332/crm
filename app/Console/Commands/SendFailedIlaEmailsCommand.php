<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\QuoteTypes;
use App\Services\AllocationService;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Services\EmailServices\FailedILAEmailService;

class SendFailedIlaEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected QuoteTypes $quoteType;
    protected $signature = 'app:send-failed-ila-emails-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send failed ILA emails';

    public function handle()
    {
      $allowedLobs =[QuoteTypes::CAR, QuoteTypes::BIKE, QuoteTypes::HEALTH, QuoteTypes::LIFE, QuoteTypes::TRAVEL, QuoteTypes::HOME, QuoteTypes::PET, QuoteTypes::CYCLE, QuoteTypes::SAVINGS, QuoteTypes::GROUP_MEDICAL, QuoteTypes::CORPLINE, QuoteTypes::BUSINESS];

      foreach ($allowedLobs as $quoteType) {
        switch ($quoteType) {
          case QuoteTypes::CAR:
            app(FailedILAEmailService::class)->sendFailedCarIlaEmails();
            break;
       
        }
       
      }
    }
}
