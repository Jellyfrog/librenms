<?php

/**
 * PluginPackageServiceProvider.php
 *
 * Registers the service providers of packages installed in the plugin composer root.
 * Laravel's own discovery only scans the core vendor, so they would never be booted.
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

namespace App\Providers;

use App\Plugins\PluginRoot;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PluginPackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // This runs on every boot, and LibreNMS boots once per device per poll cycle. Most
        // installs have no plugins, so cost them one stat.
        // A plugin that throws in boot() takes every entry point down with it, so the
        // commands that repair or remove plugins run without them.
        if (PluginRoot::skipsPlugins() || ! is_file(PluginRoot::path('vendor/autoload.php'))) {
            return;
        }

        try {
            // Discovery is all or nothing: building the manifest writes to bootstrap/cache,
            // which throws if that is not writable by whoever is booting.
            $manifest = PluginRoot::packageManifest();
            $providers = $manifest->providers();

            if ($aliases = $manifest->aliases()) {
                AliasLoader::getInstance($aliases)->register();
            }
        } catch (\Throwable $e) {
            Log::error('Failed to load plugin packages: ' . $e->getMessage());

            return;
        }

        foreach ($providers as $provider) {
            try {
                $this->app->register($provider);
            } catch (\Throwable $e) {
                // a plugin an upgrade broke takes itself out, not every plugin after it.
                // Only registration: Laravel boots these providers itself, in one loop we
                // cannot reach, so a plugin that fails in boot() still takes LibreNMS down
                Log::error("Failed to register plugin provider $provider: " . $e->getMessage());
            }
        }
    }
}
