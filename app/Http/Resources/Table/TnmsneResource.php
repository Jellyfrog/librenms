<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Coriant TNMS network element, the *_class fields are label classes for the state cells
 *
 * @mixin \App\Models\TnmsneInfo
 */
class TnmsneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'neName' => $this->neName,
            'neLocation' => $this->neLocation,
            'neType' => $this->neType,
            'neOpMode' => $this->neOpMode,
            'neOpMode_class' => $this->neOpMode == 'operation' ? 'label-success' : 'label-danger',
            'neAlarm' => $this->neAlarm,
            'neAlarm_class' => match ($this->neAlarm) {
                'cleared' => 'label-success',
                'warning' => 'label-warning',
                'minor', 'major', 'critical', 'indeterminate' => 'label-danger',
                default => 'label-default',
            },
            'neOpState' => $this->neOpState,
            'neOpState_class' => $this->neOpState == 'enabled' ? 'label-success' : 'label-danger',
        ];
    }
}
