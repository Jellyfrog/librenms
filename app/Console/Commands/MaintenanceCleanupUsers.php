<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupUsers extends LnmsCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'maintenance:cleanup-users';

    /** @var string[] Auth mechanisms that support purging users, mapped to their config key */
    private const PURGE_SETTINGS = [
        'radius' => 'radius.users_purge',
        'active_directory' => 'active_directory.users_purge',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->addArgument('days', InputArgument::OPTIONAL);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->argument('days');

        if ($days !== null && ! is_numeric($days)) {
            $this->error(__('commands.maintenance:cleanup-users.bad_days_input'));

            return 1;
        }

        // only users of external auth mechanisms may be purged, this also guards the days argument
        $mechanism = LibrenmsConfig::get('auth_mechanism');
        if (! array_key_exists($mechanism, self::PURGE_SETTINGS)) {
            $this->warn(trans('commands.maintenance:cleanup-users.unsupported_auth', ['mechanism' => (string) $mechanism]));

            return 0;
        }

        if ($days === null) {
            $days = LibrenmsConfig::get(self::PURGE_SETTINGS[$mechanism]);

            if (! is_numeric($days)) {
                $this->warn(trans('commands.maintenance:cleanup-users.bad_days_setting', ['setting' => self::PURGE_SETTINGS[$mechanism]]));

                return 0;
            }
        }

        if ($days <= 0) {
            $this->warn(__('commands.maintenance:cleanup-users.disabled'));

            return 0;
        }

        $cutoff = Carbon::now()->subDays($days);

        $deleted_total = 0;
        User::thisAuth()
            ->whereDoesntHave('apiTokens') // don't purge users with api tokens
            ->whereDoesntHave('authLogs', fn (Builder $query) => $query->where('datetime', '>=', $cutoff))
            ->chunkById(100, function ($users) use (&$deleted_total): void {
                foreach ($users as $user) {
                    // delete one by one so the UserObserver cleans up related rows
                    $deleted_total += (int) $user->delete();
                }
            }, 'user_id');

        $this->line(trans('commands.maintenance:cleanup-users.delete', ['days' => $days, 'count' => $deleted_total]));

        return 0;
    }
}
