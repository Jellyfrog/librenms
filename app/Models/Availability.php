<?php
/**
 * Availability.php
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 * @copyright  2020 Thomas Berberich
 * @author     Thomas Berberich <sourcehhdoctor@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Availability
 *
 * @property int $availability_id
 * @property int $device_id
 * @property int $duration
 * @property float $availability_perc
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability whereAvailabilityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability whereAvailabilityPerc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Availability whereDuration($value)
 * @mixin \Eloquent
 */
class Availability extends Model
{
    public $timestamps = false;
    protected $table = 'availability';
}
