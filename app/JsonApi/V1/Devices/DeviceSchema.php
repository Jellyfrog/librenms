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
            Str::make('overwriteIp'),
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
            Boolean::make('snmpDisable'),
            Number::make('bgpLocalAs', 'bgpLocalAs'),
            Str::make('sysObjectID', 'sysObjectID'),
            Str::make('sysDescr', 'sysDescr'),
            Str::make('sysContact', 'sysContact'),
            Str::make('version'),
            Str::make('hardware'),
            Str::make('features'),
            Number::make('locationId'),
            Str::make('os'),
            Boolean::make('status'),
            Str::make('statusReason'),
            Boolean::make('ignore'),
            Boolean::make('disabled'),
            Number::make('uptime'),
            Number::make('agentUptime'),
            DateTime::make('lastPolled'),
            DateTime::make('lastPollAttempted'),
            Number::make('lastPolledTimetaken'),
            Number::make('lastDiscoveredTimetaken'),
            DateTime::make('lastDiscovered'),
            DateTime::make('lastPing'),
            Number::make('lastPingTimetaken'),
            Str::make('purpose'),
            Str::make('type'),
            Str::make('serial'),
            Str::make('icon'),
            Number::make('pollerGroup'),
            Boolean::make('overrideSysLocation', 'override_sysLocation'),
            Str::make('notes'),
            Number::make('portAssociationMode'),
            Number::make('maxDepth'),
            Boolean::make('disableNotify'),

            // Relationships
            HasMany::make('ports'),
            HasMany::make('vlans'),
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
