<?php
/**
 * AuthLog.php
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
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AuthLog
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $datetime
 * @property string $user
 * @property string $address
 * @property string $result
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog whereDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AuthLog whereUser($value)
 * @mixin \Eloquent
 */
class AuthLog extends Model
{
    public $timestamps = false;
    protected $table = 'authlog';
    protected $dates = ['datetime'];
}
