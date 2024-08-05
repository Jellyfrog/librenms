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
            Str::make('asText', 'astext'),
            Str::make('peerIdentifier', 'bgpPeerIdentifier'),
            Number::make('peerRemoteAs', 'bgpPeerRemoteAs'),
            Str::make('peerState', 'bgpPeerState'),
            Str::make('peerAdminStatus', 'bgpPeerAdminStatus'),
            Number::make('peerLastErrorCode', 'bgpPeerLastErrorCode'),
            Number::make('peerLastErrorSubCode', 'bgpPeerLastErrorSubCode'),
            Str::make('peerLastErrorText', 'bgpPeerLastErrorText'),
            Number::make('peerIface', 'bgpPeerIface'),
            Str::make('localAddr', 'bgpLocalAddr'),
            Str::make('peerRemoteAddr', 'bgpPeerRemoteAddr'),
            Str::make('peerDescr', 'bgpPeerDescr'),
            Number::make('peerInUpdates', 'bgpPeerInUpdates'),
            Number::make('peerOutUpdates', 'bgpPeerOutUpdates'),
            Number::make('peerInTotalMessages', 'bgpPeerInTotalMessages'),
            Number::make('peerOutTotalMessages', 'bgpPeerOutTotalMessages'),
            Number::make('peerFsmEstablishedTime', 'bgpPeerFsmEstablishedTime'),
            Number::make('peerInUpdateElapsedTime', 'bgpPeerInUpdateElapsedTime'),
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

            Where::make('localAs', 'bgpLocalAs'),
            Where::make('peerRemoteAs', 'bgpPeerRemoteAs'),
            Where::make('localAddr', 'bgpLocalAddr'),
            Where::make('peerIdentifier', 'bgpPeerIdentifier'),
            Where::make('peerState', 'bgpPeerState'),
            Where::make('peerAdminStatus', 'bgpPeerAdminStatus'),
            WhereLike::make('peerDescr', 'bgpPeerDescr'),
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
