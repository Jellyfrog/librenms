<?php

namespace App\Http\Controllers\Api\V2;

use App\Facades\Permissions;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Port;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LibreNMS\Data\Store\Rrd;
use LibreNMS\Enum\ImageFormat;
use LibreNMS\Exceptions\RrdGraphException;
use LibreNMS\Util\Graph;

class RrdController extends Controller
{
    /**
     * Generate an RRD graph image.
     *
     * Returns a PNG/SVG graph image for the given parameters.
     */
    public function graph(Request $request): Response|JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'nullable|integer',
            'device_id' => 'nullable|integer',
            'width' => 'nullable|integer|min:100|max:4000',
            'height' => 'nullable|integer|min:50|max:2000',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'format' => 'nullable|in:png,svg',
            'output' => 'nullable|in:image,base64',
        ]);

        $deviceId = $request->input('device_id');
        if ($deviceId && ! $this->canAccessDevice($deviceId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $vars = $request->only([
            'type', 'id', 'from', 'to', 'legend', 'title', 'absolute',
            'font', 'bg', 'bbg', 'graph_title', 'nototal', 'nodetails',
            'noagg', 'inverse', 'previous', 'duration',
        ]);

        $vars['width'] = $request->input('width', 1075);
        $vars['height'] = $request->input('height', 300);

        if ($request->input('format') === 'svg') {
            $vars['graph_type'] = 'svg';
        }

        try {
            $graph = Graph::get($vars);

            if ($request->input('output') === 'base64') {
                return response()->json([
                    'image' => $graph->base64(),
                    'content_type' => $graph->contentType(),
                ]);
            }

            return response($graph->data, 200, [
                'Content-Type' => $graph->contentType(),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (RrdGraphException $e) {
            return response()->json([
                'message' => 'Graph generation failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch raw RRD data as JSON.
     *
     * Returns time-series data points from an RRD file.
     */
    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|integer|exists:devices,device_id',
            'filename' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'cf' => 'nullable|in:AVERAGE,MIN,MAX,LAST',
            'resolution' => 'nullable|integer|min:1',
        ]);

        $deviceId = $request->input('device_id');
        if (! $this->canAccessDevice($deviceId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $device = Device::findOrFail($deviceId);

        $rrd = new Rrd();
        $rrdDir = config('librenms.rrd_dir', base_path('rrd'));
        $filename = $rrdDir . '/' . $device->hostname . '/' . $request->input('filename');

        if (! str_ends_with($filename, '.rrd')) {
            $filename .= '.rrd';
        }

        // Sanitize - ensure the file is within the expected directory
        $realPath = realpath($filename);
        $realRrdDir = realpath($rrdDir);

        if ($realPath === false || ! str_starts_with($realPath, $realRrdDir)) {
            return response()->json(['message' => 'RRD file not found.'], 404);
        }

        $lastUpdate = $rrd->lastUpdate($filename);
        if ($lastUpdate === null) {
            return response()->json(['message' => 'No data available.'], 404);
        }

        return response()->json([
            'device_id' => $deviceId,
            'hostname' => $device->hostname,
            'filename' => $request->input('filename'),
            'last_update' => $lastUpdate->timestamp,
            'data' => $lastUpdate->data,
        ]);
    }

    /**
     * List available RRD files for a device.
     */
    public function files(Request $request, int $deviceId): JsonResponse
    {
        if (! $this->canAccessDevice($deviceId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $device = Device::findOrFail($deviceId);

        $rrdDir = config('librenms.rrd_dir', base_path('rrd'));
        $deviceDir = $rrdDir . '/' . $device->hostname;

        if (! is_dir($deviceDir)) {
            return response()->json([
                'device_id' => $deviceId,
                'hostname' => $device->hostname,
                'files' => [],
            ]);
        }

        $files = [];
        $iterator = new \DirectoryIterator($deviceDir);
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'rrd') {
                $files[] = [
                    'filename' => $file->getBasename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        usort($files, fn ($a, $b) => strcmp($a['filename'], $b['filename']));

        return response()->json([
            'device_id' => $deviceId,
            'hostname' => $device->hostname,
            'files' => $files,
            'count' => count($files),
        ]);
    }

    /**
     * List available graph types for a device.
     */
    public function graphTypes(Request $request, int $deviceId): JsonResponse
    {
        if (! $this->canAccessDevice($deviceId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $device = Device::findOrFail($deviceId);

        $graphs = $device->graphs()
            ->select('graph')
            ->distinct()
            ->pluck('graph')
            ->sort()
            ->values();

        return response()->json([
            'device_id' => $deviceId,
            'hostname' => $device->hostname,
            'graphs' => $graphs,
            'count' => $graphs->count(),
        ]);
    }

    private function canAccessDevice(int $deviceId): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->hasGlobalRead()) {
            return true;
        }

        return Permissions::canAccessDevice($deviceId, $user);
    }
}
