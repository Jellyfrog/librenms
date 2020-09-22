<?php
/**
 * PollerClusterStat.php
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
 * App\Models\PollerClusterStat
 *
 * @property int $id
 * @property int $parent_poller
 * @property string $poller_type
 * @property int $depth
 * @property int $devices
 * @property float $worker_seconds
 * @property int $workers
 * @property int $frequency
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereDevices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereParentPoller($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat wherePollerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereWorkerSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PollerClusterStat whereWorkers($value)
 * @mixin \Eloquent
 */
class PollerClusterStat extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';
//    protected $fillable = ['poller_name'];
}
