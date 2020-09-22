<?php
/**
 * PollerCluster.php
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
 * @link       http://librenms.org
 * @copyright  2020 Thomas Berberich
 * @author     Thomas Berberich <sourcehhdoctor@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PollerCluster
 *
 * @property int $id
 * @property string $node_id
 * @property string $poller_name
 * @property string $poller_version
 * @property string $poller_groups
 * @property string $last_report
 * @property int $master
 * @property int|null $poller_enabled
 * @property int|null $poller_frequency
 * @property int|null $poller_workers
 * @property int|null $poller_down_retry
 * @property int|null $discovery_enabled
 * @property int|null $discovery_frequency
 * @property int|null $discovery_workers
 * @property int|null $services_enabled
 * @property int|null $services_frequency
 * @property int|null $services_workers
 * @property int|null $billing_enabled
 * @property int|null $billing_frequency
 * @property int|null $billing_calculate_frequency
 * @property int|null $alerting_enabled
 * @property int|null $alerting_frequency
 * @property int|null $ping_enabled
 * @property int|null $ping_frequency
 * @property int|null $update_enabled
 * @property int|null $update_frequency
 * @property string|null $loglevel
 * @property int|null $watchdog_enabled
 * @property string|null $watchdog_log
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PollerClusterStat[] $stats
 * @property-read int|null $stats_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereAlertingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereAlertingFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereBillingCalculateFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereBillingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereBillingFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereDiscoveryEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereDiscoveryFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereDiscoveryWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereLastReport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereLoglevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereMaster($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePingFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerDownRetry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerGroups($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster wherePollerWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereServicesEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereServicesFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereServicesWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereUpdateEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereUpdateFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereWatchdogEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerCluster whereWatchdogLog($value)
 * @mixin \Eloquent
 */
class PollerCluster extends Model
{
    public $timestamps = false;
    protected $table = 'poller_cluster';
    protected $primaryKey = 'id';
    protected $fillable = ['poller_name'];

    // ---- Accessors/Mutators ----

    public function setPollerGroupsAttribute($groups)
    {
        $this->attributes['poller_groups'] = is_array($groups) ? implode(',', $groups) : $groups;
    }

    // ---- Helpers ----

