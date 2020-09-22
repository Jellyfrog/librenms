<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LibreNMS\Util\Rewrite;
use Permissions;

/**
 * App\Models\Port
 *
 * @property int $port_id
 * @property int $device_id
 * @property string|null $port_descr_type
 * @property string|null $port_descr_descr
 * @property string|null $port_descr_circuit
 * @property string|null $port_descr_speed
 * @property string|null $port_descr_notes
 * @property string|null $ifDescr
 * @property string|null $ifName
 * @property string|null $portName
 * @property int|null $ifIndex
 * @property int|null $ifSpeed
 * @property int|null $ifSpeed_prev
 * @property string|null $ifConnectorPresent
 * @property string|null $ifPromiscuousMode
 * @property int|null $ifHighSpeed
 * @property int|null $ifHighSpeed_prev
 * @property string|null $ifOperStatus
 * @property string|null $ifOperStatus_prev
 * @property string|null $ifAdminStatus
 * @property string|null $ifAdminStatus_prev
 * @property string|null $ifDuplex
 * @property int|null $ifMtu
 * @property string|null $ifType
 * @property string|null $ifAlias
 * @property string|null $ifPhysAddress
 * @property string|null $ifHardType
 * @property int $ifLastChange
 * @property string $ifVlan
 * @property string|null $ifTrunk
 * @property int $ifVrf
 * @property int|null $counter_in
 * @property int|null $counter_out
 * @property int $ignore
 * @property int $disabled
 * @property int $detailed
 * @property int $deleted
 * @property string|null $pagpOperationMode
 * @property string|null $pagpPortState
 * @property string|null $pagpPartnerDeviceId
 * @property string|null $pagpPartnerLearnMethod
 * @property int|null $pagpPartnerIfIndex
 * @property int|null $pagpPartnerGroupIfIndex
 * @property string|null $pagpPartnerDeviceName
 * @property string|null $pagpEthcOperationMode
 * @property string|null $pagpDeviceId
 * @property int|null $pagpGroupIfIndex
 * @property int|null $ifInUcastPkts
 * @property int|null $ifInUcastPkts_prev
 * @property int|null $ifInUcastPkts_delta
 * @property int|null $ifInUcastPkts_rate
 * @property int|null $ifOutUcastPkts
 * @property int|null $ifOutUcastPkts_prev
 * @property int|null $ifOutUcastPkts_delta
 * @property int|null $ifOutUcastPkts_rate
 * @property int|null $ifInErrors
 * @property int|null $ifInErrors_prev
 * @property int|null $ifInErrors_delta
 * @property int|null $ifInErrors_rate
 * @property int|null $ifOutErrors
 * @property int|null $ifOutErrors_prev
 * @property int|null $ifOutErrors_delta
 * @property int|null $ifOutErrors_rate
 * @property int|null $ifInOctets
 * @property int|null $ifInOctets_prev
 * @property int|null $ifInOctets_delta
 * @property int|null $ifInOctets_rate
 * @property int|null $ifOutOctets
 * @property int|null $ifOutOctets_prev
 * @property int|null $ifOutOctets_delta
 * @property int|null $ifOutOctets_rate
 * @property int|null $poll_time
 * @property int|null $poll_prev
 * @property int|null $poll_period
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortAdsl[] $adsl
 * @property-read int|null $adsl_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Eventlog[] $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortsFdb[] $fdbEntries
 * @property-read int|null $fdb_entries_count
 * @property-read mixed $if_phys_address
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv4Address[] $ipv4
 * @property-read int|null $ipv4_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv6Address[] $ipv6
 * @property-read int|null $ipv6_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MacAccounting[] $macAccounting
 * @property-read int|null $mac_accounting_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv4Mac[] $macs
 * @property-read int|null $macs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortsNac[] $nac
 * @property-read int|null $nac_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OspfNbr[] $ospfNeighbors
 * @property-read int|null $ospf_neighbors_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OspfPort[] $ospfPorts
 * @property-read int|null $ospf_ports_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Pseudowire[] $pseudowires
 * @property-read int|null $pseudowires_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortStatistic[] $statistics
 * @property-read int|null $statistics_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortStp[] $stp
 * @property-read int|null $stp_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PortVlan[] $vlans
 * @property-read int|null $vlans_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port hasErrors()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isDeleted()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isDisabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isDown()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isIgnored()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isNotDeleted()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isShutdown()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isUp()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port isValid()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereCounterIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereCounterOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereDetailed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfAdminStatusPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfConnectorPresent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfDuplex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfHardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfHighSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfHighSpeedPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInErrorsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInErrorsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInErrorsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInOctets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInOctetsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInOctetsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInOctetsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInUcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInUcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInUcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfInUcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOperStatusPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutErrorsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutErrorsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutErrorsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutOctets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutOctetsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutOctetsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutOctetsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutUcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutUcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutUcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfOutUcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfPhysAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfPromiscuousMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfSpeedPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfTrunk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfVlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIfVrf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port whereIgnore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpEthcOperationMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpGroupIfIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpOperationMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPartnerDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPartnerDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPartnerGroupIfIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPartnerIfIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPartnerLearnMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePagpPortState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePollPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePollPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePollTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortDescrCircuit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortDescrDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortDescrNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortDescrSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortDescrType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Port wherePortName($value)
 * @mixin \Eloquent
 */
