<?php

namespace App\Models;

use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;
use LibreNMS\Exceptions\InvalidIpException;
use LibreNMS\Util\IP;
use LibreNMS\Util\IPv4;
use LibreNMS\Util\IPv6;
use LibreNMS\Util\Rewrite;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;
use Permissions;

/**
 * App\Models\Device
 *
 * @property int $device_id
 * @property string|null $inserted
 * @property string $hostname
 * @property string|null $sysName
 * @property mixed|null $ip
 * @property string|null $overwrite_ip
 * @property string|null $community
 * @property string|null $authlevel
 * @property string|null $authname
 * @property string|null $authpass
 * @property string|null $authalgo
 * @property string|null $cryptopass
 * @property string|null $cryptoalgo
 * @property string $snmpver
 * @property int $port
 * @property string $transport
 * @property int|null $timeout
 * @property int|null $retries
 * @property int $snmp_disable
 * @property int|null $bgpLocalAs
 * @property string|null $sysObjectID
 * @property string|null $sysDescr
 * @property string|null $sysContact
 * @property string|null $version
 * @property string|null $hardware
 * @property string|null $features
 * @property int|null $location_id
 * @property string|null $os
 * @property bool $status
 * @property string $status_reason
 * @property int $ignore
 * @property int $disabled
 * @property int|null $uptime
 * @property int $agent_uptime
 * @property \Illuminate\Support\Carbon|null $last_polled
 * @property string|null $last_poll_attempted
 * @property float|null $last_polled_timetaken
 * @property float|null $last_discovered_timetaken
 * @property string|null $last_discovered
 * @property string|null $last_ping
 * @property float|null $last_ping_timetaken
 * @property string|null $purpose
 * @property string $type
 * @property string|null $serial
 * @property string|null $icon
 * @property int $poller_group
 * @property int|null $override_sysLocation
 * @property string|null $notes
 * @property int $port_association_mode
 * @property int $max_depth
 * @property int $disable_notify
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AccessPoint[] $accessPoints
 * @property-read int|null $access_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AlertSchedule[] $alertSchedules
 * @property-read int|null $alert_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Alert[] $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Application[] $applications
 * @property-read int|null $applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DeviceAttrib[] $attribs
 * @property-read int|null $attribs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BgpPeer[] $bgppeers
 * @property-read int|null $bgppeers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CefSwitching[] $cefSwitching
 * @property-read int|null $cef_switching_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Device[] $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Component[] $components
 * @property-read int|null $components_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntPhysical[] $entityPhysical
 * @property-read int|null $entity_physical_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Eventlog[] $eventlogs
 * @property-read int|null $eventlogs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DeviceGraph[] $graphs
 * @property-read int|null $graphs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DeviceGroup[] $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\HrDevice[] $hostResources
 * @property-read int|null $host_resources_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\IpsecTunnel[] $ipsecTunnels
 * @property-read int|null $ipsec_tunnels_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv4Address[] $ipv4
 * @property-read int|null $ipv4_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv6Address[] $ipv6
 * @property-read int|null $ipv6_count
 * @property-read \App\Models\Location|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MefInfo[] $mefInfo
 * @property-read int|null $mef_info_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Mempool[] $mempools
 * @property-read int|null $mempools_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsLspPath[] $mplsLspPaths
 * @property-read int|null $mpls_lsp_paths_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsLsp[] $mplsLsps
 * @property-read int|null $mpls_lsps_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSap[] $mplsSaps
 * @property-read int|null $mpls_saps_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSdpBind[] $mplsSdpBinds
 * @property-read int|null $mpls_sdp_binds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSdp[] $mplsSdps
 * @property-read int|null $mpls_sdps_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsService[] $mplsServices
 * @property-read int|null $mpls_services_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsTunnelArHop[] $mplsTunnelArHops
 * @property-read int|null $mpls_tunnel_ar_hops_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsTunnelCHop[] $mplsTunnelCHops
 * @property-read int|null $mpls_tunnel_c_hops_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MuninPlugin[] $muninPlugins
 * @property-read int|null $munin_plugins_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\NetscalerVserver[] $netscalerVservers
 * @property-read int|null $netscaler_vservers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OspfInstance[] $ospfInstances
 * @property-read int|null $ospf_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OspfNbr[] $ospfNbrs
 * @property-read int|null $ospf_nbrs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OspfPort[] $ospfPorts
 * @property-read int|null $ospf_ports_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Package[] $packages
 * @property-read int|null $packages_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Device[] $parents
 * @property-read int|null $parents_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DevicePerf[] $perf
 * @property-read int|null $perf_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Port[] $ports
 * @property-read int|null $ports_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortsFdb[] $portsFdb
 * @property-read int|null $ports_fdb_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortsNac[] $portsNac
 * @property-read int|null $ports_nac_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Toner[] $printerSupplies
 * @property-read int|null $printer_supplies_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Processor[] $processors
 * @property-read int|null $processors_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Pseudowire[] $pseudowires
 * @property-read int|null $pseudowires_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LoadbalancerRserver[] $rServers
 * @property-read int|null $r_servers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Route[] $routes
 * @property-read int|null $routes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AlertRule[] $rules
 * @property-read int|null $rules_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sensor[] $sensors
 * @property-read int|null $sensors_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Service[] $services
 * @property-read int|null $services_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sla[] $slas
 * @property-read int|null $slas_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Storage[] $storage
 * @property-read int|null $storage_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stp[] $stpInstances
 * @property-read int|null $stp_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Syslog[] $syslogs
 * @property-read int|null $syslogs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LoadbalancerVserver[] $vServers
 * @property-read int|null $v_servers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vlan[] $vlans
 * @property-read int|null $vlans_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vminfo[] $vminfo
 * @property-read int|null $vminfo_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VrfLite[] $vrfLites
 * @property-read int|null $vrf_lites_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vrf[] $vrfs
 * @property-read int|null $vrfs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WirelessSensor[] $wirelessSensors
 * @property-read int|null $wireless_sensors_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device canPing()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isActive()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isDisableNotify()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isDisabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isDown()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isIgnored()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isNotDisabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device isUp()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device notIgnored()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereAgentUptime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereAuthalgo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereAuthlevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereAuthname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereAuthpass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereBgpLocalAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereCommunity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereCryptoalgo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereCryptopass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereDisableNotify($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereHardware($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereIgnore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereInserted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastDiscovered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastDiscoveredTimetaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastPing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastPingTimetaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastPollAttempted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastPolled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLastPolledTimetaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereMaxDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereOverrideSysLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereOverwriteIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device wherePollerGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device wherePortAssociationMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereRetries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSnmpDisable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSnmpver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereStatusReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSysContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSysDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSysName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereSysObjectID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereTransport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereUptime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Device whereVersion($value)
 * @mixin \Eloquent
 */
