<?php

namespace App\Http\Resources\Table;

use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\Html;

/**
 * A customer: the ports with this customer in their description, and the customer traffic graphs
 *
 * @property array{name: string, ports: Collection<int, Port>} $resource
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->resource['name'];

        return [
            'id' => $name,
            'port_descr_descr' => $name,
            'ports' => $this->resource['ports']->map(fn (Port $port) => [
                'id' => $port->port_id,
                'hostname' => Blade::render('<x-device-link :device="$device"/>', ['device' => $port->device]),
                'ifDescr' => Blade::render('<x-port-link :port="$port"/>', ['port' => $port]),
                'speed' => $port->port_descr_speed,
                'circuit' => $port->port_descr_circuit,
                'notes' => $port->port_descr_notes,
            ])->values(),
            'graphs' => Html::graphRow(['type' => 'customer_bits', 'height' => 100, 'width' => 220, 'customer' => $name]),
        ];
    }
}
