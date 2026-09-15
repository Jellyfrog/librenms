<?php

/**
 * Plugins.php
 *
 * Most LibreNMS upgrades happen unattended from daily.sh and nobody reads composer's
 * output, so a plugin left behind by an upgrade has to surface where people look.
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

namespace LibreNMS\Validations;

use App\Plugins\PluginRoot;
use LibreNMS\ValidationResult;
use LibreNMS\Validator;

class Plugins extends BaseValidation
{
    public function validate(Validator $validator): void
    {
        if (is_file(PluginRoot::legacyManifest())) {
            // the upgrade migration moves these across; still being here means it could not
            $validator->warn(
                PluginRoot::LEGACY_MANIFEST . ' still exists, so plugins from an older LibreNMS were not moved to '
                    . PluginRoot::path() . '. They can block LibreNMS upgrades.',
                'Install the packages it lists with ./lnms plugin:add, then delete it'
            );
        }

        $required = PluginRoot::required();

        if ($required === []) {
            return;
        }

        // the manifest lists what was asked for; only the vendor says what is really there
        $missing = array_diff_key($required, PluginRoot::installed());

        if ($missing !== []) {
            $validator->result(ValidationResult::fail(
                'Plugin packages are configured but not installed.',
                './lnms plugin:sync'
            )->setList('Plugins', array_keys($missing)));

            return;
        }

        if (PluginRoot::isStale()) {
            $validator->warn(
                count($required) . ' plugin package(s) were resolved against a different LibreNMS install. '
                    . 'They may load against dependency versions they were never tested with.',
                'Re-resolve them with ./lnms plugin:sync'
            );

            return;
        }

        $validator->ok(count($required) . ' plugin package(s) installed.');
    }
}
