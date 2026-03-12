<?php

/**
 * MuninPluginDs.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuninPluginDs extends BaseModel
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'munin_plugins_ds';
    protected $primaryKey = 'mplug_id';
    protected $fillable = [
        'mplug_id',
        'ds_name',
        'ds_type',
        'ds_label',
        'ds_cdef',
        'ds_draw',
        'ds_info',
        'ds_extinfo',
        'ds_min',
        'ds_max',
        'ds_graph',
        'ds_negative',
        'ds_warning',
        'ds_critical',
        'ds_colour',
        'ds_sum',
        'ds_stack',
        'ds_line',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(MuninPlugin::class, 'mplug_id', 'mplug_id');
    }
}
