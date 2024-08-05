<?php

namespace App\JsonApi\V1\Devices;

use App\Models\Device;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class DeviceSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Device::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('inserted'),
            Str::make('hostname'),
            Str::make('sysName', 'sysName'),
            Str::make('display'),
            Str::make('ip'),
            Str::make('overwrite_ip'),
            Str::make('community'),
            Str::make('authlevel'),
            Str::make('authname'),
            Str::make('authpass'),
            Str::make('authalgo'),
            Str::make('cryptopass'),
            Str::make('cryptoalgo'),
            Str::make('snmpver'),
            Number::make('port'),
            Str::make('transport'),
            Number::make('timeout'),
            Number::make('retries'),
            Boolean::make('snmp_disable'),
            Number::make('bgpLocalAs'),
            Str::make('sysObjectID'),
            Str::make('sysDescr', 'sysDescr'),
            Str::make('sysContact', 'sysContact'),
            Str::make('version'),
            Str::make('hardware'),
            Str::make('features'),
            Number::make('location_id'),
            Str::make('os'),
            Boolean::make('status'),
            Str::make('status_reason'),
            Boolean::make('ignore'),
            Boolean::make('disabled'),
            Number::make('uptime'),
            Number::make('agent_uptime'),
            DateTime::make('last_polled'),
            DateTime::make('last_poll_attempted'),
            Number::make('last_polled_timetaken'),
            Number::make('last_discovered_timetaken'),
            DateTime::make('last_discovered'),
            DateTime::make('last_ping'),
            Number::make('last_ping_timetaken'),
            Str::make('purpose'),
            Str::make('type'),
            Str::make('serial'),
            Str::make('icon'),
            Number::make('poller_group'),
            Boolean::make('override_sysLocation'),
            Str::make('notes'),
            Number::make('port_association_mode'),
            Number::make('max_depth'),
            Boolean::make('disable_notify'),

            // Relationships
            HasMany::make('ports'),
            HasMany::make('vlans'),
            HasMany::make('bgp-peers'),
            BelongsTo::make('location'),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Where::make('hostname')->singular(),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
