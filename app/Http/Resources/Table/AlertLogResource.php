<?php

namespace App\Http\Resources\Table;

use App\Http\Parsers\AlertLogDetailParser;
use App\Models\AlertLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Util\Html;
use LibreNMS\Util\Url;

/**
 * Alert log row of x-alert-log-table, the buttons are handled by resources/views/alerts/partials/table-scripts.blade.php
 *
 * @property AlertLog $resource
 */
class AlertLogResource extends JsonResource
{
    /** Max entity rows rendered in the (collapsed) inline detail cell to bound memory. */
    private const int INLINE_DETAIL_ROW_LIMIT = 100;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        $details = is_array($model->details) ? $model->details : [];
        $entity_count = 1;

        if ($model->fault_id && $model->rule) {
            // Count siblings without loading/decompressing their details blobs.
            $sibling_query = fn () => AlertLog::query()
                ->where('device_id', $model->device_id)
                ->where('rule_id', $model->rule_id)
                ->where('state', $model->state->value)
                ->where('time_logged', $model->time_logged)
                ->whereNotNull('fault_id');

            $sibling_count = $sibling_query()->count();

            if ($sibling_count > 1 && AlertUtil::shouldGroupFaultDetails($model->rule, $sibling_count)) {
                $entity_count = $sibling_count;

                // Only now (when grouping) load the detail blobs, chunked and stopping once
                // we have enough rows to fill the inline display cap.
                $rows = [];
                $sibling_query()
                    ->orderBy('id')
                    ->select(['id', 'details'])
                    ->chunk(200, function ($chunk) use (&$rows): bool {
                        foreach ($chunk as $sibling) {
                            foreach ((array) ($sibling->details['rule'] ?? []) as $row) {
                                $rows[] = $row;
                                if (count($rows) >= self::INLINE_DETAIL_ROW_LIMIT) {
                                    return false; // stop loading, we have enough to display
                                }
                            }
                        }

                        return true;
                    });
                $details = ['rule' => $rows] + $details;
            }
        }

        // Bound the rows handed to the parser so a single very large alert cannot exhaust
        // memory while rendering the collapsed inline detail. The full entity count is still
        // shown in the label, and complete details remain available via the details endpoint.
        if (isset($details['rule']) && is_array($details['rule']) && count($details['rule']) > self::INLINE_DETAIL_ROW_LIMIT) {
            $details['rule'] = array_slice($details['rule'], 0, self::INLINE_DETAIL_ROW_LIMIT);
        }

        $fault_detail = view('alerts.fault-detail', [
            'details' => app(AlertLogDetailParser::class)->parse($details),
        ])->render();

        $status = Html::severityToLabel($model->state->asSeverity(), title: $model->state->name, class: 'alert-status');

        $alert_rule = e($model->rule?->name);
        if ($entity_count > 1) {
            $alert_rule .= ' <span class="label label-default">' . $entity_count . '&times;</span>';
        }

        return [
            'id' => $model->id,
            'time_logged' => $model->time_logged?->toIso8601String(),
            'details' => '<a class="fa fa-plus incident-toggle" data-toggle="collapse" data-target="#incident' . $model->id . '" data-parent="#alerts"></a>',
            'verbose_details' => Gate::allows('alert.detail') ? "<button type='button' class='btn btn-alert-details command-alert-details' aria-label='Details' data-alert_log_id='$model->id'><i class='fa-solid fa-circle-info'></i></button>" : '',
            'hostname' => '<div class="incident">' . Url::modernDeviceLink($model->device) . '<div id="incident' . $model->id . '" class="collapse">' . $fault_detail . '</div></div>',
            'alert_rule' => $alert_rule,
            'status' => $status,
            'severity' => $model->rule?->severity,
        ];
    }
}