class Device extends BaseModel
{
    use PivotEventTrait;

    public $timestamps = false;
    protected $primaryKey = 'device_id';
    protected $fillable = ['hostname', 'ip', 'status', 'status_reason', 'sysName', 'sysDescr', 'sysObjectID', 'hardware', 'version', 'features', 'serial', 'icon'];
    protected $casts = [
        'last_polled' => 'datetime',
        'status' => 'boolean',
    ];

    // ---- Helper Functions ----

    public static function findByHostname($hostname)
    {
        return static::where('hostname', $hostname)->first();
    }

    /**
     * Returns IP/Hostname where polling will be targeted to
     *
     * @param string $device hostname which will be triggered
     *        array  $device associative array with device data
     * @return string IP/Hostname to which Device polling is targeted
     */
    public static function pollerTarget($device)
    {
        if (! is_array($device)) {
            $ret = static::where('hostname', $device)->first(['hostname', 'overwrite_ip']);
            if (empty($ret)) {
                return $device;
            }
            $overwrite_ip = $ret->overwrite_ip;
            $hostname = $ret->hostname;
        } elseif (array_key_exists('overwrite_ip', $device)) {
            $overwrite_ip = $device['overwrite_ip'];
            $hostname = $device['hostname'];
        } else {
            return $device['hostname'];
        }

        return $overwrite_ip ?: $hostname;
    }