    /**
     * Get the frontend config definition for this poller
     *
     * @param \Illuminate\Support\Collection $groups optionally supply full list of poller groups to avoid fetching multiple times
     * @return array[]
     */
    public function configDefinition($groups = null)
    {
        if (empty($groups)) {
            $groups = PollerGroup::list();
        }

        return [
            [
                'name' => 'poller_groups',
                'default' => \LibreNMS\Config::get('distributed_poller_group'),
                'value' => $this->poller_groups ?? \LibreNMS\Config::get('distributed_poller_group'),
                'type' => 'multiple',
                'options' => $groups,
            ],
            [
                'name' => 'poller_enabled',
                'default' => \LibreNMS\Config::get('service_poller_enabled'),
                'value' => (bool) ($this->poller_enabled ?? \LibreNMS\Config::get('service_poller_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'poller_workers',
                'default' => \LibreNMS\Config::get('service_poller_workers'),
                'value' => $this->poller_workers ?? \LibreNMS\Config::get('service_poller_workers'),
                'type' => 'integer',
                'units' => 'workers',
            ],
            [
                'name' => 'poller_frequency',
                'default' => \LibreNMS\Config::get('service_poller_frequency'),
                'value' => $this->poller_workers ?? \LibreNMS\Config::get('service_poller_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'poller_down_retry',
                'default' => \LibreNMS\Config::get('service_poller_down_retry'),
                'value' => $this->poller_down_retry ?? \LibreNMS\Config::get('service_poller_down_retry'),
                'type' => 'integer',
                'units' => 'seconds',
            ],
            [
                'name' => 'discovery_enabled',
                'default' => \LibreNMS\Config::get('service_discovery_enabled'),
                'value' => (bool) ($this->discovery_enabled ?? \LibreNMS\Config::get('service_discovery_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'discovery_workers',
                'default' => \LibreNMS\Config::get('service_discovery_workers'),
                'value' => $this->discovery_workers ?? \LibreNMS\Config::get('service_discovery_workers'),
                'type' => 'integer',
                'units' => 'workers',
            ],
            [
                'name' => 'discovery_frequency',
                'default' => \LibreNMS\Config::get('service_discovery_frequency'),
                'value' => $this->discovery_frequency ?? \LibreNMS\Config::get('service_discovery_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'services_enabled',
                'default' => \LibreNMS\Config::get('service_services_enabled'),
                'value' => (bool) ($this->services_enabled ?? \LibreNMS\Config::get('service_services_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'services_workers',
                'default' => \LibreNMS\Config::get('service_services_workers'),
                'value' => $this->services_workers ?? \LibreNMS\Config::get('service_services_workers'),
                'type' => 'integer',
                'units' => 'workers',
            ],
            [
                'name' => 'services_frequency',
                'default' => \LibreNMS\Config::get('service_services_frequency'),
                'value' => $this->services_frequency ?? \LibreNMS\Config::get('service_services_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'billing_enabled',
                'default' => \LibreNMS\Config::get('service_billing_enabled'),
                'value' => (bool) ($this->billing_enabled ?? \LibreNMS\Config::get('service_billing_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'billing_frequency',
                'default' => \LibreNMS\Config::get('service_billing_frequency'),
                'value' => $this->billing_frequency ?? \LibreNMS\Config::get('service_billing_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'billing_calculate_frequency',
                'default' => \LibreNMS\Config::get('service_billing_calculate_frequency'),
                'value' => $this->billing_calculate_frequency ?? \LibreNMS\Config::get('service_billing_calculate_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'alerting_enabled',
                'default' => \LibreNMS\Config::get('service_alerting_enabled'),
                'value' => (bool) ($this->alerting_enabled ?? \LibreNMS\Config::get('service_alerting_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'alerting_frequency',
                'default' => \LibreNMS\Config::get('service_alerting_frequency'),
                'value' => $this->alerting_frequency ?? \LibreNMS\Config::get('service_alerting_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'ping_enabled',
                'default' => \LibreNMS\Config::get('service_ping_enabled'),
                'value' => (bool) ($this->ping_enabled ?? \LibreNMS\Config::get('service_ping_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'ping_frequency',
                'default' => \LibreNMS\Config::get('ping_rrd_step'),
                'value' => $this->ping_frequency ?? \LibreNMS\Config::get('ping_rrd_step'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'update_enabled',
                'default' => \LibreNMS\Config::get('service_update_enabled'),
                'value' => (bool) ($this->update_enabled ?? \LibreNMS\Config::get('service_update_enabled')),
                'type' => 'boolean',
                'advanced' => true,
            ],
            [
                'name' => 'update_frequency',
                'default' => \LibreNMS\Config::get('service_update_frequency'),
                'value' => $this->update_frequency ?? \LibreNMS\Config::get('service_update_frequency'),
                'type' => 'integer',
                'units' => 'seconds',
                'advanced' => true,
            ],
            [
                'name' => 'loglevel',
                'default' => \LibreNMS\Config::get('service_loglevel'),
                'value' => $this->loglevel ?? \LibreNMS\Config::get('service_loglevel'),
                'type' => 'select',
                'options' => [
                    'DEBUG' => 'DEBUG',
                    'INFO' => 'INFO',
                    'WARNING' => 'WARNING',
                    'ERROR' => 'ERROR',
                    'CRITICAL' => 'CRITICAL',
                ],
            ],
            [
                'name' => 'watchdog_enabled',
                'default' => \LibreNMS\Config::get('service_watchdog_enabled'),
                'value' => (bool) ($this->watchdog_enabled ?? \LibreNMS\Config::get('service_watchdog_enabled')),
                'type' => 'boolean',
            ],
            [
                'name' => 'watchdog_log',
                'default' => \LibreNMS\Config::get('log_file'),
                'value' => $this->watchdog_log ?? \LibreNMS\Config::get('log_file'),
                'type' => 'text',
                'advanced' => true,
            ],
        ];
    }

    // ---- Relationships ----

    public function stats()
    {
        return $this->hasMany(\App\Models\PollerClusterStat::class, 'parent_poller', 'id');
    }
}
