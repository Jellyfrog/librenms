<?php

/**
 * User.php
 *
 * Check that user is set properly and we are running as the correct user.  Check that user is the owner of install_dir.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\Validations;

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use LibreNMS\Util\EnvHelper;
use LibreNMS\Util\Git;
use LibreNMS\ValidationResult;
use LibreNMS\Validator;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class User extends BaseValidation
{
    /**
     * Validate this module.
     * To return ValidationResults, call ok, warn, fail, or result methods on the $validator
     *
     * @param  Validator  $validator
     */
    public function validate(Validator $validator): void
    {
        // Check we are running this as the root user
        $username = $validator->getUsername();
        $lnms_username = \config('librenms.user');
        $lnms_groupname = \config('librenms.group');

        if (App::runningInConsole()) {
            if (! ($username === 'root' || $username === $lnms_username)) {
                $validator->fail("You need to run this script as '$lnms_username' or root");
            }
        } elseif ($username !== $lnms_username && function_exists('posix_getgrnam')) {
            $lnms_group = posix_getgrnam($lnms_groupname);

            if ($lnms_group === false) {
                $validator->fail(
                    "The group '$lnms_groupname' does not exist",
                    "groupadd $lnms_groupname"
                );
            } elseif (posix_getegid() !== $lnms_group['gid']) {
                // files the web server creates get its effective group, a supplementary group (usermod -a -G) is not enough
                // the poller can only write them when the group is librenms
                $validator->fail(
                    "Your web server or php-fpm does not run with the group '$lnms_groupname'. Files it creates cannot be changed by the poller.",
                    "Set 'group = $lnms_groupname' in your php-fpm pool configuration and restart php-fpm"
                );
            }
        }

        // skip if docker image
        if (EnvHelper::librenmsDocker()) {
            return;
        }

        $dir = LibrenmsConfig::get('install_dir');
        $log_dir = rtrim((string) LibrenmsConfig::get('log_dir', "$dir/logs"), '/');
        $rrd_dir = rtrim((string) LibrenmsConfig::get('rrd_dir', "$dir/rrd"), '/');
        $data_dirs = [$rrd_dir, $log_dir, "$dir/storage", "$dir/bootstrap/cache"];

        // packages set their own permissions, some run the web server and the poller as the same user
        if (! App::runningInConsole() && ! LibrenmsConfig::get('installed_from_package')) {
            $this->checkWebWritableCode($validator, $lnms_username, $lnms_groupname, $dir, $data_dirs);
        }

        // if no git, then we probably have different permissions by design
        if (! Git::make()->repoPresent()) {
            return;
        }

        // Let's test the user configured if we have it
        if ($lnms_username) {
            $data_dirs_arg = implode(' ', array_map('escapeshellarg', $data_dirs));

            // generic fix, chgrp -H also reaches data directories that are symlinks or outside the install directory
            $fix = [
                "sudo chown -R $lnms_username:$lnms_groupname $dir",
                "sudo chgrp -RH $lnms_groupname $data_dirs_arg",
                "sudo setfacl -R -d -m g::rwx,o::--- $data_dirs_arg",
                "sudo chmod -R a-x,a+X $data_dirs_arg",
                "sudo setfacl -R -m u::rwX,g::rwX,o::--- $data_dirs_arg",
            ];

            if (! LibrenmsConfig::get('installed_from_package')) {
                // php-fpm can run as a separate user in the librenms group, so it creates files in the data directories
                // -H: data directories can be symlinks to another disk
                $prune = implode(' ', array_map('escapeshellarg', self::pruneArgs($data_dirs)));
                $find_output = shell_exec("find $dir $prune -o \\( \\! -user $lnms_username -o \\! -group $lnms_groupname \\) -print 2> /dev/null")
                    . shell_exec("find -H $data_dirs_arg \\! -group $lnms_groupname 2> /dev/null");
                $find_result = rtrim($find_output);
                if (! empty($find_result)) {
                    // Ignore files created by the webserver
                    $ignore_files = [
                        "$log_dir/error_log",
                        "$log_dir/access_log",
                        "$dir/bootstrap/cache/",
                        "$dir/storage/framework/cache/",
                        "$dir/storage/framework/sessions/",
                        "$dir/storage/framework/views/",
                        "$dir/storage/debugbar/",
                        "$dir/.pki/", // ignore files/folders created by setting the librenms home directory to the install directory
                    ];

                    $smokeping_dir = LibrenmsConfig::get('smokeping.dir');
                    if ($smokeping_dir && str_starts_with($smokeping_dir, '/')) {
                        $ignore_files[] = $smokeping_dir;
                    }

                    $files = array_filter(explode(PHP_EOL, $find_result), function ($file) use ($ignore_files) {
                        if (Str::startsWith($file, $ignore_files)) {
                            return false;
                        }

                        return true;
                    });

                    if (! empty($files)) {
                        $result = ValidationResult::fail(
                            "We have found some files that are owned by a different user or group than '$lnms_username:$lnms_groupname', this " .
                            'will stop you updating automatically and / or rrd files being updated causing graphs to fail.'
                        )
                            ->setFix($fix)
                            ->setList('Files', $files);

                        $validator->result($result);

                        return;
                    }
                }
            }

            // the group only needs access when the web server runs as a different user than the poller
            if (! App::runningInConsole() && $validator->getUsername() !== $lnms_username) {
                $this->checkDataDirsGroupWritable($validator, $lnms_groupname, $dir, $data_dirs);
            }
        } else {
            $validator->warn("You don't have LIBRENMS_USER set, this most likely needs to be set to 'librenms'");
        }
    }

    /**
     * The web server and the poller run as different users in the same group, they must be able to write each other's files.
     * Some files only need to be readable by the group, their directories must be writable:
     * - rrd files: only the poller (or rrdcached) writes them, the web server reads them and renames or deletes them
     * - Laravel replaces its bootstrap cache and compiled views with a rename and chmods them to 0777 - umask
     *
     * @param  string[]  $dirs
     */
    private function checkDataDirsGroupWritable(Validator $validator, ?string $lnms_groupname, string $dir, array $dirs): void
    {
        $dirs = array_values(array_filter($dirs, 'is_dir'));
        if (empty($dirs)) {
            return;
        }

        // -path matches the text of the start points, so the patterns use the same paths
        $replaced = ['(', '-path', self::glob("$dir/bootstrap/cache") . '/*', '-o', '-path', self::glob("$dir/storage/framework/views") . '/*', ')'];

        $read_only = ['(', '-name', '*.rrd', '-o', ...$replaced, ')'];

        // -H: data directories can be symlinks to another disk
        // skip the placeholder files committed to git, they are never written at runtime
        // only check *.php in the replaced directories, the temporary files of a replace in progress are 0600
        $process = new Process(['find', '-H', ...$dirs, '(',
            '-type', 'd', '!', '-perm', '-g+w',
            '-o', '!', '-type', 'd', '!', '-name', '.gitignore', '!', ...$read_only, '!', '-perm', '-g+w',
            '-o', '!', '-type', 'd', '(', '-name', '*.rrd', '-o', '-name', '*.php', ...$replaced, ')', '!', '-perm', '-g+r',
            ')', '-print', '-quit']);
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return;
        }

        $first = trim($process->getOutput());
        if ($first === '') {
            return;
        }

        $validator->result(
            ValidationResult::warn("Some files in the data directories do not have the permissions the group '$lnms_groupname' needs. The web server and the poller cannot use each other's files.")
                ->setFix([
                    'sudo chmod -R a-x,a+X ' . implode(' ', array_map('escapeshellarg', $dirs)),
                    'sudo setfacl -R -m u::rwX,g::rwX,o::--- ' . implode(' ', array_map('escapeshellarg', $dirs)),
                    'sudo setfacl -R -d -m g::rwx,o::--- ' . implode(' ', array_map('escapeshellarg', $dirs)),
                ])
                ->setList('First file found', [$first])
        );
    }

    /**
     * The web server must not be able to change the code it runs, only the data directories.
     *
     * @param  string[]  $data_dirs
     */
    private function checkWebWritableCode(Validator $validator, ?string $lnms_username, ?string $lnms_groupname, string $dir, array $data_dirs): void
    {
        // html/plugins: some plugins (Weathermap) write their output next to their code
        $skip = ['./rrd', './logs', './storage', './bootstrap/cache', './html/plugins'];
        // rrd_dir and log_dir can be set to other directories inside the install directory
        foreach ($data_dirs as $path) {
            if (str_starts_with($path, "$dir/")) {
                $skip[] = '.' . substr($path, strlen($dir));
            }
        }
        $prune = self::pruneArgs(array_unique($skip));

        // LC_ALL=C: the error output is parsed below
        $process = new Process(['find', '.', ...$prune, '-o', '-writable', '-print'], $dir, ['LC_ALL' => 'C']);
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $validator->warn('Could not check if the web server can write to the LibreNMS code: find timed out');

            return;
        }

        $writable = array_slice(array_filter(explode(PHP_EOL, $process->getOutput())), 0, 20);

        // find exits 1 on directories the web server cannot read (~/.ssh), the result for the rest is still valid
        // any other error (unreadable install directory, find without -writable) means nothing was checked
        // older GNU find quotes paths as `./dir'
        $errors = array_filter(explode(PHP_EOL, $process->getErrorOutput()), fn ($line) => $line !== '' && ! preg_match('#^find: [\'`]?\./.+: Permission denied$#', $line));
        if (! $process->isSuccessful() && ($process->getExitCode() !== 1 || ! empty($errors))) {
            $validator->warn('Could not check if the web server can write to the LibreNMS code: ' . (reset($errors) ?: 'find exit code ' . $process->getExitCode()));

            return;
        }

        if (empty($writable)) {
            $validator->ok('Web server cannot write to the LibreNMS code');

            return;
        }

        $prune_args = implode(' ', array_map('escapeshellarg', $prune));

        $validator->result(
            ValidationResult::warn(
                "Your web server or php-fpm can write to the LibreNMS code. Run php-fpm as a different user than '$lnms_username', in the group '$lnms_groupname'.",
            )->setFix([
                // not $(which nologin): it runs before sudo, and the PATH of a regular user on Debian has no sbin
                'sudo useradd librenms-web -g ' . $lnms_groupname . ' -d /nonexistent -M -r -s /usr/sbin/nologin',
                "Set 'user = librenms-web' and 'group = $lnms_groupname' in your php-fpm pool configuration and restart php-fpm",
                "cd $dir && sudo chmod 751 . && sudo find . $prune_args -o \\! -type l -exec chmod go-w {} +",
                "sudo chmod 640 $dir/.env",
            ])->setList('Writable (first 20)', $writable)
        );
    }

    /**
     * find arguments that skip the given paths: ( -path a -o -path b ) -prune
     *
     * @param  string[]  $paths
     * @return string[]
     */
    private static function pruneArgs(array $paths): array
    {
        $args = array_merge(...array_map(fn ($path) => ['-o', '-path', self::glob($path)], array_values($paths)));
        $args[0] = '(';

        return [...$args, ')', '-prune'];
    }

    /**
     * Escape a path for find -path, which matches with glob patterns
     */
    private static function glob(string $path): string
    {
        return addcslashes($path, '\\*?[');
    }
}
