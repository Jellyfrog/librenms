<?php

/*
 * CheckRrddirPermissions.php
 *
 * -Description-
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2022 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\Validations\Rrd;

use App\Facades\LibrenmsConfig;
use LibreNMS\Interfaces\Validation;
use LibreNMS\ValidationResult;

class CheckRrdDirPermissions implements Validation
{
    /**
     * @inheritDoc
     */
    public function validate(): ValidationResult
    {
        $rrd_dir = LibrenmsConfig::get('rrd_dir');

        $dir_stat = stat($rrd_dir);
        if ($dir_stat[4] == 0 || $dir_stat[5] == 0) {
            return ValidationResult::warn(trans('validation.validations.rrd.CheckRrdDirPermissions.fail_root'),
                sprintf('chown %s:%s %s', LibrenmsConfig::get('user'), LibrenmsConfig::get('group'), $rrd_dir)
            );
        }

        // the web server and the poller need write access through the owner or the group, other needs no access
        if ((fileperms($rrd_dir) & 0770) !== 0770) {
            return ValidationResult::warn(trans('validation.validations.rrd.CheckRrdDirPermissions.fail_mode'), "chmod ug+rwx $rrd_dir");
        }

        return ValidationResult::ok(trans('validation.validations.rrd.CheckRrdDirPermissions.ok'));
    }

    /**
     * @inheritDoc
     */
    public function enabled(): bool
    {
        return ! LibrenmsConfig::get('rrdcached');
    }
}
