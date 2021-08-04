<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LibreNMS\Util\Debug;
use LibreNMS\Util\Version;
use App\Console\Commands\ScheduleDevicePollerCommand;
use LibreNMS\Config;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->schedulePolling($schedule);

        $schedule->command('queue:prune-batches')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');

        if ($this->app->environment() !== 'production') {
            require base_path('routes/dev-console.php');
        }
    }

    public function getArtisan()
    {
        if (is_null($this->artisan)) {
            parent::getArtisan();
            /** @phpstan-ignore-next-line */
            $this->artisan->setName(\LibreNMS\Config::get('project_name', 'LibreNMS'));
            /** @phpstan-ignore-next-line */
            $this->artisan->setVersion(Version::get()->local());
        }

        return $this->artisan;
    }

    public function handle($input, $output = null)
    {
        // intercept input and check for debug
        if ($input->hasParameterOption(['-d', '--debug', '-vv', '-vvv'], true)) {
            if ($input->hasParameterOption(['-vvv'], true)) {
                Debug::setVerbose();
            }
            $this->app->booted('\LibreNMS\Util\Debug::set');
        }

        return parent::handle($input, $output);
    }

    /**
     * Handle the scheduling of discover + polling
     */
    private function schedulePolling(Schedule $schedule): void {

        // Nothing to do if Laravel based polling isn't enabled
        if (!Config::get('polling.laravel')) {
           // return;
        }

        $poller_interval    = Config::get('schedule.polling', 5);
        $discovery_interval = Config::get('schedule.discovery', 1);

        // Polling
        $schedule
        ->command(ScheduleDevicePollerCommand::class)
        ->cron("*/{$poller_interval} * * * *");

        // Discovery
        // TODO :)
    }
}