    public static function findByIp($ip)
    {
        if (! IP::isValid($ip)) {
            return null;
        }

        $device = static::where('hostname', $ip)->orWhere('ip', inet_pton($ip))->first();

        if ($device) {
            return $device;
        }

        try {
            $ipv4 = new IPv4($ip);
            $port = Ipv4Address::where('ipv4_address', (string) $ipv4)
                ->with('port', 'port.device')
                ->firstOrFail()->port;
            if ($port) {
                return $port->device;
            }
        } catch (InvalidIpException $e) {
            //
        } catch (ModelNotFoundException $e) {
            //
        }

        try {
            $ipv6 = new IPv6($ip);
            $port = Ipv6Address::where('ipv6_address', $ipv6->uncompressed())
                ->with(['port', 'port.device'])
                ->firstOrFail()->port;
            if ($port) {
                return $port->device;
            }
        } catch (InvalidIpException $e) {
            //
        } catch (ModelNotFoundException $e) {
            //
        }

        return null;
    }

    /**
     * Get the display name of this device (hostname) unless force_ip_to_sysname is set
     * and hostname is an IP and sysName is set
     *
     * @return string
     */
    public function displayName()
    {
        if (\LibreNMS\Config::get('force_ip_to_sysname') && $this->sysName && IP::isValid($this->hostname)) {
            return $this->sysName;
        }

        return $this->hostname;
    }

    public function name()
    {
        $displayName = $this->displayName();
        if ($this->sysName !== $displayName) {
            return $this->sysName;
        } elseif ($this->hostname !== $displayName && $this->hostname !== $this->ip) {
            return $this->hostname;
        }

        return '';
    }

    public function isUnderMaintenance()
    {
        if (! $this->device_id) {
            return false;
        }

        $query = AlertSchedule::isActive()
            ->where(function (Builder $query) {
                $query->whereHas('devices', function (Builder $query) {
                    $query->where('alert_schedulables.alert_schedulable_id', $this->device_id);
                });

                if ($this->groups) {
                    $query->orWhereHas('deviceGroups', function (Builder $query) {
                        $query->whereIn('alert_schedulables.alert_schedulable_id', $this->groups->pluck('id'));
                    });
                }

                if ($this->location) {
                    $query->orWhereHas('locations', function (Builder $query) {
                        $query->where('alert_schedulables.alert_schedulable_id', $this->location->id);
                    });
                }
            });

        return $query->exists();
    }

    /**
     * Get the shortened display name of this device.
     * Length is always overridden by shorthost_target_length.
     *
     * @param int $length length to shorten to, will not break up words so may be longer
     * @return string
     */
    public function shortDisplayName($length = 12)
    {
        $name = $this->displayName();

        // IP addresses should not be shortened
        if (IP::isValid($name)) {
            return $name;
        }

        $length = \LibreNMS\Config::get('shorthost_target_length', $length);
        if ($length < strlen($name)) {
            $take = substr_count($name, '.', 0, $length) + 1;

            return implode('.', array_slice(explode('.', $name), 0, $take));
        }

        return $name;
    }

    /**
     * Check if user can access this device.
     *
     * @param User $user
     * @return bool
     */
    public function canAccess($user)
    {
        if (! $user) {
            return false;
        }

        if ($user->hasGlobalRead()) {
            return true;
        }

        return Permissions::canAccessDevice($this->device_id, $user->user_id);
    }

    public function formatDownUptime($short = false)
    {
        $time = ($this->status == 1) ? $this->uptime : time() - strtotime($this->last_polled);

        return Time::formatInterval($time, $short);
    }

    /**
     * @return string
     */
    public function logo()
    {
        $base_name = pathinfo($this->icon, PATHINFO_FILENAME);
        $options = [
            "images/logos/$base_name.svg",
            "images/logos/$base_name.png",
            "images/os/$base_name.svg",
            "images/os/$base_name.png",
        ];

        foreach ($options as $file) {
            if (is_file(public_path() . "/$file")) {
                return asset($file);
            }
        }

        return asset('images/os/generic.svg');
    }

