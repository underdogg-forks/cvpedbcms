<?php

namespace Core\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Core\Console\Commands\BindingsCommand::class,
        \Core\Console\Commands\ControllerCommand::class,
        \Core\Console\Commands\EntityCommand::class,
        \Core\Console\Commands\PresenterCommand::class,
        \Core\Console\Commands\RepositoryCommand::class,
        \Core\Console\Commands\TransformerCommand::class,
        \Core\Console\Commands\ValidatorCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }
}
