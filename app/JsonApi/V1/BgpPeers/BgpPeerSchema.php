<?php

namespace App\JsonApi\V1\BgpPeers;

use App\JsonApi\Filters\WhereLike;
use App\Models\BgpPeer;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereHas;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class BgpPeerSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = BgpPeer::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('deviceId'),
            Number::make('vrfId'),
            Str::make('astext'),
            Str::make('identifier', 'bgpPeerIdentifier'),
            Number::make('remoteAs', 'bgpPeerRemoteAs'),
            Str::make('state', 'bgpPeerState'),
            Str::make('adminStatus', 'bgpPeerAdminStatus'),
            Number::make('lastErrorCode', 'bgpPeerLastErrorCode'),
            Number::make('lastErrorSubCode', 'bgpPeerLastErrorSubCode'),
            Str::make('lastErrorText', 'bgpPeerLastErrorText'),
            Number::make('iface', 'bgpPeerIface'),
            Str::make('bgpLocalAddr', 'bgpLocalAddr'),
            Str::make('remoteAddr', 'bgpPeerRemoteAddr'),
            Str::make('descr', 'bgpPeerDescr'),
            Number::make('inUpdates', 'bgpPeerInUpdates'),
            Number::make('outUpdates', 'bgpPeerOutUpdates'),
            Number::make('inTotalMessages', 'bgpPeerInTotalMessages'),
            Number::make('outTotalMessages', 'bgpPeerOutTotalMessages'),
            Number::make('fsmEstablishedTime', 'bgpPeerFsmEstablishedTime'),
            Number::make('inUpdateElapsedTime', 'bgpPeerInUpdateElapsedTime'),
            Str::make('contextName'),

            // Relationships
            BelongsTo::make('device'),
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

            Where::make('remoteAs', 'bgpPeerRemoteAs'),
            Where::make('localAddr', 'bgpLocalAddr'),
            Where::make('identifier', 'bgpPeerIdentifier'),
            Where::make('state', 'bgpPeerState'),
            Where::make('adminStatus', 'bgpPeerAdminStatus'),
            WhereLike::make('descr', 'bgpPeerDescr'),
            Scope::make('family'),

            // Relationships
            WhereHas::make($this, 'device'),
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
