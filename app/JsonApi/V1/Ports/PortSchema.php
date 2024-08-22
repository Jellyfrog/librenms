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

            Number::make('deviceId'),
            Str::make('descrType', 'port_descr_type'),
            Str::make('descrDescr', 'port_descr_descr'),
            Str::make('descrCircuit', 'port_descr_circuit'),
            Str::make('descrSpeed', 'port_descr_speed'),
            Str::make('descrNotes', 'port_descr_notes'),
            Str::make('ifDescr', 'ifDescr'),
            Str::make('ifName', 'ifName'),
            Str::make('name', 'portName'),
            Number::make('ifIndex', 'ifIndex'),
            Number::make('ifSpeed', 'ifSpeed'),
            Number::make('ifSpeedPrev', 'ifSpeed_prev'),
            Str::make('ifConnectorPresent', 'ifConnectorPresent'),
            Str::make('ifOperStatus', 'ifOperStatus'),
            Str::make('ifOperStatusPrev', 'ifOperStatus_prev'),
            Str::make('ifAdminStatus', 'ifAdminStatus'),
            Str::make('ifAdminStatusPrev', 'ifAdminStatus_prev'),
            Str::make('ifDuplex', 'ifDuplex'),
            Number::make('ifMtu', 'ifMtu'),
            Str::make('ifType', 'ifType'),
            Str::make('ifAlias', 'ifAlias'),
            Str::make('ifPhysAddress', 'ifPhysAddress'),
            Number::make('ifLastChange', 'ifLastChange'),
            Str::make('ifVlan', 'ifVlan'),
            Str::make('ifTrunk', 'ifTrunk'),
            Number::make('ifVrf', 'ifVrf'),
            Boolean::make('ignore'),
            Boolean::make('disabled'),
            Boolean::make('deleted'),
            Str::make('pagpOperationMode', 'pagpOperationMode'),
            Str::make('pagpPortState', 'pagpPortState'),
            Str::make('pagpPartnerDeviceId', 'pagpPartnerDeviceId'),
            Str::make('pagpPartnerLearnMethod', 'pagpPartnerLearnMethod'),
            Number::make('pagpPartnerIfIndex', 'pagpPartnerIfIndex'),
            Number::make('pagpPartnerGroupIfIndex', 'pagpPartnerGroupIfIndex'),
            Str::make('pagpPartnerDeviceName', 'pagpPartnerDeviceName'),
            Str::make('pagpEthcOperationMode', 'pagpEthcOperationMode'),
            Str::make('pagpDeviceId', 'pagpDeviceId'),
            Number::make('pagpGroupIfIndex', 'pagpGroupIfIndex'),
            Number::make('ifInUcastPkts', 'ifInUcastPkts'),
            Number::make('ifInUcastPktsPrev', 'ifInUcastPkts_prev'),
            Number::make('ifInUcastPktsDelta', 'ifInUcastPkts_delta'),
            Number::make('ifInUcastPktsRate', 'ifInUcastPkts_rate'),
            Number::make('ifOutUcastPkts', 'ifOutUcastPkts'),
            Number::make('ifOutUcastPktsPrev', 'ifOutUcastPkts_prev'),
            Number::make('ifOutUcastPktsDelta', 'ifOutUcastPkts_delta'),
            Number::make('ifOutUcastPktsRate', 'ifOutUcastPkts_rate'),
            Number::make('ifInErrors', 'ifInErrors'),
            Number::make('ifInErrorsPrev', 'ifInErrors_prev'),
            Number::make('ifInErrorsDelta', 'ifInErrors_delta'),
            Number::make('ifInErrorsRate', 'ifInErrors_rate'),
            Number::make('ifOutErrors', 'ifOutErrors'),
            Number::make('ifOutErrorsPrev', 'ifOutErrors_prev'),
            Number::make('ifOutErrorsDelta', 'ifOutErrors_delta'),
            Number::make('ifOutErrorsRate', 'ifOutErrors_rate'),
            Number::make('ifInOctets', 'ifInOctets'),
            Number::make('ifInOctetsPrev', 'ifInOctets_prev'),
            Number::make('ifInOctetsDelta', 'ifInOctets_delta'),
            Number::make('ifInOctetsRate', 'ifInOctets_rate'),
            Number::make('ifOutOctets', 'ifOutOctets'),
            Number::make('ifOutOctetsPrev', 'ifOutOctets_prev'),
            Number::make('ifOutOctetsDelta', 'ifOutOctets_delta'),
            Number::make('ifOutOctetsRate', 'ifOutOctets_rate'),
            Number::make('pollTime'),
            Number::make('pollPrev'),
            Number::make('pollPeriod'),

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
