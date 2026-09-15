<?php

/**
 * PluginRoot.php
 *
 * Plugin packages are installed into their own composer root (storage/plugins) instead of
 * the LibreNMS composer.json, so a plugin can never block a LibreNMS upgrade.
 *
 * The trick is the generated "replace" block: every package LibreNMS already has is
 * declared as replaced at its exact installed version, so composer resolves plugins
 * against what LibreNMS ships rather than installing a second copy of it.
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
 * @copyright  2025 LibreNMS
 */

namespace App\Plugins;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use LibreNMS\ComposerHelper;
use Symfony\Component\Console\Input\ArgvInput;

class PluginRoot
{
    public const LEGACY_MANIFEST = 'composer.plugins.json';

    /** Values every generated manifest ends up with, so they can be changed later. */
    private const SKELETON = [
        'name' => 'librenms/plugin-root',
        'description' => 'Installed LibreNMS plugin packages. Do not edit by hand.',
        'type' => 'project',
        'config' => [
            'prepend-autoloader' => false, // core's autoloader must win every lookup
            'optimize-autoloader' => true,
            'preferred-install' => 'dist',
            'allow-plugins' => false,
            'platform-check' => false, // the solver checks the platform on install already
        ],
    ];

    /** The commands that manage the plugin root, and so must run without loading it. */
    private const MANAGE_COMMANDS = ['plugin:add', 'plugin:list', 'plugin:remove', 'plugin:sync'];

    /**
     * Is this a command that manages plugin packages? Those run without them, so a plugin that
     * fails to load cannot stop the commands that repair or remove it. Called from
     * bootstrap/app.php, before Laravel exists, so it only looks at the command line.
     *
     * @param  string[]|null  $argv
     */
    public static function skipsPlugins(?array $argv = null): bool
    {
        if ($argv === null && PHP_SAPI !== 'cli') {
            return false;
        }

        $command = (new ArgvInput($argv))->getFirstArgument();

        return in_array($command, self::MANAGE_COMMANDS, true);
    }

    public static function path(string $append = ''): string
    {
        return base_path('storage/plugins' . ($append === '' ? '' : "/$append"));
    }

    /** Where LibreNMS before the plugin root recorded installed plugins. */
    public static function legacyManifest(): string
    {
        return base_path(self::LEGACY_MANIFEST);
    }

    /** A composer manifest, or null if it is missing or unreadable. @return array<string, mixed>|null */
    public static function readManifest(string $file): ?array
    {
        $manifest = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($manifest) ? $manifest : null;
    }

    /** Packages the plugin root asks for, name => constraint. @return array<string, string> */
    public static function required(): array
    {
        return (array) (self::manifest()['require'] ?? []);
    }

    /** Packages installed in the plugin root, name => version. @return array<string, string> */
    public static function installed(): array
    {
        return array_filter(array_map(
            fn (array $info) => $info['pretty_version'] ?? null,
            self::composerVersions(self::path('vendor/composer/installed.php'))
        ));
    }

    /**
     * Laravel's package discovery for the plugin root, the same it runs over the core vendor.
     * Its cache is rebuilt by composer(), which runs as a user that can write bootstrap/cache.
     */
    public static function packageManifest(): PackageManifest
    {
        $manifest = new PackageManifest(new Filesystem, self::path(), app()->bootstrapPath('cache/plugin-packages.php'));
        // Laravel otherwise takes this from COMPOSER_VENDOR_DIR, wherever that points; the
        // plugin vendor is spelled out everywhere else and discovery has to agree with it
        $manifest->vendorPath = self::path('vendor');

        return $manifest;
    }

    /**
     * Were the plugins resolved against a LibreNMS that has since moved on? Nothing repairs
     * itself on boot, so this is how somebody who upgraded around plugin:sync finds out.
     */
    public static function isStale(?array $manifest = null): bool
    {
        $manifest ??= self::manifest();

        return ($manifest['require'] ?? []) !== []
            && ($manifest['replace'] ?? []) !== self::replaceBlock();
    }

    /**
     * Rewrite the manifest: the replace block from what LibreNMS has installed, plus any
     * added requirements. Anything else in the file, such as repositories, is kept.
     *
     * Requirements LibreNMS already provides are dropped: the replace block satisfies them
     * whatever they say, so they only clutter the plugin list.
     *
     * @param  array<string, string>  $add
     */
    public static function writeManifest(array $add = []): void
    {
        $manifest = self::manifest();
        $replace = self::replaceBlock();
        $require = array_diff_key(array_merge((array) ($manifest['require'] ?? []), $add), $replace);
        ksort($require);

        @mkdir(self::path(), 0775, true);

        file_put_contents(self::path('composer.json'), json_encode(
            array_merge($manifest, self::SKELETON, [
                'require' => $require === [] ? new \stdClass : $require,
                'replace' => $replace,
            ]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) . PHP_EOL);
    }

    /**
     * Everything LibreNMS already provides, as name => version.
     *
     * composer's installed.php is the resolved form of installed.json: laravel/framework's
     * "self.version" replaces are already expanded, and virtual packages other libraries
     * only provide are listed too, so none of that has to be worked out here.
     *
     * @return array<string, string>
     */
    public static function replaceBlock(?string $installedPhp = null): array
    {
        $versions = self::composerVersions($installedPhp ?? base_path('vendor/composer/installed.php'));
        $replace = [];

        foreach ($versions as $name => $info) {
            $replace[$name] = $info['pretty_version'] ?? $info['replaced'][0] ?? $info['provided'][0] ?? null;
        }

        unset($replace['librenms/librenms']); // the root package replacing itself helps nobody
        ksort($replace);

        return array_filter($replace);
    }

    /**
     * Run composer against the plugin root, through the wrapper the rest of LibreNMS uses.
     *
     * The manifest is regenerated first so the replace block matches the core vendor as it
     * is now, and Laravel's caches are refreshed afterwards whatever composer left behind.
     */
    public static function composer(array $arguments): int
    {
        $previous = @file_get_contents(self::path('composer.json'));
        self::writeManifest();

        $code = ComposerHelper::execComposerCommand(
            array_merge(['--no-interaction', '--working-dir=' . self::path()], $arguments)
        );

        if ($code !== 0 && $previous !== false) {
            // composer puts back its own changes, but not the replace block written above; a
            // manifest resolved against a newer core than the lock is one that lies about it
            file_put_contents(self::path('composer.json'), $previous);
        }

        try {
            self::packageManifest()->build();
            Artisan::call('optimize:clear', ['--except' => 'cache,compiled']);
        } catch (\Throwable $e) {
            // composer has already done the install; a cache we cannot write is not a reason
            // to report that it failed
            Log::error('Refreshing the caches after a plugin change failed: ' . $e->getMessage());
        }

        return $code;
    }

    /** @return array<string, mixed> */
    private static function manifest(): array
    {
        return self::readManifest(self::path('composer.json')) ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    private static function composerVersions(string $installedPhp): array
    {
        $data = is_file($installedPhp) ? (array) require $installedPhp : [];

        return $data['versions'] ?? [];
    }
}
