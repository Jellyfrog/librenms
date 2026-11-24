<?php

namespace App\Http\Resources\Table;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Billing;
use LibreNMS\Util\Color;
use LibreNMS\Util\Html;
use LibreNMS\Util\Number;

/**
 * Bill row, period=prev shows the last finished billing period from bill_history
 *
 * @property Bill $resource
 */
class BillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bill = $this->resource;
        $previous = $request->input('period') === 'prev';
        $allowed = (float) $bill->getAttribute('bill_allowed');

        if ($previous) {
            $from = Carbon::parse($bill->getAttribute('bill_datefrom'));
            $to = Carbon::parse($bill->getAttribute('bill_dateto'));
            $percent = (float) $bill->getAttribute('bill_percent');
            $overuse = (float) $bill->getAttribute('bill_overuse');
            $predicted = '-';
        } else {
            ['from' => $from, 'to' => $to] = $bill->billingPeriod();
            $percent = Number::calculatePercent($bill->used(), $allowed);
            $overuse = $bill->used() - $allowed;
            $predicted = $bill->formatUsage(Billing::getPredictedUsage($bill->bill_day, $bill->used()));
        }

        $colors = Color::percentage($percent);
        $total = Billing::formatBytes($bill->total_data);
        $rate95th = Number::formatSi($bill->rate_95th, 2, 0, 'bps');

        $actions = '';
        if (! $previous && Gate::allows('update', $bill)) {
            $actions = '<a href="' . route('bill.edit', $bill->bill_id) . '"><i class="fa fa-pencil fa-lg icon-theme" aria-hidden="true"></i> ' . __('Edit') . '</a>';
        }

        return [
            'id' => $bill->bill_id,
            'bill_name' => '<a href="' . route('bill.show', $bill->bill_id) . '" class="tw:font-bold">' . e($bill->bill_name) . '</a><br />'
                . $from->format('Y-m-d') . ' ' . __('to') . ' ' . $to->format('Y-m-d'),
            'notes' => e($bill->bill_notes),
            'bill_type' => $bill->isCdr() ? 'CDR' : 'Quota',
            'bill_allowed' => $bill->formatUsage($allowed),
            'total_data_in' => $bill->isCdr() ? $bill->formatUsage($bill->rate_95th_in) : $bill->formatUsage($bill->total_data_in),
            'total_data_out' => $bill->isCdr() ? $bill->formatUsage($bill->rate_95th_out) : $bill->formatUsage($bill->total_data_out),
            'total_data' => $bill->isCdr() ? $total : "<b>$total</b>",
            'rate_95th' => $bill->isCdr() ? "<b>$rate95th</b>" : $rate95th,
            'overusage' => $overuse > 0 ? '<span style="color: #' . $colors['left'] . '; font-weight: bold;">' . $bill->formatUsage($overuse) . '</span>' : '-',
            'predicted' => $predicted,
            'graph' => Html::percentageBar(250, 10, $percent, null, $percent . '%', null, null, ['left' => $colors['left'], 'right' => $colors['right']]),
            'actions' => $actions,
        ];
    }
}
