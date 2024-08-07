<?php

namespace App\Http\Controllers\Api\V1;

use App\JsonApi\V1\Ports\PortSchema;
use App\Models\Port;
use Illuminate\Http\Request;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LibreNMS\Util\Graph;

class PortController extends JsonApiController
{
    public function graph(Request $request, PortSchema $schema, Port $port)
    {
        $vars = [
            'port' => $port->ifName,
            'type' => $request->get('type'),
            'id' => $port->port_id,
        ];

        //dd($port);

        $graph = Graph::get([
            'width' => $request->get('width', 1075),
            'height' => $request->get('height', 300),
            ...$vars,
        ]);

        return response($graph->data, 200, ['Content-Type' => $graph->contentType()]);
    }
}
