<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class MaintenanceJobRun extends Model
{
    use MassPrunable;

    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public $timestamps = false;
    protected $fillable = [
        'job',
        'poller_name',
        'queued_at',
        'started_at',
        'finished_at',
        'duration_ms',
        'status',
        'exception',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::where('started_at', '<', now()->subDays(30));
    }
}