    /**
     * Update the max_depth field based on parents
     * Performs SQL query, so make sure all parents are saved first
     *
     * @param int $exclude exclude a device_id from being considered (used for deleting)
     */
    public function updateMaxDepth($exclude = null)
    {
        // optimize for memory instead of time
        $query = $this->parents()->getQuery();
        if (! is_null($exclude)) {
            $query->where('device_id', '!=', $exclude);
        }

        $count = $query->count();
        if ($count === 0) {
            if ($this->children()->count() === 0) {
                $this->max_depth = 0; // no children or parents
            } else {
                $this->max_depth = 1; // has children
            }
        } else {
            $parents_max_depth = $query->max('max_depth');
            $this->max_depth = $parents_max_depth + 1;
        }

        $this->save();
    }

    /**
     * Device dependency check to see if this node is standalone or not.
     * Standalone is a special case where the device has no parents or children and is denoted by a max_depth of 0
     *
     * Only checks on root nodes (where max_depth is 1 or 0)
     */
    public function validateStandalone()
    {
        if ($this->max_depth === 0 && $this->children()->count() > 0) {
            $this->max_depth = 1; // has children
        } elseif ($this->max_depth === 1 && $this->parents()->count() === 0) {
            $this->max_depth = 0; // no children or parents
        }

        $this->save();
    }

    public function getAttrib($name)
    {
        return $this->attribs->pluck('attrib_value', 'attrib_type')->get($name);
    }

    public function setAttrib($name, $value)
    {
        $attrib = $this->attribs->first(function ($item) use ($name) {
            return $item->attrib_type === $name;
        });

        if (! $attrib) {
            $attrib = new DeviceAttrib(['attrib_type' => $name]);
            $this->attribs->push($attrib);
        }

        $attrib->attrib_value = $value;

        return (bool) $this->attribs()->save($attrib);
    }

    public function forgetAttrib($name)
    {
        $attrib_index = $this->attribs->search(function ($attrib) use ($name) {
            return $attrib->attrib_type === $name;
        });

        if ($attrib_index !== false) {
            $deleted = (bool) $this->attribs->get($attrib_index)->delete();
            // only forget the attrib_index after delete, otherwise delete() will fail fatally with:
            // Symfony\\Component\\Debug\Exception\\FatalThrowableError(code: 0):  Call to a member function delete() on null
            $this->attribs->forget($attrib_index);

            return $deleted;
        }

        return false;
    }

    public function getAttribs()
    {
        return $this->attribs->pluck('attrib_value', 'attrib_type')->toArray();
    }

    public function setLocation($location_text)
    {
        $location_text = $location_text ? Rewrite::location($location_text) : null;

        $this->location_id = null;
        if ($location_text) {
            $location = Location::firstOrCreate(['location' => $location_text]);
            $this->location()->associate($location);
        }
    }

    // ---- Accessors/Mutators ----

    public function getIconAttribute($icon)
    {
        return Str::start(Url::findOsImage($this->os, $this->features, $icon), 'images/os/');
    }

    public function getIpAttribute($ip)
    {
        if (empty($ip)) {
            return null;
        }
        // @ suppresses warning, inet_ntop() returns false if it fails
        return @inet_ntop($ip) ?: null;
    }

    public function setIpAttribute($ip)
    {
        $this->attributes['ip'] = inet_pton($ip);
    }

    public function setStatusAttribute($status)
    {
        $this->attributes['status'] = (int) $status;
    }

    // ---- Query scopes ----

