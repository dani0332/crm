<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\QuoteTypes;
use App\Services\AllocationService;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Services\EmailServices\FailedILAEmailService;
use App\Services\Logger\LoggerService;
use App\Enums\ApplicationStorageEnums;

class SendFailedIlaEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected QuoteTypes $quoteType;
    protected $signature = 'send-failed-ila-leads {--quoteType=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send failed ILA emails to managers';

    public function handle()
    {
      try {

        $sendFailedIlaEmailsSwitch = getAppStorageValueByKey(ApplicationStorageEnums::SEND_FAILED_ILA_EMAILS_SWITCH, useCache: true);
        if (!$sendFailedIlaEmailsSwitch) {
            LoggerService::warning(self::class.' - sendFailedIlaEmails - Send failed ILA emails switch is not enabled');
            return Command::FAILURE;
        }
        $quoteType = $this->option('quoteType');
        $quoteType = QuoteTypes::tryFrom($quoteType);
        
        if (!$quoteType) {
            LoggerService::warning(self::class.' - sendFailedIlaEmails - Quote type is required');
            return Command::FAILURE;
        }
        LoggerService::info(self::class.' - sendFailedIlaEmails - Starting to send failed ILA emails to managers for quote type: '.$quoteType->value);
        switch ($quoteType) {
         
          case QuoteTypes::CAR:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::CAR);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::CAR->value);
            break;
          case QuoteTypes::BIKE:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::BIKE);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::BIKE->value);
            break;
          case QuoteTypes::HEALTH:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::HEALTH);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::HEALTH->value);
            break;
          case QuoteTypes::LIFE:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::LIFE);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::LIFE->value);
            break;
          case QuoteTypes::TRAVEL:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::TRAVEL);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::TRAVEL->value);
            break;
          case QuoteTypes::HOME:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::HOME);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::HOME->value);
            break;
          case QuoteTypes::PET:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::PET);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::PET->value);
            break;
          case QuoteTypes::CYCLE:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::CYCLE);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::CYCLE->value);
            break;
          case QuoteTypes::SAVINGS:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::SAVINGS);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::SAVINGS->value);
            break;
          case QuoteTypes::GROUP_MEDICAL:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::GROUP_MEDICAL);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::GROUP_MEDICAL->value);
            break;
          case QuoteTypes::CORPLINE:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::CORPLINE);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::CORPLINE->value);
            break;
          case QuoteTypes::YACHT:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::YACHT);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::YACHT->value);
            break;
          case QuoteTypes::JETSKI:
            app(FailedILAEmailService::class)->sendFailedIlaEmails(QuoteTypes::JETSKI);
            LoggerService::info(self::class.' - sendFailedIlaEmails - Sent failed ILA emails to managers for quote type: '.QuoteTypes::JETSKI->value);
            break;
          default:
            LoggerService::warning(self::class.' - sendFailedIlaEmails - Invalid quote type: '.$quoteType->value);
            break;
       
        }
      } catch (\Exception $e) {
        LoggerService::error(self::class.' - sendFailedIlaEmails - Error: '.$e->getMessage());
        return Command::FAILURE;
      }
      
      LoggerService::info(self::class.' - sendFailedIlaEmails - Completed sending failed ILA emails to managers');
      return Command::SUCCESS;
    }
}
