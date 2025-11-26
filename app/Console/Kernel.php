<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérifier les prix toutes les heures
        $schedule->command('prices:check --limit=50')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/price-check.log'));

        // Vérifier les prix toutes les 30 minutes pour les produits prioritaires
        $schedule->command('prices:check --limit=20')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Vérifier les alerts toutes les heures (sans envoyer de notifications)
        $schedule->command('alerts:check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
