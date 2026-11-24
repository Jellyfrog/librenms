<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\IP;
use LibreNMS\Util\Mac;

/**
 * Forwarding database entry. DNS names are only looked up while the DNS column is shown.
 * Expects port.fdb_entries_count (withCount) to mark the likely endpoint port.
 *
 * @mixin \App\Models\PortsFdb
 */
class FdbResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mac = Mac::parse($this->mac_address);
        $ips = $this->ipv4Addresses->pluck('ipv4_address')->unique();
        $port = $this->port;

        $interface = '';
        if ($port) {
            $interface = Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link>', ['port' => $port]);
            if ($port->ifInErrors_delta > 0 || $port->ifOutErrors_delta > 0) {
                $interface .= Blade::render(' <x-port-link :port="$port"><i class="fa fa-flag fa-lg" style="color:red" aria-hidden="true"></i></x-port-link>', ['port' => $port]);
            }
            if ($port->fdb_entries_count == 1) {
                // only one mac on this port, likely the endpoint
                $interface .= ' <i class="fa fa-star fa-lg" style="color:green" aria-hidden="true" title="' . e(__('This indicates the most likely endpoint switchport')) . '"></i>';
            }
        }

        return [
            'id' => $this->ports_fdb_id,
            'device' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'mac_address' => $mac->readable(),
            'mac_oui' => $mac->vendor(),
            'ipv4_address' => $ips->implode(', '),
            'interface' => $interface,
            'vlan' => $this->vlan ?? '',
            'description' => $port?->ifAlias ?? '',
            'dnsname' => in_array('dnsname', $request->array('columns'), true) ? $this->resolveDns($ips) : 'N/A',
            'first_seen' => $this->created_at?->toDateTimeString() ?? __('unknown'),
            'last_seen' => $this->updated_at?->diffForHumans() ?? __('unknown'),
        ];
    }

    /**
     * @param  Collection<int, string>  $ips
     */
    private function resolveDns(Collection $ips): string
    {
        // don't try too many dns queries, this is the slowest part
        foreach ($ips->take(3) as $ip) {
            $hostname = gethostbyaddr($ip);
            if (! IP::isValid($hostname)) {
                return $hostname;
            }
        }

        return 'N/A';
    }
}