class Port extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'port_id';

    /**
     * Initialize this class
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function (Port $port) {
            // delete related data
            $port->adsl()->delete();
            $port->fdbEntries()->delete();
            $port->ipv4()->delete();
            $port->ipv6()->delete();
            $port->macAccounting()->delete();
            $port->macs()->delete();
            $port->nac()->delete();
            $port->ospfNeighbors()->delete();
            $port->ospfPorts()->delete();
            $port->pseudowires()->delete();
            $port->statistics()->delete();
            $port->stp()->delete();
            $port->vlans()->delete();

            // dont have relationships yet
            DB::table('juniAtmVp')->where('port_id', $port->port_id)->delete();
            DB::table('ports_perms')->where('port_id', $port->port_id)->delete();
            DB::table('links')->where('local_port_id', $port->port_id)->orWhere('remote_port_id', $port->port_id)->delete();
            DB::table('ports_stack')->where('port_id_low', $port->port_id)->orWhere('port_id_high', $port->port_id)->delete();

            \Rrd::purge(optional($port->device)->hostname, \Rrd::portName($port->port_id)); // purge all port rrd files
        });
    }

    // ---- Helper Functions ----

    /**
     * Returns a human readable label for this port
     *
     * @return string
     */
    public function getLabel()
    {
        $os = $this->device->os;

        if (\LibreNMS\Config::getOsSetting($os, 'ifname')) {
            $label = $this->ifName;
        } elseif (\LibreNMS\Config::getOsSetting($os, 'ifalias')) {
            $label = $this->ifAlias;
        }

        if (empty($label)) {
            $label = $this->ifDescr;

            if (\LibreNMS\Config::getOsSetting($os, 'ifindex')) {
                $label .= " $this->ifIndex";
            }
        }

        foreach ((array) \LibreNMS\Config::get('rewrite_if', []) as $src => $val) {
            if (Str::contains(strtolower($label), strtolower($src))) {
                $label = $val;
            }
        }

        foreach ((array) \LibreNMS\Config::get('rewrite_if_regexp', []) as $reg => $val) {
            $label = preg_replace($reg . 'i', $val, $label);
        }

        return $label;
    }

    /**
     * Get the shortened label for this device.  Replaces things like GigabitEthernet with GE.
     *
     * @return string
     */
    public function getShortLabel()
    {
        return Rewrite::shortenIfName(Rewrite::normalizeIfName($this->ifName ?: $this->ifDescr));
    }

    /**
     * Check if user can access this port.
     *
     * @param User|int $user
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

        return Permissions::canAccessDevice($this->device_id, $user) || Permissions::canAccessPort($this->port_id, $user);
    }

    // ---- Accessors/Mutators ----

    public function getIfPhysAddressAttribute($mac)
    {
        if (! empty($mac)) {
            return preg_replace('/(..)(..)(..)(..)(..)(..)/', '\\1:\\2:\\3:\\4:\\5:\\6', $mac);
        }

        return null;
    }

    // ---- Query scopes ----

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsDeleted($query)
    {
        return $query->where([
            ['deleted', 1],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsNotDeleted($query)
    {
        return $query->where([
            ['deleted', 0],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsUp($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['ignore', '=', 0],
            ['disabled', '=', 0],
            ['ifOperStatus', '=', 'up'],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsDown($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['ignore', '=', 0],
            ['disabled', '=', 0],
            ['ifOperStatus', '!=', 'up'],
            ['ifAdminStatus', '=', 'up'],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsShutdown($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['ignore', '=', 0],
            ['disabled', '=', 0],
            ['ifAdminStatus', '=', 'down'],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsIgnored($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['ignore', '=', 1],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsDisabled($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['disabled', '=', 1],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeHasErrors($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['ignore', '=', 0],
            ['disabled', '=', 0],
        ])->where(function ($query) {
            /** @var Builder $query */
            $query->where('ifInErrors_delta', '>', 0)
                ->orWhere('ifOutErrors_delta', '>', 0);
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsValid($query)
    {
        return $query->where([
            ['deleted', '=', 0],
            ['disabled', '=', 0],
        ]);
    }

    public function scopeHasAccess($query, User $user)
    {
        return $this->hasPortAccess($query, $user);
    }

    // ---- Define Relationships ----

    public function adsl()
    {
        return $this->hasMany(PortAdsl::class, 'port_id');
    }

    public function events()
    {
        return $this->morphMany(Eventlog::class, 'events', 'type', 'reference');
    }

    public function fdbEntries()
    {
        return $this->hasMany(\App\Models\PortsFdb::class, 'port_id', 'port_id');
    }

    public function ipv4()
    {
        return $this->hasMany(\App\Models\Ipv4Address::class, 'port_id');
    }

    public function ipv6()
    {
        return $this->hasMany(\App\Models\Ipv6Address::class, 'port_id');
    }

    public function macAccounting()
    {
        return $this->hasMany(MacAccounting::class, 'port_id');
    }

    public function macs()
    {
        return $this->hasMany(Ipv4Mac::class, 'port_id');
    }

    public function nac()
    {
        return $this->hasMany(PortsNac::class, 'port_id');
    }

    public function ospfNeighbors()
    {
        return $this->hasMany(OspfNbr::class, 'port_id');
    }

    public function ospfPorts()
    {
        return $this->hasMany(OspfPort::class, 'port_id');
    }

    public function pseudowires()
    {
        return $this->hasMany(Pseudowire::class, 'port_id');
    }

    public function statistics()
    {
        return $this->hasMany(PortStatistic::class, 'port_id');
    }

    public function stp()
    {
        return $this->hasMany(PortStp::class, 'port_id');
    }

    public function users()
    {
        // FIXME does not include global read
        return $this->belongsToMany(\App\Models\User::class, 'ports_perms', 'port_id', 'user_id');
    }

    public function vlans()
    {
        return $this->hasMany(PortVlan::class, 'port_id');
    }
}
