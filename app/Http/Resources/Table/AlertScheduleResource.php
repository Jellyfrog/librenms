<?php

namespace App\Http\Resources\Table;

use App\Models\AlertSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alert schedule (maintenance) row, status: 0 set, 1 lapsed, 2 current
 *
 * @property AlertSchedule $resource
 */
class AlertScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        return [
            'title' => $model->title,
            'notes' => $model->notes,
            'behavior' => $model->behavior->descr(),
            'id' => $model->schedule_id,
            'start' => $model->recurring ? '' : $model->start->toDateTimeString('minutes'),
            'end' => $model->recurring ? '' : $model->end->toDateTimeString('minutes'),
            'start_recurring_dt' => $model->recurring ? $model->start_recurring_dt : '',
            'start_recurring_hr' => $model->recurring ? $model->start_recurring_hr : '',
            'end_recurring_dt' => $model->recurring ? $model->end_recurring_dt : '',
            'end_recurring_hr' => $model->recurring ? $model->end_recurring_hr : '',
            'recurring' => $model->recurring ? __('Yes') : __('No'),
            'recurring_day' => $model->recurring ? implode(',', $model->recurring_day) : '',
            'status' => $model->status,
        ];
    }
}
