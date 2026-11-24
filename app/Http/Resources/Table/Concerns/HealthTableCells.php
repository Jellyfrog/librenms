<?php

namespace App\Http\Resources\Table\Concerns;

use App\Models\Device;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\Html;

/**
 * Shared cells of the health tables (sensors, processors, storage...)
 */
trait HealthTableCells
{
    protected function deviceLink(?Device $device): string
    {
        return Blade::render('<x-device-link :device="$device" />', ['device' => $device]);
    }

    /**
     * The graphs view shows a row of four graphs under the cells: each cell gets one graph below it
     *
     * @param  array<string, string>  $cells  four cells in column order
     * @param  array<string, mixed>  $graph  graph array for Html::graphRow()
     * @return array<string, string>
     */
    protected function withGraphRow(array $cells, array $graph, int $width = 216): array
    {
        $row = Html::graphRow(array_replace($graph, ['height' => 100, 'width' => $width]));
        $index = 0;

        foreach ($cells as $key => $cell) {
            $style = $index === 0 ? "width:{$width}px;margin-left:auto" : "width:{$width}px";
            $cells[$key] = '<div class="tw:border-b tw:border-gray-200" style="min-height:20px">' . $cell . '</div><div style="' . $style . '">' . ($row[$index] ?? '') . '</div>';
            $index++;
        }

        return $cells;
    }
}
