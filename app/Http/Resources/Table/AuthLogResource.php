<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AuthLog
 */
class AuthLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'datetime' => $this->datetime?->format('Y-m-d H:i:s'),
            'user' => $this->user,
            'address' => $this->address,
            'result' => $this->result,
        ];
    }
}
