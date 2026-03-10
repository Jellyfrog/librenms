<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LibreNMS\Enum\ImageFormat;
use LibreNMS\Exceptions\RrdGraphException;
use LibreNMS\Util\Graph;

class GraphController extends Controller
{
    /**
     * Return a graph image for a port.
     */
    public function port(Request $request, Port $port): Response
    {
        $this->authorize('view', $port);

        $type = $request->input('type', 'port_bits');

        return $this->renderGraph($request, [
            'id' => $port->port_id,
            'type' => $type,
        ]);
    }

    /**
     * Return a graph image for a device.
     */
    public function device(Request $request, Device $device): Response
    {
        $this->authorize('view', $device);

        $type = $request->input('type', 'device_uptime');

        return $this->renderGraph($request, [
            'device' => $device->device_id,
            'type' => $type,
        ]);
    }

    private function renderGraph(Request $request, array $vars): Response
    {
        $vars = array_merge($vars, $request->only([
            'from', 'to', 'legend', 'title', 'absolute',
            'nototal', 'nodetails', 'noagg', 'inverse', 'previous',
        ]));

        $vars['width'] = $request->input('width', 1075);
        $vars['height'] = $request->input('height', 300);

        if (($graphType = $request->input('graph_type')) !== null) {
            $vars['graph_type'] = $graphType;
        }

        try {
            $graph = Graph::get($vars);

            if ($request->input('output') === 'base64') {
                return response($graph->base64(), 200, [
                    'Content-Type' => 'text/plain',
                ]);
            }

            return response($graph->data, 200, [
                'Content-Type' => $graph->contentType(),
            ]);
        } catch (RrdGraphException $e) {
            return response($e->generateErrorImage(), 500, [
                'Content-Type' => ImageFormat::forGraph($vars['graph_type'] ?? null)->contentType(),
            ]);
        }
    }
}
