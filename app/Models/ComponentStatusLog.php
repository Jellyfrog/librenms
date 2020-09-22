<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ComponentStatusLog
 *
 * @property int $id ID for each log entry, unique index
 * @property int $component_id id from the component table
 * @property int $status The status that the component was changed TO
 * @property string|null $message
 * @property string $timestamp When the status of the component was changed
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog whereComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentStatusLog whereTimestamp($value)
 * @mixin \Eloquent
 */
class ComponentStatusLog extends Model
{
    public $timestamps = false;
    protected $table = 'component_statuslog';
    protected $fillable = ['component_id', 'status', 'message'];

    // ---- Accessors/Mutators ----

    public function setStatusAttribute($status)
    {
        $this->attributes['status'] = (int) $status;
    }
}
