<?php
/**
 * DeviceSummaryController.php
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
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Http\Controllers\Widgets;

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Port;
use App\Models\Service;
use Illuminate\Http\Request;
use LibreNMS\Config;

abstract class DeviceSummaryController extends WidgetController
{
    protected $title = 'Device Summary';

    public function __construct()
    {
        // init defaults we need to check config, so do it in construct
        $this->defaults = [
            'show_services' => (int)Config::get('show_services', 1),
            'summary_errors' => (int)Config::get('summary_errors', 0),
            'device_group' => null,
        ];
    }

    public function getSettingsView(Request $request)
    {
        $settings = $this->getSettings();
        $settings['device_group'] = DeviceGroup::find($settings['device_group']);

        return view('widgets.settings.device-summary', $settings);
    }

    protected function getData(Request $request)
    {
        $data = $this->getSettings();
        $user = $request->user();

        $query = Device::hasAccess($user);
        if ($data['device_group']) {
            $query->whereHas('groups', function ($query) use ($data) {
                $query->where('id', $data['device_group']);
            });
        }

        $data['devices'] = [
            'count' => (clone $query)->count(),
            'up' => (clone $query)->isUp()->count(),
            'down' => (clone $query)->isDown()->count(),
            'ignored' => (clone $query)->isIgnored()->count(),
            'disabled' => (clone $query)->isDisabled()->count(),
        ];

        $query = Port::hasAccess($user);
        if ($data['device_group']) {
            $query_devicegroup = $query->whereHas('device.groups', function ($query) {
                $query->where('id', 1);
            });
        }

        $data['ports'] = [
            'count' => (clone $query)->isNotDeleted()->count(),
            'up' => (clone $query)->isNotDeleted()->isUp()->count(),
            'down' => (clone $query)->isNotDeleted()->isDown()->count(),
            'ignored' => (clone $query)->isNotDeleted()->isIgnored()->count(),
            'shutdown' => (clone $query)->isNotDeleted()->isShutdown()->count(),
            'errored' => $data['summary_errors'] ? (clone $query)->isNotDeleted()->hasErrors()->count() : -1,
        ];

        $query = Service::hasAccess($user);
        if ($data['device_group']) {
            $query_devicegroup = $query->whereHas('device.groups', function ($query) use ($data) {
                $query->where('id', $data['device_group']);
            });
        }

        if ($data['show_services']) {
            $data['services'] = [
                'count' => (clone $query)->count(),
                'up' => (clone $query)->isUp()->count(),
                'down' => (clone $query)->isDown()->count(),
                'ignored' => (clone $query)->isIgnored()->count(),
                'disabled' => (clone $query)->isDisabled()->count(),
            ];
        }

        return $data;
    }
}
