<?php

namespace Vuma\SaaS\Console\Commands;

use Illuminate\Console\Command;
use Vuma\SaaS\Services\Billing\DunningService;

class RunDunning extends Command
{
    protected $signature   = 'saas:dunning';
    protected $description = 'Run the billing dunning cycle (notify, retry payments, suspend overdue tenants)';

    public function handle(DunningService $dunning): int
    {
        $this->info('Starting dunning cycle...');
        $dunning->runCycle();
        $this->info('Dunning cycle complete.');
        return self::SUCCESS;
    }
}
