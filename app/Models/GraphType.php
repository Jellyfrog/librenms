<?php
/**
 * GraphTypes.php
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

/**
 * App\Models\GraphType
 *
 * @property string $graph_type
 * @property string $graph_subtype
 * @property string $graph_section
 * @property string|null $graph_descr
 * @property int $graph_order
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType whereGraphDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType whereGraphOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType whereGraphSection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType whereGraphSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\GraphType whereGraphType($value)
 * @mixin \Eloquent
 */
class GraphType extends BaseModel
{
    public $timestamps = false;
}
