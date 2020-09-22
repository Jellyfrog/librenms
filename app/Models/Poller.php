<?php
/**
 * Widget.php
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
 * App\Models\Poller
 *
 * @property int $id
 * @property string $poller_name
 * @property string $last_polled
 * @property int $devices
 * @property float $time_taken
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller whereDevices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller whereLastPolled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller wherePollerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Poller whereTimeTaken($value)
 * @mixin \Eloquent
 */
class Poller extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $fillable = ['poller_name'];
}
