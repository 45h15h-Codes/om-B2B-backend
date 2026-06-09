<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('shopify:sync-recovery')->everyFifteenMinutes();
        $schedule->command('sys:monitor-health')->everyFifteenMinutes();
        $schedule->command('sys:expire-reservations')->everyMinute();
        $schedule->command('sys:verify-cross-store')->daily();

        // Daily retention pruning for notifications
        $schedule->call(function () {
            // Soft delete active notifications older than 30 days
            \App\Models\Notification::where('created_at', '<', now()->subDays(30))->delete();
            // Permanently prune soft-deleted notifications older than 30 days
            \App\Models\Notification::onlyTrashed()->where('deleted_at', '<', now()->subDays(30))->forceDelete();
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
