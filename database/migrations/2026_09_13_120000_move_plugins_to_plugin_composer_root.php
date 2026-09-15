<?php

use App\Plugins\PluginRoot;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Move plugins recorded in composer.plugins.json into the plugin composer root.
     *
     * Older LibreNMS versions installed plugin packages into the LibreNMS composer.json
     * itself and re-applied them from composer.plugins.json on every update.
     *
     * The moved plugins are installed straight away. That needs the network and can fail,
     * and a plugin must never break a LibreNMS upgrade, so a failure is only reported.
     */
    public function up(): void
    {
        $file = PluginRoot::legacyManifest();

        if (! is_file($file)) {
            return;
        }

        $manifest = PluginRoot::readManifest($file);

        if ($manifest === null) {
            // deleting it here would take the only record of the plugin list with it
            echo PluginRoot::LEGACY_MANIFEST . ' could not be read, move the plugins it lists by hand.' . PHP_EOL;

            return;
        }

        $plugins = (array) ($manifest['require'] ?? []);

        try {
            PluginRoot::writeManifest($plugins);
        } catch (Throwable $e) {
            // never fail the migration, that would block the whole LibreNMS upgrade
            echo 'Could not move plugins to ' . PluginRoot::path() . ': ' . $e->getMessage() . PHP_EOL;

            return;
        }

        unlink($file);

        echo 'moved plugins to ' . PluginRoot::path() . ': ' . implode(', ', array_keys($plugins)) . PHP_EOL;

        try {
            $installed = PluginRoot::composer(['update', '--no-dev']) === 0;
        } catch (Throwable $e) {
            $installed = false;
        }

        if (! $installed) {
            echo 'Could not install the moved plugins, run lnms plugin:sync.' . PHP_EOL;
        }
    }
};
