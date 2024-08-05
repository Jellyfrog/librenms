<?php

namespace App\JsonApi\V1\Ports;

use App\Models\Port;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereHas;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class PortSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Port::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('device_id'),
            Str::make('port_descr_type'),
            Str::make('port_descr_descr'),
            Str::make('port_descr_circuit'),
            Str::make('port_descr_speed'),
            Str::make('port_descr_notes'),
            Str::make('ifDescr', 'ifDescr'),
            Str::make('ifName', 'ifName'),
            Str::make('portName', 'portName'),
            Number::make('ifIndex', 'ifIndex'),
            Number::make('ifSpeed', 'ifSpeed'),
            Number::make('ifSpeed_prev'),
            Str::make('ifConnectorPresent'),
            Str::make('ifOperStatus'),
            Str::make('ifOperStatus_prev'),
            Str::make('ifAdminStatus'),
            Str::make('ifAdminStatus_prev'),
            Str::make('ifDuplex', 'ifDuplex'),
            Number::make('ifMtu', 'ifMtu'),
            Str::make('ifType', 'ifType'),
            Str::make('ifAlias', 'ifAlias'),
            Str::make('ifPhysAddress'),
            Number::make('ifLastChange'),
            Str::make('ifVlan', 'ifVlan'),
            Str::make('ifTrunk', 'ifTrunk'),
            Number::make('ifVrf', 'ifVrf'),
            Boolean::make('ignore'),
            Boolean::make('disabled'),
            Boolean::make('deleted'),
            Str::make('pagpOperationMode'),
            Str::make('pagpPortState'),
            Str::make('pagpPartnerDeviceId'),
            Str::make('pagpPartnerLearnMethod'),
            Number::make('pagpPartnerIfIndex'),
            Number::make('pagpPartnerGroupIfIndex'),
            Str::make('pagpPartnerDeviceName'),
            Str::make('pagpEthcOperationMode'),
            Str::make('pagpDeviceId'),
            Number::make('pagpGroupIfIndex'),
            Number::make('ifInUcastPkts'),
            Number::make('ifInUcastPkts_prev'),
            Number::make('ifInUcastPkts_delta'),
            Number::make('ifInUcastPkts_rate'),
            Number::make('ifOutUcastPkts'),
            Number::make('ifOutUcastPkts_prev'),
            Number::make('ifOutUcastPkts_delta'),
            Number::make('ifOutUcastPkts_rate'),
            Number::make('ifInErrors'),
            Number::make('ifInErrors_prev'),
            Number::make('ifInErrors_delta'),
            Number::make('ifInErrors_rate'),
            Number::make('ifOutErrors'),
            Number::make('ifOutErrors_prev'),
            Number::make('ifOutErrors_delta'),
            Number::make('ifOutErrors_rate'),
            Number::make('ifInOctets'),
            Number::make('ifInOctets_prev'),
            Number::make('ifInOctets_delta'),
            Number::make('ifInOctets_rate'),
            Number::make('ifOutOctets'),
            Number::make('ifOutOctets_prev'),
            Number::make('ifOutOctets_delta'),
            Number::make('ifOutOctets_rate'),
            Number::make('poll_time'),
            Number::make('poll_prev'),
            Number::make('poll_period'),

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
