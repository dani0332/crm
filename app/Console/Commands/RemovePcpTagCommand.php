<?php

namespace App\Console\Commands;

use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;
use Illuminate\Console\Command;

class RemovePcpTagCommand extends Command
{
    use PrivateClient;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove-pcp-tag {customer_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove PCP tag from customers';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LoggerService::info('cmd:RemovePcpTagCommand - Remove PCP tag from customers Started');
        $customerId = $this->argument('customer_id');

        $this->removePcpTag($customerId);

        LoggerService::info('cmd:RemovePcpTagCommand - Remove PCP tag from customers Ended');
    }
}
