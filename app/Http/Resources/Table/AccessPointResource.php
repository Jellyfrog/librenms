<?php

namespace App\Http\Resources\Table;

use App\Models\AccessPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * @property AccessPoint $resource
 */
class AccessPointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        $detailUrl = route('device.accesspoints.show', [
            'device' => $model->device_id,
            'accessPoint' => $model->accesspoint_id,
        ]);
        $escapedUrl = htmlspecialchars($detailUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name = htmlspecialchars((string) $model->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mac = htmlspecialchars((string) $model->mac_addr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $type = htmlspecialchars((string) $model->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $baseGraph = [
            'id' => $model->accesspoint_id,
            'from' => '-1d',
            'width' => 100,
            'height' => 20,
            'legend' => 'no',
        ];

        $graphTitle = strip_tags((string) $model->name) . ' radio ' . (int) $model->radio_number;
        $clientsGraph = Url::graphPopup([
            ...$baseGraph,
            'type' => 'accesspoints_numasoclients',
            'popup_title' => htmlspecialchars($graphTitle . ': Associated Clients', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ], link: $detailUrl);
        $utilizationGraph = Url::graphPopup([
            ...$baseGraph,
            'type' => 'accesspoints_radioutil',
            'popup_title' => htmlspecialchars($graphTitle . ': Radio Utilization', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ], link: $detailUrl);
        $interferenceGraph = Url::graphPopup([
            ...$baseGraph,
            'type' => 'accesspoints_interference',
            'popup_title' => htmlspecialchars($graphTitle . ': Interference Index', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ], link: $detailUrl);

        return [
            'id' => (int) $model->accesspoint_id,
            'name' => '<a href="' . $escapedUrl . '">' . $name . '</a><br /><span class="text-muted">' . $mac . '</span>',
            'radio' => $type . ' (' . (int) $model->radio_number . ')',
            'channel' => (int) $model->channel,
            'numasoclients' => (int) $model->numasoclients,
            'radioutil' => (int) $model->radioutil,
            'interference' => (int) $model->interference,
            'txpow' => (int) $model->txpow,
            'trends' => '<div class="tw:flex tw:flex-col tw:gap-1">'
                . $clientsGraph
                . $utilizationGraph
                . $interferenceGraph
                . '</div>',
        ];
    }
}
