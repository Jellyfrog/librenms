<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Port;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Rrd;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class RrdController extends Controller
{
    /**
     * Return raw RRD time-series data for a port.
     */
    public function port(Request $request, Port $port): JsonResponse
    {
        $this->authorize('view', $port);

        $rrdFile = Rrd::name($port->device->hostname, Rrd::portName($port->port_id));

        return $this->exportRrd($request, $rrdFile, [
            'INOCTETS' => 'in_octets',
            'OUTOCTETS' => 'out_octets',
            'INERRORS' => 'in_errors',
            'OUTERRORS' => 'out_errors',
            'INUCASTPKTS' => 'in_ucast_pkts',
            'OUTUCASTPKTS' => 'out_ucast_pkts',
        ]);
    }

    /**
     * Return raw RRD time-series data for a device.
     *
     * Accepts a `type` query parameter to select which RRD file to export.
     * Defaults to "uptime".
     */
    public function device(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $type = $request->input('type', 'uptime');

        $typeMap = [
            'uptime' => [
                'file' => 'uptime',
                'ds' => ['uptime' => 'uptime'],
            ],
            'ping' => [
                'file' => 'ping-perf',
                'ds' => ['ping' => 'ping'],
            ],
            'poller' => [
                'file' => 'poller-perf',
                'ds' => ['poller' => 'poller'],
            ],
        ];

        if (! isset($typeMap[$type])) {
            return response()->json([
                'error' => 'Unknown RRD type.',
                'available_types' => array_keys($typeMap),
            ], 400);
        }

        $config = $typeMap[$type];
        $rrdFile = Rrd::name($device->hostname, $config['file']);

        return $this->exportRrd($request, $rrdFile, $config['ds']);
    }

    /**
     * Run rrdtool xport to extract raw data from an RRD file.
     *
     * @param  array<string, string>  $dataSources  Map of RRD DS name => output label
     */
    private function exportRrd(Request $request, string $rrdFile, array $dataSources): JsonResponse
    {
        $from = $this->validateTimeSpec($request->input('from', '-1d'));
        $to = $this->validateTimeSpec($request->input('to', 'now'));
        $step = $request->input('step');
        $cf = strtoupper($request->input('cf', 'AVERAGE'));

        if ($from === null || $to === null) {
            return response()->json(['error' => 'Invalid time specification. Use relative (e.g. -1d, -6h) or epoch timestamps.'], 400);
        }

        if (! in_array($cf, ['AVERAGE', 'MIN', 'MAX', 'LAST'])) {
            return response()->json(['error' => 'Invalid consolidation function. Use: AVERAGE, MIN, MAX, LAST'], 400);
        }

        // Build xport options — use Rrd::buildCommand to handle rrdcached daemon flag
        $options = [
            '--start', $from,
            '--end', $to,
            '--json',
        ];

        if ($step) {
            $options[] = '--step';
            $options[] = (string) max(1, (int) $step);
        }

        foreach ($dataSources as $dsName => $label) {
            $options[] = "DEF:{$label}={$rrdFile}:{$dsName}:{$cf}";
            $options[] = "XPORT:{$label}:{$label}";
        }

        // Use buildCommand to get proper rrdcached --daemon flag injected
        $cmd = Rrd::buildCommand('xport', '', $options);

        $process = new Process($cmd);
        $process->setTimeout(30);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            return response()->json(['error' => 'RRD export timed out.'], 504);
        }

        if (! $process->isSuccessful()) {
            $stderr = $process->getErrorOutput();
            $status = str_contains($stderr, 'No such file') ? 404 : 500;
            $error = $status === 404 ? 'RRD file not found.' : 'RRD export failed.';

            return response()->json(['error' => $error], $status);
        }

        $output = $process->getOutput();

        $data = json_decode($output, true);
        if ($data === null) {
            // rrdtool's JSON output sometimes has trailing commas — fix and retry
            $output = preg_replace('/,\s*([\]}])/', '$1', $output);
            $data = json_decode($output, true);
        }

        if ($data === null) {
            return response()->json(['error' => 'Failed to parse RRD output.'], 500);
        }

        return response()->json($data);
    }

    /**
     * Validate an rrdtool time specification.
     * Allows relative times (-1d, -6h, -1w), epoch timestamps, and "now".
     */
    private function validateTimeSpec(string $value): ?string
    {
        $value = trim($value);

        // "now", "end", "start" keywords
        if (preg_match('/^(now|end|start)$/i', $value)) {
            return $value;
        }

        // Relative time: -1d, -6h, -1w, -30m, etc.
        if (preg_match('/^-\d+[smhdwMy]$/', $value)) {
            return $value;
        }

        // Epoch timestamp (10-13 digits)
        if (preg_match('/^\d{10,13}$/', $value)) {
            return $value;
        }

        return null;
    }
}