    public function scopeIsUp($query)
    {
        return $query->where([
            ['status', '=', 1],
            ['ignore', '=', 0],
            ['disable_notify', '=', 0],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeIsActive($query)
    {
        return $query->where([
            ['ignore', '=', 0],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeIsDown($query)
    {
        return $query->where([
            ['status', '=', 0],
            ['disable_notify', '=', 0],
            ['ignore', '=', 0],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeIsIgnored($query)
    {
        return $query->where([
            ['ignore', '=', 1],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeNotIgnored($query)
    {
        return $query->where([
            ['ignore', '=', 0],
        ]);
    }

    public function scopeIsDisabled($query)
    {
        return $query->where([
            ['disabled', '=', 1],
        ]);
    }

    public function scopeIsDisableNotify($query)
    {
        return $query->where([
            ['disable_notify', '=', 1],
        ]);
    }

    public function scopeIsNotDisabled($query)
    {
        return $query->where([
            ['disable_notify', '=', 0],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeWhereUptime($query, $uptime, $modifier = '<')
    {
        return $query->where([
            ['uptime', '>', 0],
            ['uptime', $modifier, $uptime],
        ]);
    }

    public function scopeCanPing(Builder $query)
    {
        return $query->where('disabled', 0)
            ->leftJoin('devices_attribs', function (JoinClause $query) {
                $query->on('devices.device_id', 'devices_attribs.device_id')
                    ->where('devices_attribs.attrib_type', 'override_icmp_disable');
            })
            ->where(function (Builder $query) {
                $query->whereNull('devices_attribs.attrib_value')
                    ->orWhere('devices_attribs.attrib_value', '!=', 'true');
            });
    }

    public function scopeHasAccess($query, User $user)
    {
        return $this->hasDeviceAccess($query, $user);
    }

    public function scopeInDeviceGroup($query, $deviceGroup)
    {
        return $query->whereIn($query->qualifyColumn('device_id'), function ($query) use ($deviceGroup) {
            $query->select('device_id')
                ->from('device_group_device')
                ->where('device_group_id', $deviceGroup);
        });
    }

    // ---- Define Relationships ----

    public function accessPoints()
    {
        return $this->hasMany(AccessPoint::class, 'device_id');
    }

    public function alerts()
    {
        return $this->hasMany(\App\Models\Alert::class, 'device_id');
    }

    public function attribs()
    {
        return $this->hasMany(\App\Models\DeviceAttrib::class, 'device_id');
    }

    public function alertSchedules()
    {
        return $this->morphToMany(\App\Models\AlertSchedule::class, 'alert_schedulable', 'alert_schedulables', 'schedule_id', 'schedule_id');
    }

    public function applications()
    {
        return $this->hasMany(\App\Models\Application::class, 'device_id');
    }

    public function bgppeers()
    {
        return $this->hasMany(\App\Models\BgpPeer::class, 'device_id');
    }

    public function cefSwitching()
    {
        return $this->hasMany(\App\Models\CefSwitching::class, 'device_id');
    }

    public function children()
    {
        return $this->belongsToMany(self::class, 'device_relationships', 'parent_device_id', 'child_device_id');
    }

    public function components()
    {
        return $this->hasMany(\App\Models\Component::class, 'device_id');
    }

    public function hostResources()
    {
        return $this->hasMany(HrDevice::class, 'device_id');
    }

    public function entityPhysical()
    {
        return $this->hasMany(EntPhysical::class, 'device_id');
    }

    public function eventlogs()
    {
        return $this->hasMany(\App\Models\Eventlog::class, 'device_id', 'device_id');
    }

    public function graphs()
    {
        return $this->hasMany(\App\Models\DeviceGraph::class, 'device_id');
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\DeviceGroup::class, 'device_group_device', 'device_id', 'device_group_id');
    }

    public function ipsecTunnels()
    {
        return $this->hasMany(IpsecTunnel::class, 'device_id');
    }

    public function ipv4()
    {
        return $this->hasManyThrough(\App\Models\Ipv4Address::class, \App\Models\Port::class, 'device_id', 'port_id', 'device_id', 'port_id');
    }

    public function ipv6()
    {
        return $this->hasManyThrough(\App\Models\Ipv6Address::class, \App\Models\Port::class, 'device_id', 'port_id', 'device_id', 'port_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
    }

    public function mefInfo()
    {
        return $this->hasMany(MefInfo::class, 'device_id');
    }

    public function muninPlugins()
    {
        return $this->hasMany(\App\Models\MuninPlugin::class, 'device_id');
    }

    public function ospfInstances()
    {
        return $this->hasMany(\App\Models\OspfInstance::class, 'device_id');
    }

    public function ospfNbrs()
    {
        return $this->hasMany(\App\Models\OspfNbr::class, 'device_id');
    }

    public function ospfPorts()
    {
        return $this->hasMany(\App\Models\OspfPort::class, 'device_id');
    }

    public function netscalerVservers()
    {
        return $this->hasMany(NetscalerVserver::class, 'device_id');
    }

    public function packages()
    {
        return $this->hasMany(\App\Models\Package::class, 'device_id', 'device_id');
    }

    public function parents()
    {
        return $this->belongsToMany(self::class, 'device_relationships', 'child_device_id', 'parent_device_id');
    }

    public function perf()
    {
        return $this->hasMany(\App\Models\DevicePerf::class, 'device_id');
    }

    public function ports()
    {
        return $this->hasMany(\App\Models\Port::class, 'device_id', 'device_id');
    }

    public function portsFdb()
    {
        return $this->hasMany(\App\Models\PortsFdb::class, 'device_id', 'device_id');
    }

    public function portsNac()
    {
        return $this->hasMany(\App\Models\PortsNac::class, 'device_id', 'device_id');
    }

    public function processors()
    {
        return $this->hasMany(\App\Models\Processor::class, 'device_id');
    }

    public function routes()
    {
        return $this->hasMany(Route::class, 'device_id');
    }

    public function rules()
    {
        return $this->belongsToMany(\App\Models\AlertRule::class, 'alert_device_map', 'device_id', 'rule_id');
    }

    public function sensors()
    {
        return $this->hasMany(\App\Models\Sensor::class, 'device_id');
    }

    public function services()
    {
        return $this->hasMany(\App\Models\Service::class, 'device_id');
    }

    public function storage()
    {
        return $this->hasMany(\App\Models\Storage::class, 'device_id');
    }

    public function stpInstances()
    {
        return $this->hasMany(Stp::class, 'device_id');
    }

    public function mempools()
    {
        return $this->hasMany(\App\Models\Mempool::class, 'device_id');
    }

    public function mplsLsps()
    {
        return $this->hasMany(\App\Models\MplsLsp::class, 'device_id');
    }

    public function mplsLspPaths()
    {
        return $this->hasMany(\App\Models\MplsLspPath::class, 'device_id');
    }

    public function mplsSdps()
    {
        return $this->hasMany(\App\Models\MplsSdp::class, 'device_id');
    }

    public function mplsServices()
    {
        return $this->hasMany(\App\Models\MplsService::class, 'device_id');
    }

    public function mplsSaps()
    {
        return $this->hasMany(\App\Models\MplsSap::class, 'device_id');
    }

    public function mplsSdpBinds()
    {
        return $this->hasMany(\App\Models\MplsSdpBind::class, 'device_id');
    }

    public function mplsTunnelArHops()
    {
        return $this->hasMany(\App\Models\MplsTunnelArHop::class, 'device_id');
    }

    public function mplsTunnelCHops()
    {
        return $this->hasMany(\App\Models\MplsTunnelCHop::class, 'device_id');
    }

    public function printerSupplies()
    {
        return $this->hasMany(Toner::class, 'device_id');
    }

    public function pseudowires()
    {
        return $this->hasMany(Pseudowire::class, 'device_id');
    }

    public function rServers()
    {
        return $this->hasMany(LoadbalancerRserver::class, 'device_id');
    }

    public function slas()
    {
        return $this->hasMany(Sla::class, 'device_id');
    }

    public function syslogs()
    {
        return $this->hasMany(\App\Models\Syslog::class, 'device_id', 'device_id');
    }

    public function users()
    {
        // FIXME does not include global read
        return $this->belongsToMany(\App\Models\User::class, 'devices_perms', 'device_id', 'user_id');
    }

    public function vminfo()
    {
        return $this->hasMany(\App\Models\Vminfo::class, 'device_id');
    }

    public function vlans()
    {
        return $this->hasMany(\App\Models\Vlan::class, 'device_id');
    }

    public function vrfLites()
    {
        return $this->hasMany(\App\Models\VrfLite::class, 'device_id');
    }

    public function vrfs()
    {
        return $this->hasMany(\App\Models\Vrf::class, 'device_id');
    }

    public function vServers()
    {
        return $this->hasMany(LoadbalancerVserver::class, 'device_id');
    }

    public function wirelessSensors()
    {
        return $this->hasMany(\App\Models\WirelessSensor::class, 'device_id');
    }
}
