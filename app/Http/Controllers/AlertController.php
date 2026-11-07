<?php

namespace App\Http\Controllers;

use App\Facades\LibrenmsConfig;
use App\Models\Alert;
use App\Models\AlertFault;
use App\Models\Eventlog;
use Illuminate\Http\Request;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Enum\AlertState;
use LibreNMS\Enum\Severity;

class AlertController extends Controller
{
    public function ack(Request $request, AlertFault $fault): \Illuminate\Http\JsonResponse
    {
        $alert = Alert::query()->where('rule_id', $fault->rule_id)->where('device_id', $fault->device_id)->first();
        if ($alert) {
            $this->authorize('update', $alert);
        }

        $this->validate($request, [
            'state' => 'nullable|int', // legacy client hint; the stored fault state decides
            'ack_msg' => 'nullable|string',
            'ack_until_clear' => 'nullable|in:0,1,true,false',
        ]);

        // Decide from the stored fault state, not the client-supplied one, so a stale page
        // cannot (un)ack a fault that has since recovered or changed state.
        $state_description = '';
        $newState = null;
        if ($fault->canUnacknowledge()) {
            $newState = AlertState::ACTIVE;
            $state_description = 'UnAck';
        } elseif ($fault->canAcknowledge()) {
            $newState = AlertState::ACKNOWLEDGED;
            $state_description = 'Ack';
        }

        if ($newState === null) {
            return response()->json([
                'message' => 'Fault has not been acknowledged.',
                'status' => 'error',
            ]);
        }

        $untilClear = filter_var($request->input('ack_until_clear'), FILTER_VALIDATE_BOOLEAN);
        $timestamp = date(LibrenmsConfig::get('dateformat.long'));
        $username = $request->user()->username;
        $ack_msg = $request->input('ack_msg');
        $note_suffix = "$timestamp - $state_description ($username) " . $ack_msg;

        $fault->loadMissing('rule');
        $targets = $fault->acknowledgementTargets($newState === AlertState::ACKNOWLEDGED);

        $saved = false;
        foreach ($targets as $target) {
            $target->state = $newState;
            $target->open = 1;
            $info = $target->info ?: [];
            $info['until_clear'] = $untilClear;
            $target->info = $info;
            $target->note = trim($target->note . PHP_EOL . $note_suffix);
            $saved = $target->save() || $saved;
        }

        if ($saved) {
            $fault->loadMissing('rule');
            if ($fault->rule) {
                (new AlertRules($fault->device_id))->syncAlertState($fault->rule);
            }

            $rule_name = $fault->rule?->name;
            $act = strtolower($state_description) . 'nowledged';
            Eventlog::log("$username {$act} alert $rule_name note: $ack_msg", $fault->device_id, 'alert', Severity::Info, $fault->id);

            return response()->json([
                'message' => "Fault {$state_description}nowledged.",
                'status' => 'ok',
            ]);
        }

        return response()->json([
            'message' => 'Fault has not been acknowledged.',
            'status' => 'error',
        ]);
    }
}
