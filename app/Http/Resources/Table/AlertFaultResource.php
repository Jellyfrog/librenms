<?php

namespace App\Http\Resources\Table;

use App\Http\Parsers\AlertLogDetailParser;
use App\Models\AlertFault;
use App\Models\AlertLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Enum\AlertState;
use LibreNMS\Enum\Severity;
use LibreNMS\Util\Html;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;

/**
 * Open alert (fault) row of x-alerts-table, the buttons are handled by resources/views/alerts/partials/table-scripts.blade.php
 *
 * @property AlertFault $resource
 */
class AlertFaultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        $state = (int) $model->state;
        $noteClass = empty($model->note) ? 'default' : 'warning';
        $location = $model->device?->location?->location;
        $entityCount = $this->entityCount($model);
        $ruleName = e((string) $model->rule?->name);
        if ($entityCount > 1) {
            $ruleName .= ' <span class="label label-default" title="' . $entityCount . ' matching entities grouped into this alert">' . $entityCount . '&times;</span>';
        }

        $alertLogId = AlertLog::query()->where('fault_id', $model->id)->max('id');

        return [
            'id' => $model->id,
            'rule' => '<i title="' . e(json_encode($model->rule?->builder)) . '"><a href="' . Url::generate(['page' => 'alert-rules']) . '">' . $ruleName . '</a></i>',
            'details' => '<a class="fa-solid fa-plus incident-toggle" data-toggle="collapse" data-target="#incident' . $model->id . '" data-parent="#alerts"></a>',
            'verbose_details' => $this->verboseDetailsButton($alertLogId),
            'hostname' => $this->renderHostname($model),
            'location' => '<a href="' . e(Url::generate(['page' => 'devices', 'location' => $location ?? ''])) . '">' . e($location ?? 'N/A') . '</a>',
            'timestamp' => $model->timestamp ? Time::format($model->timestamp, 'compact') : 'N/A',
            'severity' => $this->severityIcon($model->rule?->severity, $state),
            'state' => $state,
            'alert_id' => $model->id,
            'ack_ico' => $this->ackButton($model, $state),
            'proc' => $this->procButton($model->rule?->proc),
            'notes' => "<button type='button' class='btn btn-$noteClass fa fa-sticky-note-o command-alert-note' aria-label='Notes' id='alert-notes' data-alert_id='$model->id'></button>",
        ];
    }

    private function entityCount(AlertFault $fault): int
    {
        $count = AlertUtil::openEntityCountForRuleDevice((int) $fault->rule_id, (int) $fault->device_id);
        if ($fault->rule && AlertUtil::shouldNotifyPerEntity($fault->rule, $count)) {
            return 1;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function faultDetails(AlertFault $fault): array
    {
        $details = is_array($fault->details) ? $fault->details : [];

        $openCount = AlertUtil::openEntityCountForRuleDevice((int) $fault->rule_id, (int) $fault->device_id);
        if ($fault->rule && AlertUtil::shouldGroupFaultDetails($fault->rule, $openCount)) {
            $rows = [];
            $siblings = AlertFault::query()
                ->where('device_id', $fault->device_id)
                ->where('rule_id', $fault->rule_id)
                ->where('open', 1)
                ->where('state', '!=', AlertState::RECOVERED)
                ->get(['id', 'details']);
            foreach ($siblings as $sibling) {
                foreach ((array) ($sibling->details['rule'] ?? []) as $row) {
                    $rows[] = $row;
                }
            }
            if (! empty($rows)) {
                $details = ['rule' => $rows] + $details;
            }
        }

        return $details;
    }

    private function renderFaultDetail(AlertFault $fault): string
    {
        $details = $this->faultDetails($fault);
        if (empty($details)) {
            return '';
        }

        return view('alerts.fault-detail', [
            'details' => app(AlertLogDetailParser::class)->parse($details),
        ])->render();
    }

    private function renderHostname(AlertFault $fault): string
    {
        $faultDetail = $this->renderFaultDetail($fault);
        $collapseClass = $this->incidentCollapseClass($faultDetail);

        return '<div class="incident">'
            . Url::modernDeviceLink($fault->device)
            . '<div id="incident' . $fault->id . '"' . $collapseClass . '>' . $faultDetail . '</div>'
            . '</div>';
    }

    private function countPrintableCharacters(string $string): int
    {
        $string = trim(strip_tags($string));
        $string = preg_replace('/[[:^print:]]\s/u', '', $string);
        $string = preg_replace('/ +/', ' ', $string);

        return strlen($string);
    }

    private function incidentCollapseClass(string $detail): string
    {
        if (empty($detail)) {
            return '';
        }

        $uncollapseKeyCount = request()->input('uncollapse_key_count');
        $maxRowLength = $this->countPrintableCharacters($detail);

        return (is_numeric($uncollapseKeyCount) && $maxRowLength < (int) $uncollapseKeyCount)
            ? ''
            : ' class="collapse"';
    }

    private function severityIcon(?string $severity, int $state): string
    {
        $severity = $state === AlertState::ACKNOWLEDGED
            ? Severity::Notice
            : Severity::fromAlertRule($severity);

        $label = Html::severityToLabel($severity, '&nbsp;', class: 'alert-status');
        if ($state === AlertState::WORSE) {
            $label .= ' <strong>+</strong>';
        } elseif ($state === AlertState::BETTER) {
            $label .= ' <strong>-</strong>';
        }

        return $label;
    }

    private function ackButton(AlertFault $model, int $state): string
    {
        if (! Gate::allows('alert.update')) {
            return '';
        }

        $info = is_array($model->info) ? $model->info : [];
        $btnBase = "type=\"button\" data-target=\"ack-alert\" data-state=\"$state\" data-alert_id=\"{$model->id}\" data-alert_state=\"$state\" name=\"ack-alert\"";

        if ($state !== AlertState::ACKNOWLEDGED) {
            return "<button $btnBase class=\"btn btn-danger command-ack-alert fa fa-eye\" aria-hidden=\"true\" title=\"Mark as acknowledged\"></button>";
        }

        $icon = ($info['until_clear'] ?? true) === false ? 'fa-eye' : 'fa-eye-slash';

        return "<button $btnBase class=\"btn btn-primary command-ack-alert fa $icon\" aria-hidden=\"true\" title=\"Mark as not acknowledged\"></button>";
    }

    private function procButton(?string $proc): string
    {
        if (! $proc || $proc === 'NULL' || ! preg_match('#^https?://#', $proc)) {
            return '';
        }

        return '<a href="' . e($proc) . '" target="_blank"><button type="button" class="btn btn-info fa fa-external-link" aria-hidden="true"></button></a>';
    }

    private function verboseDetailsButton(?int $alertLogId): string
    {
        if (! Gate::allows('alert.detail')) {
            return '';
        }

        return "<button type='button' class='btn btn-alert-details command-alert-details' aria-label='Details' id='alert-details' data-alert_log_id='$alertLogId'><i class='fa-solid fa-circle-info'></i></button>";
    }
}
