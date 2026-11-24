<?php

namespace App\Http\Resources\Table;

use App\Models\Port;
use App\Models\PortGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Enum\IfOperStatus;
use LibreNMS\Util\Url;

/**
 * Port row of the device port settings table.
 * The device context is shared by every row: PortSettingsResource::collection($paginator->through(fn ($port) => new PortSettingsResource($port, ...)))
 *
 * @mixin Port
 */
class PortSettingsResource extends JsonResource
{
    /**
     * @param  Port  $resource
     * @param  array<string, string>  $attribs  device attribs, they hold the per port overrides
     * @param  bool  $selected  selected port polling is enabled
     * @param  bool  $rrdTune  rrd tune is enabled for the device
     */
    public function __construct($resource, private readonly array $attribs = [], private readonly bool $selected = false, private readonly bool $rrdTune = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tune = $this->attribs['ifName_tune:' . $this->ifName] ?? null;
        $speedOverride = isset($this->attribs['ifSpeed:' . $this->ifName]);

        return [
            'port_id' => $this->port_id,
            'ifIndex' => $this->ifIndex,
            'label' => $this->getLabel(),
            'ifDescr' => $this->ifDescr,
            'url' => Url::portUrl($this->resource),
            'link_class' => Url::portLinkDisplayClass($this->resource),
            'ifAdminStatus' => $this->ifAdminStatus?->value,
            'ifOperStatus' => $this->ifOperStatus?->value,
            'disabled' => (bool) $this->disabled,
            'ignore' => (bool) $this->ignore,
            'deleted' => (bool) $this->deleted,
            'polling' => $this->pollingState(),
            'ifSpeed' => $this->ifSpeed,
            'ifSpeed_override' => $speedOverride,
            // the speed reported by the device is not stored while overridden
            'ifSpeed_device' => $speedOverride ? null : $this->ifSpeed,
            'circuit_speed' => $this->circuitSpeeds(),
            'circuit_speed_override' => isset($this->attribs['port_descr_speed:' . $this->ifName]),
            'ifAlias' => $this->ifAlias === 'repoll' ? '' : $this->ifAlias,
            'ifAlias_override' => isset($this->attribs['ifName:' . $this->ifName]),
            'rrd_tune' => $tune === null ? $this->rrdTune : $tune === 'true',
            'rrd_tune_override' => $tune !== null,
            'groups' => $this->groups->map(fn (PortGroup $group) => ['id' => $group->id, 'text' => $group->name])->values(),
        ];
    }

    /**
     * Mirrors how the ports poller decides which ports to poll, see EditPortsController::pollingQuery()
     */
    private function pollingState(): string
    {
        if ($this->deleted) {
            return 'deleted';
        }

        if ($this->disabled) {
            return 'disabled';
        }

        if ($this->selected) {
            if ($this->ifAdminStatus === IfOperStatus::Down) {
                return 'admin_down';
            }

            if ($this->ifOperStatus === IfOperStatus::Down || $this->ifOperStatus === IfOperStatus::LowerLayerDown) {
                return 'down';
            }
        }

        return 'polled';
    }
}
