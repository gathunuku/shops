<?php

namespace Vuma\SaaS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Vuma\SaaS\Console\Commands\RunDunning;
use Vuma\SaaS\Console\Commands\ComputeKpis;

class SchedulerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([RunDunning::class, ComputeKpis::class]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('saas:dunning')->hourly()->withoutOverlapping()->runInBackground();
            $schedule->command('saas:compute-kpis')->dailyAt('01:00')->withoutOverlapping();
        });
    }
}
